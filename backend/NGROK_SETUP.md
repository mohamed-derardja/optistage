# Ngrok Setup Guide for Local Payment Development

## Why Ngrok is Needed

When developing payment integrations locally, payment providers (like ChargilyPay) need to send callbacks and webhooks to your application. Since `localhost` URLs are not accessible from the internet, you need a tool like ngrok to create a public tunnel to your local server.

## Prerequisites

1. A running Laravel backend server (typically on `http://localhost:8000`)
2. Ngrok installed on your system

## Step 1: Install Ngrok

### Windows
1. Download ngrok from https://ngrok.com/download
2. Extract the `ngrok.exe` file to a folder (e.g., `C:\ngrok`)
3. Add the folder to your system PATH, or use the full path when running commands

### macOS
```bash
brew install ngrok/ngrok/ngrok
```

### Linux
```bash
# Download and install
curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | sudo tee /etc/apt/sources.list.d/ngrok.list
sudo apt update && sudo apt install ngrok
```

## Step 2: Sign Up for Ngrok (Free Account)

1. Go to https://dashboard.ngrok.com/signup
2. Create a free account
3. Get your authtoken from https://dashboard.ngrok.com/get-started/your-authtoken

## Step 3: Configure Ngrok

Run this command to authenticate (replace `YOUR_AUTHTOKEN` with your actual token):

```bash
ngrok config add-authtoken YOUR_AUTHTOKEN
```

## Step 4: Start Your Laravel Server

Make sure your Laravel backend is running:

```bash
cd backend
php artisan serve
```

Your server should be running on `http://localhost:8000` (or another port if specified).

## Step 5: Start Ngrok Tunnel

Open a new terminal window and run:

```bash
ngrok http 8000
```

**Note:** If your Laravel server is running on a different port, replace `8000` with your port number.

You'll see output like this:

```
Session Status                online
Account                       Your Name (Plan: Free)
Version                       3.x.x
Region                        United States (us)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:8000
```

**Important:** Copy the `Forwarding` URL (e.g., `https://abc123.ngrok-free.app`). This is your ngrok URL.

## Step 6: Configure Your .env File

Add the ngrok URL to your `backend/.env` file:

```env
# Ngrok URL for local development (leave empty in production)
NGROK_URL=https://abc123.ngrok-free.app

# Your existing APP_URL (keep this for production)
APP_URL=http://localhost:8000
```

**Important Notes:**
- The `NGROK_URL` will be used for payment callbacks when set
- If `NGROK_URL` is not set, the system will fall back to `APP_URL`
- In production, don't set `NGROK_URL` (or leave it empty)

## Step 7: Configure ChargilyPay Webhook

1. Log in to your ChargilyPay dashboard: https://pay.chargily.com
2. Go to **Settings** → **Webhooks**
3. Add a new webhook with the URL:
   ```
   https://your-ngrok-url.ngrok-free.app/api/chargilypay/webhook
   ```
   Replace `your-ngrok-url` with your actual ngrok URL from Step 5.

4. Select the events you want to listen to (typically: `checkout.paid`, `checkout.failed`)

## Step 8: Restart Your Laravel Server

After updating your `.env` file, restart your Laravel server:

```bash
# Stop the server (Ctrl+C) and restart
php artisan serve
```

## Step 9: Test the Payment Flow

1. Make sure both your Laravel server and ngrok are running
2. Try to complete a payment in your application
3. The payment should redirect properly and callbacks should work

## Troubleshooting

### Issue: Ngrok URL changes every time

**Solution:** With a free ngrok account, the URL changes each time you restart ngrok. You have two options:

1. **Update .env each time:** When you restart ngrok, update the `NGROK_URL` in your `.env` file
2. **Use ngrok static domain (paid):** Upgrade to a paid ngrok plan to get a static domain

### Issue: Payment callbacks not working

**Checklist:**
- ✅ Ngrok is running and forwarding to the correct port
- ✅ `NGROK_URL` is set correctly in `.env` (without trailing slash)
- ✅ Laravel server is running and accessible via ngrok
- ✅ Webhook URL is configured in ChargilyPay dashboard
- ✅ You've restarted Laravel server after updating `.env`

### Issue: "Tunnel not found" or connection errors

- Make sure ngrok is running
- Verify the port number matches your Laravel server port
- Check that your firewall isn't blocking ngrok

### Issue: Ngrok shows "This site can't be reached"

- Make sure your Laravel server is running on the port ngrok is forwarding to
- Try accessing `http://localhost:8000` directly in your browser first

## Quick Start Script (Optional)

You can create a simple script to start both services:

**Windows (start-dev.bat):**
```batch
@echo off
start "Laravel Server" cmd /k "cd backend && php artisan serve"
timeout /t 3
start "Ngrok" cmd /k "ngrok http 8000"
echo.
echo Laravel server and ngrok are starting...
echo Don't forget to update NGROK_URL in your .env file with the ngrok URL!
pause
```

**macOS/Linux (start-dev.sh):**
```bash
#!/bin/bash
cd backend
php artisan serve &
sleep 3
ngrok http 8000 &
echo "Laravel server and ngrok are starting..."
echo "Don't forget to update NGROK_URL in your .env file with the ngrok URL!"
```

## Production Deployment

When deploying to production:
1. Remove or leave `NGROK_URL` empty in your production `.env`
2. Set `APP_URL` to your production domain (e.g., `https://yourdomain.com`)
3. Update the webhook URL in ChargilyPay dashboard to your production URL

## Additional Resources

- Ngrok Documentation: https://ngrok.com/docs
- ChargilyPay Webhooks: https://pay.chargily.com/docs/webhooks

