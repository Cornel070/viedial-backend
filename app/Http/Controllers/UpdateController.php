<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UpdateController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function updatePhone(Request $request)
    {
    	$validator = $this->validatePhone($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()], 422);
        }

        $this->user->phone = $request->phone;
        $this->user->save();

        return response()->json(['res_type'=>'success', 'message'=>'Phone number updated']);
    }

    public function validatePhone(Request $request)
    {
    	$msg = ['phone.required'=>'Please enter a phone number'];
    	return validator()->make($request->all(), [
            'phone' => 'required'
        ], $msg);
    }
}
