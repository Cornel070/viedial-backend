<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RiskService;
use App\Traits\ApiResponse;

class TeleMonitoringController extends Controller
{
    private $riskService;

    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    public function saveReading(Request $request)
    {
        $response = json_decode($this->riskService->storeReading($request), true);
        if ($response['res_type'] === 'error') {
            return $this->errorResponse($response, 404);
        }
        if ($response['res_type'] === 'validator_error') {
            return $this->errorResponse($response, 422);
        }
        return $this->successResponse($response);
    }
}
