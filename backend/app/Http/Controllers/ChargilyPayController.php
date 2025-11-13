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

            // Create payment record
            $payment = ChargilyPayment::create([
                'user_id'  => $userId,
                'status'   => 'pending',
                'currency' => $currency,
                'amount'   => $amount,
            ]);

            // Create ChargilyPay instance
            $chargilyPay = $this->chargilyPayInstance();
            
            // Create checkout
            $checkout = $chargilyPay->checkouts()->create([
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

            // Get checkout URL
            $checkoutUrl = $checkout->getUrl();
            
            return response()->json([
                "success" => true,
                "url" => $checkoutUrl
            ]);
        } catch (\Exception $e) {
            Log::error('ChargilyPay Redirect Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment redirect',
                'error' => config('app.debug') ? $e->getMessage() : 'Payment initialization failed. Please try again.'
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

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment record not found'
                ], 404);
            }

            // Update payment status based on checkout status
            if ($checkout->getStatus() === 'paid') {
                $payment->status = 'paid';
                $payment->save();
                
                // Update user access only when payment is successful
                $user = User::findOrFail($payment->user_id);
                $user->is_active = true;
                $user->access_expires_at = Carbon::now()->addDays(30);
                $user->save();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed successfully.',
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                ]);
            } else {
                $payment->status = 'failed';
                $payment->save();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed.',
                    'status' => $payment->status,
                ], 400);
            }
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
        try {
            $publicKey = env('CHARGILY_PAY_PUBLIC');
            $secretKey = env('CHARGILY_PAY_SECRET');
            $useMock = env('CHARGILY_PAY_USE_MOCK', false);
            
            // Convert string 'true'/'false' to boolean
            $useMock = filter_var($useMock, FILTER_VALIDATE_BOOLEAN);
            
            // Use mock mode if explicitly enabled OR if credentials are missing
            if ($useMock || (!$publicKey || !$secretKey)) {
                if ($useMock) {
                    Log::info('ChargilyPay mock mode enabled for testing.');
                } else {
                    Log::warning('ChargilyPay credentials not configured. Using mock mode for development.');
                }
                
                // Return a mock instance for development/testing
                return new class {
                    public function checkouts() {
                        return new class {
                            public function create($data) {
                                return new class($data) {
                                    private $data;
                                    public function __construct($data) {
                                        $this->data = $data;
                                    }
                                    public function getUrl() {
                                        // Return a mock payment URL that will simulate successful payment
                                        $baseUrl = env('APP_URL', 'http://127.0.0.1:8000');
                                        $paymentId = $this->data['metadata']['payment_id'] ?? 1;
                                        return $baseUrl . '/api/chargilypay/back?checkout_id=mock_checkout_' . $paymentId . '_' . time();
                                    }
                                    public function getMetadata() {
                                        return $this->data['metadata'] ?? [];
                                    }
                                };
                            }
                            public function get($checkoutId) {
                                // Mock checkout for testing - always returns paid status
                                return new class($checkoutId) {
                                    private $checkoutId;
                                    public function __construct($checkoutId) {
                                        $this->checkoutId = $checkoutId;
                                    }
                                    public function getStatus() {
                                        // Extract payment_id from checkout_id if it's a mock checkout
                                        if (strpos($this->checkoutId, 'mock_checkout_') === 0) {
                                            return 'paid'; // Simulate successful payment
                                        }
                                        return 'paid';
                                    }
                                    public function getMetadata() {
                                        // Extract payment_id from mock checkout_id
                                        if (preg_match('/mock_checkout_(\d+)_/', $this->checkoutId, $matches)) {
                                            return ['payment_id' => (int)$matches[1]];
                                        }
                                        return ['payment_id' => 1];
                                    }
                                };
                            }
                        };
                    }
                    public function webhook() {
                        return new class {
                            public function get() {
                                return null;
                            }
                        };
                    }
                };
            }
            
            // Use real ChargilyPay API
            return new ChargilyPay(new Credentials([
                'mode' => env('CHARGILY_PAY_MODE', 'test'),
                'public' => $publicKey,
                'secret' => $secretKey,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to create ChargilyPay instance: ' . $e->getMessage());
            throw $e;
        }
    }
}
