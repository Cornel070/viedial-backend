<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RiskService;
use App\Traits\ApiResponse;

class RiskController extends Controller
{
    private $riskService;

    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    public function intro()
    {
        $response = json_decode($this->riskService->getIntro(), true);
        return $this->successResponse($response);
    }

    public function checkScenario(Request $request)
    {
        $response = json_decode($this->riskService->scenarios($request), true);
        return $this->successResponse($response);
    }

    public function checkRisk(Request $request)
    {
        $response = json_decode($this->riskService->riskLevel($request), true);
        return $this->successResponse($response);
    }
}
