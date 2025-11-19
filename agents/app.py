"""
Flask application to handle PDF uploads and process them using CrewAI.
"""
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
import os
import tempfile
import json
import re
from main import main
from crewai.crew import CrewOutput

try:
    from google.genai.errors import ClientError as GeminiClientError
except ImportError:  # pragma: no cover - fallback when package missing
    class GeminiClientError(Exception):
        pass

app = Flask(__name__)

UPLOAD_FOLDER = tempfile.gettempdir()
ALLOWED_EXTENSIONS = {'pdf'}

app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max upload size

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@app.route('/health', methods=['GET'])
def health_check():
    """Simple health check endpoint."""
    return jsonify({"status": "healthy", "message": "Service is running"})

@app.route('/process-pdf', methods=['POST'])
def process_pdf():
    """
    Endpoint to process uploaded PDF files.
    
    Expected format: multipart/form-data with a 'file' field containing the PDF
    """
    # Check if the post request has the file part
    if 'file' not in request.files:
        return jsonify({"error": "No file part in the request"}), 400
    
    file = request.files['file']
    
    # If user does not select file, browser might also
    # submit an empty part without filename
    if file.filename == '':
        return jsonify({"error": "No file selected"}), 400
    
    if file and allowed_file(file.filename):
        # Save the file temporarily
        filename = secure_filename(file.filename)
        temp_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        file.save(temp_path)
        
        try:
            # Process the PDF using the CrewAI pipeline
            result = main(temp_path)
            
            # Handle CrewOutput object - convert to string
            if hasattr(result, '__class__') and result.__class__.__name__ == 'CrewOutput':
                result = str(result)
            
            # Parse the internship data into a structured format
            if isinstance(result, str):
                try:
                    # Try to parse the result as structured internships
                    internships = parse_internship_results(result)
                    return jsonify(internships)
                except Exception as e:
                    # If parsing fails, return the raw result
                    return jsonify({"success": True, "result": result})
            else:
                # If it's already a dict or other JSON-serializable object
                return jsonify({"success": True, "result": result})
                
        except GeminiClientError as e:
            error_payload = getattr(e, "response_json", {})
            error_body = (error_payload or {}).get("error", {})
            message = error_body.get("message") or "Gemini quota exceeded. Please try again shortly."
            retry_after = None

            for detail in error_body.get("details", []):
                if detail.get("@type") == "type.googleapis.com/google.rpc.RetryInfo":
                    retry_after = detail.get("retryDelay")
                    break

            response_body = {
                "success": False,
                "message": message,
                "code": "LLM_QUOTA_EXCEEDED",
                "provider": "google-gemini",
                "retry_after": retry_after,
                "details": error_body or str(e),
            }

            print(f"Gemini quota error: {message}")
            return jsonify(response_body), 429

        except Exception as e:
            import traceback
            error_details = traceback.format_exc()
            print(f"Error processing PDF: {str(e)}")
            print(f"Traceback: {error_details}")
            return jsonify({
                "error": f"Processing error: {str(e)}",
                "details": error_details
            }), 500
        finally:
            # Clean up the temporary file
            if os.path.exists(temp_path):
                os.remove(temp_path)
    
    return jsonify({"error": "Invalid file type. Only PDF files are allowed."}), 400

def parse_internship_results(result_str):
    """
    Parse the internship results from the text format to structured JSON.
    
    Args:
        result_str: String containing internship data in format:
                   "1- Company\nPosition\nlink: URL\n\n2- Company..."
    
    Returns:
        A dictionary with properly structured internship data
    """
    # Extract internship entries using regex
    pattern = r'(\d+)-\s+([^\n]+)\n([^\n]+)\nlink:\s+([^\n]+)'
    matches = re.findall(pattern, result_str)
    
    internships = []
    for match in matches:
        internship_number, company, position, url = match
        internships.append({
            "id": int(internship_number),
            "company": company.strip(),
            "position": position.strip(),
            "url": url.strip()
        })
    
    return {
        "success": True,
        "internships": internships
    }

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)