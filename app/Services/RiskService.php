<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\RequestService;

class RiskService
{
    use RequestService;
    public $baseUri;
    public $secret;
    public function __construct()
    {
        $this->baseUri = config('services.risk.base_uri');
        $this->secret = config('services.risk.secret');
    }
    public function getIntro()
    {
        return $this->request('GET', '/api/intro');
    }
    public function scenarios(Request $request)
    {
        return $this->request('GET','/api/check-scenarios?diabetes='.$request->diabetes.'&hypertension='.$request->hypertension);
    }

    public function riskLevel(Request $request)
    {
        return $this->request('GET','/api/check-risk?assessment='.$request->assessment.'&score='.$request->score.'&gender='.$request->gender);
    }

    public function storeReading(Request $request)
    {
        return $this->request('POST', '/api/save-reading', $request->input());
    }

    public function storeGoal(Request $request)
    {
        return $this->request('POST', '/api/save-goal', $request->input());
    }

    public function showGoal($id)
    {
        return $this->request('GET', '/api/goal/'.$id);
    }

    public function updateGoal(Request $request, $id)
    {
        return $this->request('POST', '/api/goal/'.$id.'/update', $request->input());
    }

    public function allGoals($user_id)
    {
        return $this->request('GET', '/api/goals/'.$user_id);
    }

    public function deleteGoal($id)
    {
        return $this->request('GET', '/api/goal/'.$id.'/delete');
    }
}