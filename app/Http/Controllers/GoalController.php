<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RiskService;
use App\Traits\ApiResponse;

class GoalController extends Controller
{
    private $riskService;

    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    public function saveGoal(Request $request)
    {
        $response = json_decode($this->riskService->storeGoal($request), true);
        if ($response['res_type'] === 'validator_error') {
            return $this->errorResponse($response, 422);
        }
        return $this->successResponse($response);
    }

    public function showGoal($id)
    {
        $response = json_decode($this->riskService->showGoal($id), true);
        if ($response['res_type'] === 'error') {
            return $this->errorResponse($response, 404);
        }
        return $this->successResponse($response);
    }

    public function updateGoal(Request $request, $id)
    {
        $response = json_decode($this->riskService->updateGoal($request, $id), true);
        if ($response['res_type'] === 'error') {
            return $this->errorResponse($response, 404);
        }

        if ($response['res_type'] === 'validator_error') {
            return $this->errorResponse($response, 422);
        }

        return $this->successResponse($response);
    }

    public function allGoals($user_id)
    {
        $response = json_decode($this->riskService->allGoals($user_id), true);
        if ($response['res_type'] === 'error') {
            return $this->errorResponse($response, 404);
        }
        return $this->successResponse($response);
    }

    public function deleteGoal($id)
    {
        $response = json_decode($this->riskService->deleteGoal($id), true);
        if ($response['res_type'] === 'error') {
            return $this->errorResponse($response, 404);
        }
        return $this->successResponse($response);   
    }
}
