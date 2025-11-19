"""
Main entry point for the CrewAI application.
"""
from __future__ import annotations

import sys
import os
import logging
import time
import re
from collections import Counter
from typing import Any, Dict, List

from crewai import Crew

# Import agents and tools
from agents import DocumentAnalysisAgent, SummaryAgent
from agents.matching_agent import MatchingAgent
from agents.web_scraper_agent import WebScraperAgent
from tools import PDFContentProcessor, PDFParser
from utils.result_parser import format_internships, parse_internship_results

logger = logging.getLogger(__name__)

TRANSIENT_ERROR_HINTS = (
    "llm failed",
    "quota",
    "rate limit",
    "429",
    "temporarily unavailable",
    "deadline exceeded",
    "unavailable",
    "exceeded",
)

STOPWORDS = {
    "and",
    "the",
    "with",
    "for",
    "your",
    "have",
    "from",
    "that",
    "this",
    "experience",
    "skills",
    "internship",
    "project",
    "team",
    "role",
}

FALLBACK_OPPORTUNITIES: List[Dict[str, Any]] = [
    {
        "company": "Google",
        "position": "Software Engineering Intern (Cloud Platforms)",
        "url": "https://careers.google.com/jobs/results/software-engineering-intern/",
        "tags": {"python", "cloud", "backend", "distributed", "software"},
    },
    {
        "company": "Microsoft",
        "position": "Data Science Intern",
        "url": "https://careers.microsoft.com/students/us/en/job/DA123456",
        "tags": {"data", "analytics", "python", "machine", "learning"},
    },
    {
        "company": "Amazon",
        "position": "Product Management Intern",
        "url": "https://www.amazon.jobs/en/jobs/PMINT2025",
        "tags": {"product", "management", "business"},
    },
    {
        "company": "Tesla",
        "position": "Electrical Engineering Intern",
        "url": "https://www.tesla.com/careers/internships",
        "tags": {"hardware", "electronics", "embedded"},
    },
    {
        "company": "Spotify",
        "position": "UX Research Intern",
        "url": "https://www.spotifyjobs.com/student-opportunities/",
        "tags": {"design", "research", "ux", "ui"},
    },
]

# ---------- Helper functions ----------
def _is_transient_llm_error(error: Exception) -> bool:
    message = str(error).lower()
    return any(hint in message for hint in TRANSIENT_ERROR_HINTS)


def _kickoff_with_retries(document_crew: Crew, max_attempts: int = 3, base_delay: int = 3):
    last_error: Exception | None = None
    for attempt in range(1, max_attempts + 1):
        try:
            return document_crew.kickoff()
        except Exception as exc:
            last_error = exc
            if not _is_transient_llm_error(exc) or attempt == max_attempts:
                break

            sleep_for = base_delay * attempt
            logger.warning(
                "Crew kickoff failed due to transient LLM issue (%s). Retrying in %ss (%s/%s)",
                exc,
                sleep_for,
                attempt,
                max_attempts,
            )
            time.sleep(sleep_for)

    if last_error:
        raise last_error


def _extract_keywords(text: str, limit: int = 5) -> List[str]:
    tokens = re.findall(r"[a-zA-Z]{3,}", text.lower())
    filtered = [token for token in tokens if token not in STOPWORDS]
    if not filtered:
        return []
    counts = Counter(filtered)
    return [word for word, _ in counts.most_common(limit)]


def _score_fallbacks(keywords: List[str]) -> List[Dict[str, Any]]:
    if not keywords:
        return FALLBACK_OPPORTUNITIES[:]

    ranked: List[Dict[str, Any]] = []
    for job in FALLBACK_OPPORTUNITIES:
        score = sum(1 for keyword in keywords if keyword in job.get("tags", set()))
        job_copy = {k: v for k, v in job.items() if k != "tags"}
        job_copy["match_score"] = score
        ranked.append(job_copy)

    return sorted(ranked, key=lambda item: item.get("match_score", 0), reverse=True)


