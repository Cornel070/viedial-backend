<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerifyEmail;
use App\Mail\Welcome;
use App\Models\VerifyCode;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        //Comment out until testing is done
        // if($this->emailIsNotUnique($request->email)){
        //     $notUnique['email_not_unique'] = 'This email has already been taken'; 
        //     return response()->json(['res_type'=> 'validator_error', 'errors'=>$notUnique], 422);
        // }

        $annon_name = $this->generateAnnon();

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'dob'       => Carbon::parse($request->dob),
            'phone'     => $request->phone,
            'gender'    => $request->gender,
            'annon_name'=> $annon_name,
            'program'   => $request->program,
        ];

        $user = User::create($data);

        $this->verifyEmail($user);

        return response()->json(['res_type'=> 'success', 'name'=>$user->name], 200);
    }

    private function emailIsNotUnique($email)
    {
        foreach(User::all() as $user)
        {
            if($user->email === $email)
            {
                return true;
            }
        }

        return false;
    }

    public function validator(Request $request)
    {
        $msg = [
            'name.required' => 'Name is required',
            'name.string'   => 'Name must be a string',
            'email.required' => 'Email is required',
            'email.unique' => 'This email is already taken',
            'email.email'   => 'Please enter a valid email address',
            'password.required' => 'Please enter a password for your account',
            'password.confirmed' => 'Password does not match confirmed',
            'program.required' => 'Please include client program',
            'gender.required' => 'Please provide client gender',
            'password.min'=>'Your password must be at least 6 characters long'
        ];
        return validator()->make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|unique:users|email',
            'dob'   => 'required',
            'password' => 'required|confirmed|min:6',
            'program'=> 'required|string',
            'gender' => 'required|string',
        ],$msg);
    }

    private function generateAnnon()
    {
        return 'Annon_'.Str::random(3);
    }

    private function generateAcctKey()
    {
        return Str::random(4);
    }

    private function verifyEmail(User $user)
    {
        $code = $this->generateVerifyCode($user);
        
        Mail::to($user->email)->send(new VerifyEmail($code, $user->name));
    }

    private function generateVerifyCode(User $user)
    {
        $code = mt_rand(1000,9999);

        $verifier = new VerifyCode;
        $verifier->verification_code = $code;
        $verifier->user_id = $user->id;
        $verifier->save();

        return $code;
    }

    public function verifyFromEmail($code)
    {   
        $verified = VerifyCode::where('verification_code', $code)->first();

        if ($verified) {
            $user = User::find($verified->user_id);
            $acct_key = $this->generateAcctKey();
            $user->email_verified_at = Carbon::now();
            $user->acct_key = $acct_key;
            $user->save();

            $token = auth()->login($user);
            $this->welcomeToViedial($user);

            return response()->json(['res_type'=> 'success', 'user'=>$user, 'token'=>$token], 200);
        }

        return response()->json(['res_type'=> 'error', 'message'=>'Invalid verification code'], 400);
    }

    private function welcomeToViedial(User $user)
    {
        return Mail::to($user->email)->send(new Welcome($user));
    }

    public function login(Request $request)
    {
        $validator = $this->validateLogin($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=>'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        $credentials = request(['acct_key', 'password']);

        foreach(User::all() as $user){
            if($user->acct_key === $credentials['acct_key']){
                $token = auth()->login($user);
                return response()->json(['res_type'=>'success', 'user'=>$user, 'token'=>$token]);
            }
        } 
        return response()->json(['res_type' => 'error', 'message'=>'Invalid credentials'], 401);
    }

    public function validateLogin(Request $request)
    {
        $rules = ['acct_key'=>'required', 'password'=>'required'];
        $msg = ['acct_key.required'=>'Your Account Key is required', 'password.required'=>'Your Password is required'];
        return validator()->make($request->all(), $rules, $msg);
    }

    public function checkAcctKey($key)
    {
        foreach (User::all() as $user) {
            if ($user->acct_key === $key) {
                return response()->json(['res_type'=>'success', 'found'=>true, 'user_id'=>$user->id]);
            }
        }

       return response()->json(['res_type'=>'not found', 'message'=>'This account key does not match any account.'], 404);
    }

    public function updatePassword(Request $request)
    {
        $validator = $this->validatePasswordUpdate($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=>'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        foreach (User::all() as $user) {
            if ($user->id === $request->user_id) {
                $user->password = $request->password;
                $user->save();
                return response()->json(['res_type'=>'success', 'message'=>'Password successfully updated']);
            }
        }

        return response()->json(['res_type'=>'not found', 'message'=>'User not found'],404);
    }

    public function validatePasswordUpdate(Request $request)
    {
        $rules = ['password'=>'required|string|confirmed|min:6'];
        $msg = [
                'password.required'=>'Your new password is required', 
                'password.confirmed'=>'Your password and confirmed password must match',
                'password.string'=>'Your new password must be a valid text',
                'password.min'=>'Your new password must be at least 6 characters long',
            ];
        return validator()->make($request->all(), $rules, $msg);
    }

    public function checkToken()
    {
        $not_expired = JWTAuth::parseToken()->check();

        if ($not_expired) {
            return response()->json(['res_type'=>'success', 'expired'=>false]);
        }

        return response()->json(['res_type'=>'error', 'expired'=>true]);
    }
}
