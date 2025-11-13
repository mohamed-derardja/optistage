# Payment Setup Guide

## ChargilyPay Configuration

The payment system requires ChargilyPay API credentials to be configured in your `.env` file.

### Required Environment Variables

Add these to your `backend/.env` file:

```env
# ChargilyPay Configuration
CHARGILY_PAY_MODE=test
CHARGILY_PAY_PUBLIC=your_public_key_here
CHARGILY_PAY_SECRET=your_secret_key_here
```

### Getting ChargilyPay Credentials

1. Sign up for a ChargilyPay account at https://pay.chargily.com
2. Go to your dashboard
3. Navigate to API Keys section
4. Copy your Public Key and Secret Key
5. Add them to your `.env` file

### Test Mode vs Production Mode

- **Test Mode:** Use `CHARGILY_PAY_MODE=test` with test credentials
- **Production Mode:** Use `CHARGILY_PAY_MODE=live` with live credentials

### Payment Flow

1. User clicks "Complete Payment" button
2. Frontend calls `/api/chargilypay/redirect`
3. Backend creates a payment record and ChargilyPay checkout
4. User is redirected to ChargilyPay payment page
5. After payment, user is redirected back to `/api/chargilypay/back`
6. Backend verifies payment and updates user access

### Troubleshooting

#### Error: "Payment gateway credentials not configured"
- **Solution:** Make sure `CHARGILY_PAY_PUBLIC` and `CHARGILY_PAY_SECRET` are set in your `.env` file
- Restart your Laravel server after updating `.env`

#### Error: 500 Internal Server Error
- Check the Laravel logs: `backend/storage/logs/laravel.log`
- Verify your ChargilyPay credentials are correct
- Make sure the ChargilyPay package is installed: `composer require chargily/chargily-pay`

#### Payment Not Processing
- Verify you're using the correct mode (test/live)
- Check that your ChargilyPay account is active
- Ensure webhook URLs are configured in ChargilyPay dashboard

### Testing Payments

In test mode, you can use test card numbers provided by ChargilyPay to simulate payments.

### Security Notes

- Never commit your `.env` file to version control
- Use test credentials during development
- Switch to production credentials only when deploying to production
- Keep your secret keys secure

