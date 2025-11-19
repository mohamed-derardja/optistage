from crewai import Agent, Task
import config
import json
import requests
from urllib.parse import quote

class WebScraperAgent:
    def __init__(self):
        self.tools = []  # Will use built-in requests library
        
    def get_agent(self):
        """Create and return the web scraper agent."""
        from crewai.llm import LLM

        if not config.GOOGLE_API_KEY:
            raise ValueError("GOOGLE_API_KEY is not set. Please check your .env file.")

        gemini_llm = LLM(
            model="gemini/gemini-2.5-flash",
            api_key=config.GOOGLE_API_KEY,
            temperature=config.TEMPERATURE,
        )

        return Agent(
            role="Web Research Specialist",
            goal="Find real-world internship opportunities from online sources",
            backstory=(
                "You are an expert web researcher specialized in finding educational and "
                "career opportunities. With extensive experience in navigating employment "
                "platforms, you excel at discovering relevant internship "
                "opportunities that match specific candidate profiles. "
                "Use only working links and reject any 404 or invalid URLs."
            ),
            verbose=True,
            allow_delegation=False,
            llm=gemini_llm,
        )
    
    def get_task(self):
        """Create and return the web scraping task."""
        return Task(
            description=(
                "Using the candidate profile information extracted by the Document Analysis Agent, "
                "research and find real internship opportunities that match the candidate's "
                "skills, education, and interests. Use LinkedIn and job boards to find current opportunities."
            ),
            expected_output=(
                "A JSON structure containing: "
                "A list of 8-10 internship opportunities with title, company, location, and requirements. "
                "Each entry should include a verified source URL."
            ),
            agent=self.get_agent()
        )
    
    def search_linkedin_internships(self, profile_data):
        """
        Search LinkedIn for internships matching the candidate's profile.
        
        Args:
            profile_data: JSON structure containing candidate information
            
        Returns:
            List of verified internship opportunities
        """
        try:
            # Parse profile data if it's a string
            if isinstance(profile_data, str):
                profile = json.loads(profile_data)
            else:
                profile = profile_data
                
            # Extract relevant profile information
            skills = profile.get("skills", [])
            if isinstance(skills, str):
                skills = [skill.strip() for skill in skills.split(",")]
                
            education = profile.get("education", {})
            if isinstance(education, list):
                majors = [edu.get("field_of_study", "") for edu in education]
            else:
                majors = [education.get("field_of_study", "")]
            
            # Combine skills and majors for search terms
            search_terms = skills + majors
            search_terms = [term for term in search_terms if term]  # remove empty terms
            
            if not search_terms:
                return [{"error": "No skills or education information provided for search."}]
            
            # Simulate LinkedIn search results
            results = self._simulate_linkedin_search(search_terms[:3])
            
            # Filter by relevance to search terms
            filtered_results = [
                job for job in results
                if any(term.lower() in job["title"].lower() or term.lower() in job["description"].lower()
                       for term in search_terms)
            ]
            
            # Verify URLs
            verified_results = [job for job in filtered_results if self._verify_url(job["url"])]
            
            if not verified_results:
                return [{"error": "No valid internship opportunities found matching the profile."}]
            
            return verified_results
            
        except Exception as e:
            return [{"error": f"LinkedIn search failed: {str(e)}"}]
    
    def _verify_url(self, url):
        """Check if a URL is valid (returns HTTP 200)."""
        try:
            response = requests.head(url, allow_redirects=True, timeout=5)
            return response.status_code == 200
        except:
            return False
    
    def _simulate_linkedin_search(self, search_terms):
        """
        Simulate LinkedIn search results for demonstration purposes.
        In a real implementation, this would use web scraping or LinkedIn API.
        """
        # Predefined URLs (some popular internship pages)
        real_job_urls = [
            "https://www.linkedin.com/jobs/view/3824049375/",
            "https://www.linkedin.com/jobs/view/3839271235/",
            "https://www.linkedin.com/jobs/view/3841124576/",
            "https://boards.greenhouse.io/stripe/jobs/7488899002",
            "https://jobs.lever.co/notion/9cfab555-d17e-463a-9454-569eae5aab8b",
            "https://jobs.lever.co/figma/47f79082-3d98-4d77-9cba-5a2bedf5a0f2",
            "https://www.intel.com/content/www/us/en/jobs/job-search.html?job=JR0254618",
            "https://careers.microsoft.com/students/us/en/job/1717349",
            "https://careers.google.com/jobs/results/124400129381799174/",
            "https://www.amazon.jobs/en/jobs/2629032"
        ]
        
        job_titles = [
            "Software Engineering Intern", 
            "Data Science Intern", 
            "Marketing Intern",
            "UX/UI Design Intern", 
            "Business Development Intern",
            "Research Assistant", 
            "Product Management Intern",
            "Content Creation Intern", 
            "IT Support Intern",
            "Finance Intern"
        ]
        
        companies = [
            "Google", "Microsoft", "Amazon", "Meta", "Apple",
            "IBM", "Deloitte", "Goldman Sachs", "Tesla", "Spotify"
        ]
        
        locations = [
            "Remote", "New York, NY", "San Francisco, CA", "Austin, TX", 
            "Seattle, WA", "Boston, MA", "Chicago, IL", "Los Angeles, CA"
        ]
        
        simulated_results = []
        for i in range(10):
            job_index = i % len(job_titles)
            company_index = i % len(companies)
            location_index = i % len(locations)
            
            internship = {
                "title": job_titles[job_index],
                "company": companies[company_index],
                "location": locations[location_index],
                "description": f"Exciting opportunity to work at {companies[company_index]} and gain hands-on experience in {job_titles[job_index].split(' ')[0]}.",
                "requirements": [search_terms[i % len(search_terms)], "Communication skills", "Problem-solving", "Teamwork"],
                "url": real_job_urls[i % len(real_job_urls)],
                "source": "LinkedIn"
            }
            simulated_results.append(internship)
        
        return simulated_results
