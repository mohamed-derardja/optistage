<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get the list of all users
        try{
                $request_query = $request->query();

        // Get pages elements based on 'per_page' param if provided
        $perPage = filter_var($request_query["per_page"] ?? 15, FILTER_VALIDATE_INT) ?: 15;
        $perPage = max($perPage, 1);

        // Validate sortBy and sortDirection inputs
        $validSortColumns = ['name', 'created_at', 'updated_at'];
        $sortBy = in_array($request_query['sort_by'] ?? 'created_at', $validSortColumns)
            ? ($request_query['sort_by'] ?? 'created_at')
            : 'created_at';
        $sortDirection = in_array(strtolower($request_query['sort_direction'] ?? 'asc'), ['asc', 'desc'])
            ? strtolower($request_query['sort_direction'] ?? 'asc')
            : 'asc';

$data = User::query()->withSum('payment', 'amount')->with('payment');

        if (!empty($request_query['role'])) {
            $roleName = $request_query['role'];
            $data->where('role', 'like', '%' . $roleName . '%');
        }

         // We need to have start and end date as today if not exist
        $startDate = $request_query['start_date'] ?? null;
        $endDate = $request_query['end_date'] ?? now()->toDateString(); // Defaults to today if not provided

        if ($startDate && $endDate) {
            $data->whereRaw("DATE(users.created_at) BETWEEN ? AND ?", [$startDate, $endDate]);
        }

        //filter by whetere he subcribed or not

if (isset($request_query["is_active"])) {
    $is_active = $request_query["is_active"];
    
    if ($is_active === '0' || $is_active === '1') {
        $data->where('is_active', $is_active);
    }
}
                $data->orderBy($sortBy, $sortDirection);
                        $paginatedData = $data->paginate($perPage);

                            // Prepare a structured response
      $response = [
    'success' => true,
    'message' => 'user fetched successfully',
    'data' => $paginatedData->items(), // Paginated data items
    'pagination' => [
        'total_items' => $paginatedData->total(), // Total number of items
        'items_per_page' => $paginatedData->perPage(), // Items per page
        'current_page' => $paginatedData->currentPage(), // Current page number
        'total_pages' => $paginatedData->lastPage(), // Last page number
       
    ]
];


        // Return JSON response
        return response()->json($response, Response::HTTP_OK);



        } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch users',
            'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }



    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            return response()->json([
                'success' => false,
                'message' => 'Method not implemented yet'
            ], 501);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = User::with('payment', 'results')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User retrieved successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'role' => 'sometimes|string|in:admin,user,moderator',
                'is_active' => 'sometimes|boolean'
            ]);
            
            $user->update($data);
            
            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User updated successfully'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
