"""Helpers to normalize internship output coming from the crew."""

from __future__ import annotations

import re
from typing import Dict, List


def parse_internship_results(result_str: str) -> Dict[str, object]:
    """Parse the internship results from the crew output into structured JSON."""
    pattern = r"(\d+)-\s+([^\n]+)\n([^\n]+)\nlink:\s+([^\n]+)"
    matches = re.findall(pattern, result_str)

    internships: List[Dict[str, object]] = []
    for match in matches:
        internship_number, company, position, url = match
        internships.append(
            {
                "id": int(internship_number),
                "company": company.strip(),
                "position": position.strip(),
                "url": url.strip(),
            }
        )

    return {
        "success": True,
        "internships": internships,
    }


def format_internships(internships: List[Dict[str, str]]) -> str:
    """Format a list of internships using the legacy numbered output."""
    formatted_rows = []
    for idx, internship in enumerate(internships, start=1):
        formatted_rows.append(
            f"{idx}- {internship['company']}\n"
            f"{internship['position']}\n"
            f"link: {internship['url']}"
        )

    return "\n\n".join(formatted_rows)

