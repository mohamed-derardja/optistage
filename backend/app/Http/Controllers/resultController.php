<?php

namespace App\Http\Controllers;

use App\Models\ChargilyPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function processPdf(Request $request)
    {
        try {
            // 1. Validate the file (PDF or TXT)
            $request->validate([
                'file' => 'required|file|mimes:pdf,txt|max:5120', // 5MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // 2. Get the file
            $uploadedFile = $request->file('file');
            
            $agentConfig = config('services.agents', []);
            $agentBaseUrl = rtrim($agentConfig['base_url'] ?? 'http://host.docker.internal:5000', '/');
            $agentEndpoint = ltrim($agentConfig['endpoint'] ?? '/process-pdf', '/');
            $agentTimeout = (int) ($agentConfig['timeout'] ?? 120);
            $agentUrl = $agentBaseUrl . '/' . $agentEndpoint;

            // 3. Determine file type and content type
            $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
            $contentType = $fileExtension === 'txt' ? 'text/plain' : 'application/pdf';
            
            // 4. Make the API request
            $response = Http::asMultipart()
                ->attach(
                    'file', 
                    file_get_contents($uploadedFile->getRealPath()),
                    $uploadedFile->getClientOriginalName(),
                    ['Content-Type' => $contentType]
                )
                ->timeout($agentTimeout)
                ->post($agentUrl);

            $responseData = $response->json();

            // 4. Handle API response
            if ($response->status() === 429 && isset($responseData['code']) && $responseData['code'] === 'LLM_QUOTA_EXCEEDED') {
                Log::warning('PDF Processing throttled::5000 LLM quota exceeded.');
                return response()->json([
                    'success' => false,
                    'message' => $responseData['message'] ?? 'AI quota exceeded. Please try again shortly.',
                    'code' => 'LLM_QUOTA_EXCEEDED',
                    'retry_after' => $responseData['retry_after'] ?? null,
                ], 429);
            }

            if ($response->status() === 400) {
                throw new \Exception("API rejected the file: " . $response->body());
            }

            if (!$response->successful()) {
                throw new \Exception("API request failed with status: " . $response->status());
            }
            
            // 5. Store results in database (only if API was successful)
            if ($responseData['success'] && isset($responseData['internships'])) {
                $user = Auth::user(); // Get authenticated user
                
                foreach ($responseData['internships'] as $internship) {
                    Result::create([
                        'user_id' => $user->id,
                        'company' => $internship['company'],
                        'position' => $internship['position'],
                        'url' => $internship['url']
                    ]);
                }
            }

            // 6. Return successful response
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Processing Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function getHistory()
{
    try {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        $history = Result::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $history->items(),
            'pagination' => [
                'total' => $history->total(),
                'per_page' => $history->perPage(),
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
            'message' => 'History retrieved successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve history',
            'error' => $e->getMessage()
        ], 500);
    }
}

// In your Laravel Controller (e.g., StatsController.php)

public function getStat()
{
    try {
        // Total number of users
        $numberOfUsers = User::count();
        
        // Total payment amount (sum of all successful payments)
        $paymentTotal = ChargilyPayment::where('status', 'paid')->sum('amount') ?? 0;
        
        // Total number of tests (assuming each test has 3 results)
        $numberOfTests = ceil(Result::count() / 3);
        
        // Total number of result links
        $numberOfLinks = Result::count();
        
        // Monthly payment data for the chart (SQLite compatible)
        $monthlyPayments = ChargilyPayment::where('status', 'paid')
            ->selectRaw('SUM(CAST(amount AS REAL)) as total, strftime("%m", created_at) as month')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Format monthly data for chart
        $paymentChartData = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        foreach ($months as $index => $month) {
            $monthNum = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $payment = $monthlyPayments->firstWhere('month', $monthNum);
            $paymentChartData[$month] = $payment ? (float)$payment->total : 0;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'totalUsers' => $numberOfUsers,
                'totalPayments' => (float)$paymentTotal,
                'totalTests' => $numberOfTests,
                'totalLinks' => $numberOfLinks,
                'paymentChartData' => $paymentChartData
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve statistics',
            'error' => $e->getMessage()
        ], 500);
    }
}
}