def _build_fallback_payload(processed_content: str, reason: str) -> Dict[str, object]:
    keywords = _extract_keywords(processed_content)
    ranked = _score_fallbacks(keywords)
    top_matches = [
        {
            "id": idx + 1,
            "company": job["company"],
            "position": job["position"],
            "url": job["url"],
        }
        for idx, job in enumerate(ranked[:3])
    ]

    formatted = format_internships(top_matches)

    logger.warning("Serving fallback internships because LLM failed: %s", reason)

    return {
        "success": True,
        "internships": top_matches,
        "fallback_used": True,
        "fallback_reason": reason,
        "raw_output": formatted,
        "keywords": keywords,
        "source": "local-fallback",
    }


def _content_is_empty(extracted_text: str | None) -> bool:
    if not extracted_text:
        return True
    normalized = extracted_text.strip().lower()
    return (
        not normalized
        or normalized.startswith("warning: no text content was extracted")
        or normalized.startswith("error: the provided path")
        or normalized.startswith("pdf parsing failed")
    )


# ---------- Main function ----------
def main(pdf_path_or_content=None):
    if pdf_path_or_content is None:
        pdf_path_or_content = sys.argv[1] if len(sys.argv) > 1 else None

    if not pdf_path_or_content:
        print("Please provide a PDF/TXT file path or extracted text content as an argument.")
        return

    is_file_path = isinstance(pdf_path_or_content, str) and (
        pdf_path_or_content.lower().endswith('.pdf') and os.path.exists(pdf_path_or_content)
    )

    # ---------- File size & page limit ----------
    if is_file_path:
        if os.path.getsize(pdf_path_or_content) > 5 * 1024 * 1024:  # 5MB
            return {"success": False, "error": "PDF_TOO_LARGE", "message": "PDF too large. Split it into smaller files."}
        pdf_parser = PDFParser()
        num_pages = pdf_parser.get_number_of_pages(pdf_path_or_content)
        if num_pages > 20:
            return {"success": False, "error": "PDF_TOO_LONG", "message": "PDF too many pages (>20). Split into smaller PDFs."}
        pdf_content = pdf_parser.parse_pdf(pdf_path_or_content)
        print(f"Parsed PDF: {pdf_path_or_content}")
    else:
        pdf_content = pdf_path_or_content

    if _content_is_empty(pdf_content):
        message = "We couldn't read any text from this file. Upload a text-based resume or use OCR first."
        return {"success": False, "error": "FILE_TEXT_EMPTY", "message": message}

    # ---------- Chunk content ----------
    pdf_processor = PDFContentProcessor()
    chunk_size = 1000  # characters
    chunks = [pdf_content[i:i + chunk_size] for i in range(0, len(pdf_content), chunk_size)]
    processed_chunks = [pdf_processor.process_content(chunk) for chunk in chunks]
    processed_content = " ".join(processed_chunks)

    # ---------- Run agents sequentially ----------
    try:
        # Document Analysis
        doc_agent = DocumentAnalysisAgent(pdf_content=processed_content)
        doc_crew = Crew(agents=[doc_agent.get_agent()], tasks=[doc_agent.get_task()], verbose=True)
        result_doc = _kickoff_with_retries(doc_crew)

        # Summary Agent
        sum_agent = SummaryAgent()
        sum_crew = Crew(agents=[sum_agent.get_agent()], tasks=[sum_agent.get_task()], verbose=True)
        result_sum = _kickoff_with_retries(sum_crew)

        # Matching Agent
        match_agent = MatchingAgent()
        match_crew = Crew(agents=[match_agent.get_agent()], tasks=[match_agent.get_task()], verbose=True)
        result_match = _kickoff_with_retries(match_crew)

        # Optional: Web Scraper Agent
        # web_agent = WebScraperAgent()
        # web_crew = Crew(agents=[web_agent.get_agent()], tasks=[web_agent.get_task()], verbose=True)
        # result_web = _kickoff_with_retries(web_crew)

        final_result = str(result_match)
        parsed = parse_internship_results(final_result)

        internships = parsed.get("internships", [])
        if not internships:
            return _build_fallback_payload(processed_content, "Crew returned empty result")

        return {
            "success": True,
            "internships": internships,
            "raw_output": final_result,
            "fallback_used": False,
            "source": "llm",
        }

    except Exception as exc:
        if _is_transient_llm_error(exc):
            return _build_fallback_payload(processed_content, str(exc))
        raise


if __name__ == "__main__":
    main()
