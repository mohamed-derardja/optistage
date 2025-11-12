<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChargilyPayment;
use App\Models\User;
use Chargily\ChargilyPay\ChargilyPay;
use Chargily\ChargilyPay\Auth\Credentials;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChargilyPayController extends Controller
{
    public function redirect(Request $request)
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

        $currency = 'dzd';
        $amount = '25000'; 

        $payment = ChargilyPayment::create([
            'user_id'  => $userId,
            'status'   => 'pending',
            'currency' => $currency,
            'amount'   => $amount,
        ]);

   
        
        $checkout = $this->chargilyPayInstance()->checkouts()->create([
            'metadata' => [
                'payment_id' => $payment->id,
            ],
            'locale' => 'ar',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'description' => "Payment ID={$payment->id}",
            'success_url' => url('/api/chargilypay/back'),
            'failure_url' => url('/api/chargilypay/back'),
            
        ]);

                 $user = User::findOrFail($userId);
            $user->access_expires_at = Carbon::now()->addDays(30);
            $user->save();
            
            return response()->json([
                "success" => true,
                "url" => $checkout->getUrl()
            ]);
        } catch (\Exception $e) {
            Log::error('ChargilyPay Redirect Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment redirect',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function back(Request $request)
    {
        try {
            $checkout_id = $request->input('checkout_id');
            
            if (!$checkout_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checkout ID is required'
                ], 400);
            }
            
            $checkout = $this->chargilyPayInstance()->checkouts()->get($checkout_id);

        if ($checkout) {
            $metadata = $checkout->getMetadata();
            $payment = ChargilyPayment::find($metadata['payment_id']);

            // Simulate webhook result locally
            if ($checkout->getStatus() === 'paid') {
                $payment->status = 'paid';
            } else {
                $payment->status = 'failed';
            }
            $payment->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed locally.',
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Checkout not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('ChargilyPay Back Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $webhook = $this->chargilyPayInstance()->webhook()->get();

            if ($webhook) {
                $checkout = $webhook->getData();
                if ($checkout && $checkout instanceof \Chargily\ChargilyPay\Elements\CheckoutElement) {
                    $metadata = $checkout->getMetadata();
                    $payment = ChargilyPayment::find($metadata['payment_id']);

                    if ($payment) {
                        if ($checkout->getStatus() === 'paid') {
                            $payment->status = 'paid';
                        } else {
                            $payment->status = 'failed';
                        }
                        $payment->save();

                        return response()->json([
                            'success' => true,
                            'status' => true,
                            'message' => 'Updated from webhook'
                        ]);
                    }
                }
            }
            
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Invalid Webhook'
            ], 403);
        } catch (\Exception $e) {
            Log::error('ChargilyPay Webhook Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleOptions(Request $request)
    {
        return response()->json(['message' => 'OK'], 200);
    }

    public function testWebhook(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Webhook test endpoint',
                'data' => $request->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test webhook failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function simulateWebhook(Request $request)
    {
        try {
            $paymentId = $request->input('payment_id');
            
            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required'
                ], 400);
            }
            
            $payment = ChargilyPayment::findOrFail($paymentId);
            $payment->status = $request->input('status', 'paid');
            $payment->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook simulated successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            Log::error('Simulate Webhook Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to simulate webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function chargilyPayInstance()
    {
        return new ChargilyPay(new Credentials([
            'mode' => 'test',
            'public' => env('CHARGILY_PAY_PUBLIC', 'test_pk_********************'),
            'secret' => env('CHARGILY_PAY_SECRET', 'test_sk_********************'),
        ]));
    }
}
