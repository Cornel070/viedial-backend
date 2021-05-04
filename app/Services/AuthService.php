<?php

namespace App\Services;

use App\Traits\RequestService;
use Illuminate\Http\Request;

class AuthService
{
    use RequestService;

    public $baseUri;

    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.authenticate.base_uri');
        $this->secret = config('services.authenticate.secret');
    }

    public function register(Request $request)
    {
        return $this->request('POST', '/api/register', $request->input());
    }

    public function verifyFromEmail($code)
    {
        return $this->request('GET', '/api/verify/'.$code);
    }

    public function login(Request $request)
    {
        return $this->request('POST', '/api/login', $request->input());
    }
}