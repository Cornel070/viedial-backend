<?php 

namespace App\Traits;	


trait ApiResponse
{
    public function successResponse($data, $statusCode = 200)
    {
        return response($data, $statusCode)->header('Content-Type', 'application/json');
    }

    public function errorResponse($errorMessage, $statusCode)
    {
        return response()->json($errorMessage, $statusCode);
    }
    
    public function errorMessage($errorMessage, $statusCode)
    {
        return response($errorMessage, $statusCode)->header('Content-Type', 'application/json');
    }
}