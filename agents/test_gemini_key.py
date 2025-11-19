#!/usr/bin/env python3
"""
Quick script to test if your Gemini API key is valid.
Run this from the agents directory: python test_gemini_key.py
"""
import os
import sys
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def test_api_key():
    """Test if the Gemini API key is valid."""
    
    # Get API key
    api_key = os.getenv("GOOGLE_API_KEY") or os.getenv("GEMINI_API_KEY")
    
    if not api_key:
        print("❌ ERROR: No API key found!")
        print("\nTo fix this:")
        print("1. Create a .env file in the agents folder")
        print("2. Add: GOOGLE_API_KEY=your_key_here")
        print("3. Get a key from: https://aistudio.google.com/apikey")
        return False
    
    print(f"✅ API Key found: {api_key[:15]}...{api_key[-5:]}")
    print("Testing API key...\n")
    
    try:
        import google.generativeai as genai
        genai.configure(api_key=api_key)
        
        # Try a simple API call
        model = genai.GenerativeModel('gemini-2.5-flash')
        response = model.generate_content("Say 'Hello, API key is working!'")
        
        print("✅ SUCCESS: API Key is VALID and working!")
        print(f"   Response: {response.text.strip()}")
        print("\n✅ You can use this key for deployment!")
        return True
        
    except ImportError:
        print("❌ ERROR: google-generativeai package not installed")
        print("   Run: pip install google-generativeai")
        return False
        
    except Exception as e:
        error_msg = str(e).lower()
        
        if any(word in error_msg for word in ["api_key", "invalid", "unauthorized", "permission"]):
            print("❌ ERROR: API Key is INVALID or expired")
            print("\nTo fix this:")
            print("1. Create a new key at: https://aistudio.google.com/apikey")
            print("2. Update your .env file with the new key")
            return False
            
        elif "quota" in error_msg or "rate limit" in error_msg:
            print("⚠️  WARNING: API Key is valid but QUOTA EXCEEDED")
            print("   The key works, but you've hit the free tier limits")
            print("   Wait 24 hours or upgrade your plan")
            return True  # Key is valid, just quota issue
            
        else:
            print(f"⚠️  WARNING: Unexpected error: {e}")
            print("   The key might be valid, but there's a connection issue")
            print("   Check your internet connection and try again")
            return False

if __name__ == "__main__":
    print("=" * 60)
    print("Gemini API Key Tester")
    print("=" * 60)
    print()
    
    success = test_api_key()
    
    print()
    print("=" * 60)
    if success:
        print("✅ Your API key is ready to use!")
        sys.exit(0)
    else:
        print("❌ Please fix the issues above before deploying")
        sys.exit(1)

