# ChargilyPay Configuration Guide

## ✅ You've Added Credentials!

Since you've added the ChargilyPay credentials to your `.env` file, the system will now use the **real ChargilyPay API**.

## Configuration in .env

Make sure your `backend/.env` file has:

```env
# ChargilyPay Configuration
CHARGILY_PAY_MODE=test
CHARGILY_PAY_PUBLIC=your_public_key_here
CHARGILY_PAY_SECRET=your_secret_key_here

# Optional: Set to true to force mock mode (for testing without real API)
# CHARGILY_PAY_USE_MOCK=false
```

## Important: Restart Your Server

**After adding credentials, you MUST restart your Laravel server:**

```bash
cd backend
php artisan serve
```

## How It Works Now

1. **With Credentials (Your Current Setup):**
   - Uses real ChargilyPay API
   - Creates actual payment checkouts
   - Redirects to ChargilyPay payment page
   - Processes real payments

2. **Without Credentials (Fallback):**
   - Automatically uses mock mode
   - Simulates payments for testing
   - No real API calls

3. **Force Mock Mode:**
   - Set `CHARGILY_PAY_USE_MOCK=true` in `.env`
   - Useful for testing without making real API calls

## Testing

1. **Restart your backend server**
2. **Click "Complete Payment" in the frontend**
3. **You should be redirected to ChargilyPay payment page**
4. **Complete the payment using test credentials**
5. **You'll be redirected back and access will be granted**

## Test Mode Credentials

In test mode, ChargilyPay provides test card numbers you can use:
- Check your ChargilyPay dashboard for test payment methods
- These won't charge real money

## Troubleshooting

### Still Getting 500 Error?
1. **Check your credentials are correct** - no extra spaces, correct format
2. **Restart the server** - Laravel caches .env values
3. **Check logs**: `backend/storage/logs/laravel.log`
4. **Verify mode**: Make sure `CHARGILY_PAY_MODE=test` for testing

### Want to Test Without Real API?
Set in `.env`:
```env
CHARGILY_PAY_USE_MOCK=true
```

This will use mock mode even if credentials are present.

## Production Setup

When ready for production:
1. Change `CHARGILY_PAY_MODE=live`
2. Use your production API keys
3. Configure webhooks in ChargilyPay dashboard
4. Test thoroughly before going live

---

**Your payment system is now configured and ready to use!** 🎉

