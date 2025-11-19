# Gemini API Key Guide

This guide helps you determine if you need a new Gemini API key or can use an existing one.

## 🔍 Step 1: Check if You Have an Existing Key

### Check Local Environment Files

1. **Check your `.env` file in the `agents` folder:**
   ```bash
   cd agents
   cat .env | grep GOOGLE_API_KEY
   ```
   Or on Windows:
   ```powershell
   cd agents
   type .env | findstr GOOGLE_API_KEY
   ```

2. **If you see a line like:**
   ```
   GOOGLE_API_KEY=AIzaSy...
   ```
   You have an existing key! Skip to Step 2 to test it.

### Check if Key is Set in System Environment

```bash
# Windows PowerShell
$env:GOOGLE_API_KEY

# Windows CMD
echo %GOOGLE_API_KEY%

# Linux/Mac
echo $GOOGLE_API_KEY
```

## ✅ Step 2: Test Your Existing API Key

### Quick Test Script

Create a test file to verify your key works:

```python
# test_gemini_key.py
import os
from dotenv import load_dotenv

load_dotenv()

api_key = os.getenv("GOOGLE_API_KEY")

if not api_key:
    print("❌ No API key found!")
    print("You need to create a new key or set it in your .env file")
    exit(1)

print(f"✅ API Key found: {api_key[:10]}...")

# Test the key
try:
    import google.generativeai as genai
    genai.configure(api_key=api_key)
    
    # Try a simple API call
    model = genai.GenerativeModel('gemini-2.5-flash')
    response = model.generate_content("Say hello")
    
    print("✅ API Key is VALID and working!")
    print(f"Response: {response.text}")
    
except Exception as e:
    error_msg = str(e).lower()
    
    if "api_key" in error_msg or "invalid" in error_msg or "unauthorized" in error_msg:
        print("❌ API Key is INVALID or expired")
        print("You need to create a new key")
    elif "quota" in error_msg or "rate limit" in error_msg:
        print("⚠️ API Key is valid but quota exceeded")
        print("You can use this key, but may need to wait or upgrade plan")
    else:
        print(f"⚠️ Error testing key: {e}")
        print("Key might be valid, but there's a connection issue")
```

Run it:
```bash
cd agents
python test_gemini_key.py
```

### Test via Your Application

1. **Start your agents service:**
   ```bash
   cd agents
   python app.py
   ```

2. **Test the health endpoint:**
   ```bash
   curl http://localhost:5000/health
   ```

3. **Try uploading a PDF:**
   - If you get errors like "GOOGLE_API_KEY is not set" → Key is missing
   - If you get "API key invalid" → Key is expired/invalid
   - If you get "quota exceeded" → Key is valid but hit limits
   - If it works → Key is good! ✅

## 🆕 Step 3: When to Create a New Key

### Create a NEW key if:

1. ❌ **You don't have a key** - No `.env` file or `GOOGLE_API_KEY` variable
2. ❌ **Key is invalid** - Test script shows "invalid" or "unauthorized"
3. ❌ **Key is expired** - Google revoked it (rare, but possible)
4. ❌ **Key was deleted** - You removed it from Google Cloud Console
5. ⚠️ **Quota exceeded** - Free tier limits hit and you want a fresh start
6. 🔒 **Security concern** - Key was exposed/leaked (create new one immediately!)

### You can REUSE existing key if:

1. ✅ **Key works** - Test script shows it's valid
2. ✅ **No quota issues** - You haven't hit free tier limits
3. ✅ **Same Google account** - You're using the same Google Cloud project
4. ✅ **Key not exposed** - It's still secure

## 🔑 Step 4: How to Create a New Gemini API Key

### Method 1: Google AI Studio (Easiest)

1. **Go to:** https://aistudio.google.com/apikey
2. **Sign in** with your Google account
3. **Click** "Create API Key"
4. **Choose:**
   - "Create API key in new project" (recommended for new users)
   - OR "Create API key in existing project" (if you have a Google Cloud project)
5. **Copy the key** - It looks like: `AIzaSy...`
6. **Save it immediately** - You can only see it once!

### Method 2: Google Cloud Console

1. **Go to:** https://console.cloud.google.com/
2. **Create or select a project**
3. **Enable Gemini API:**
   - Go to "APIs & Services" → "Library"
   - Search for "Generative Language API"
   - Click "Enable"
4. **Create API Key:**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "API Key"
   - Copy the key

## 📝 Step 5: Set Your API Key

### For Local Development

1. **Create/update `.env` file in `agents` folder:**
   ```bash
   cd agents
   echo "GOOGLE_API_KEY=your_actual_key_here" > .env
   ```

2. **Or edit `.env` file manually:**
   ```
   GOOGLE_API_KEY=AIzaSyYourActualKeyHere
   ```

### For Render Deployment

1. **Go to Render Dashboard**
2. **Select your `oppmatch-agents` service**
3. **Go to "Environment" tab**
4. **Add new environment variable:**
   - Key: `GOOGLE_API_KEY`
   - Value: `your_actual_key_here`
5. **Click "Save Changes"**
6. **Service will redeploy automatically**

## 🎯 Quick Decision Tree

```
Do you have a GOOGLE_API_KEY?
│
├─ NO → Create new key (Step 4)
│
└─ YES → Test it (Step 2)
    │
    ├─ Works ✅ → Use it! (Set in .env or Render)
    │
    ├─ Invalid ❌ → Create new key (Step 4)
    │
    └─ Quota Exceeded ⚠️ → 
        ├─ Wait 24 hours (free tier resets)
        ├─ OR Create new key (if you have multiple Google accounts)
        └─ OR Upgrade to paid plan
```

## 💡 Tips

1. **Free Tier Limits:**
   - 15 requests per minute (RPM)
   - 1,500 requests per day (RPD)
   - Resets daily at midnight Pacific Time

2. **Security Best Practices:**
   - Never commit API keys to Git
   - Use `.env` files (already in `.gitignore`)
   - Rotate keys if exposed
   - Use different keys for dev/prod

3. **Multiple Keys:**
   - You can have multiple API keys per Google account
   - Useful for separating dev/staging/production
   - Each key has its own quota limits

## 🆘 Troubleshooting

### "GOOGLE_API_KEY is not set"
- **Solution:** Add key to `.env` file or set as environment variable

### "API key invalid"
- **Solution:** Create a new key (old one expired or was deleted)

### "Quota exceeded"
- **Solution:** Wait 24 hours OR create new key with different Google account OR upgrade plan

### "Permission denied"
- **Solution:** Enable "Generative Language API" in Google Cloud Console

## 📚 Resources

- **Google AI Studio:** https://aistudio.google.com/
- **API Documentation:** https://ai.google.dev/docs
- **Pricing:** https://ai.google.dev/pricing
- **Quota Limits:** Check in Google Cloud Console → APIs & Services → Quotas

