<?php

namespace App\Http\Controllers;

use App\Models\VSubscription;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }
    
    public function stripeOneTime(Request $request)
    {
        $validator = $this->validateCard($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()]);
        }

        $user = User::find($request->user_id);
        $price = $request->price;

        list($done, $token) = $this->createCardToken($request);

        if (!$done) {
            return response()->json(['res_type'=>'failed', 'message'=>'Invalid card details']);
        }

        try {
            $url = "https://api.stripe.com/v1/charges";

            $data = [
                'card' => $token->id,
                'currency' => 'USD',
                'amount' => $price * 100,
                'description' => "Payment for ".$user->program." plan" ,
            ];
            $crl = curl_init();
             
            $headr = array();
            $headr[] = 'Authorization: Bearer ' . env('STRIPE_SECRET');
            $headr[] = 'Content-type: application/x-www-form-urlencoded';
            curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
             
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
            curl_setopt($crl, CURLOPT_POST, true);
            curl_setopt($crl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
             
            $rest = curl_exec($crl);
            $data = json_decode($rest);

            if ($data->paid) {
                $this->saveSub($request->duration);
                return response()->json(['res_type'=>'success', 'message'=>'Payment success']);
            }
            return response()->json(['res_type'=>'failed', 'message'=>'Payment failed..try again']);
        } catch (\Exception $e) {
            return response()->json(['res_type'=>'failed', 'message'=>'Invalid card details']);
        }
    }

    public function createCardToken(Request $request)
    {
        try {
            $url = "https://api.stripe.com/v1/tokens";

            $data = [
                "card[number]" => $request->card_no,
                "card[exp_month]" => $request->ccExpiryMonth,
                "card[exp_year]" => $request->ccExpiryYear,
                "card[cvc]" => $request->cvvNumber
            ];
            $crl = curl_init();
         
            $headr = array();
            $headr[] = 'Authorization: Bearer ' . env('STRIPE_SECRET');
            $headr[] = 'Content-type: application/x-www-form-urlencoded';
            curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
         
            curl_setopt($crl, CURLOPT_URL, $url);
            curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
         
            curl_setopt($crl, CURLOPT_POST, true);
            curl_setopt($crl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
         
            $rest = curl_exec($crl);
            $data = json_decode($rest);
            return array(true, $data);
        } catch (\Exception $e) {
            return array(false, null);
        }
    }

    public function validateCard(Request $request)
    {
        return validator()->make($request->all(), [
         'card_no' => 'required',
         'ccExpiryMonth' => 'required',
         'ccExpiryYear' => 'required',
         'cvvNumber' => 'required',
         'price'     => 'required|integer',
         'user_id'   => 'required',
         'duration'  => 'required|integer'
         ],[
            'card_no.required'=>'Card number is required',
            'ccExpiryMonth.required'  =>'The expiry month is required',
            'ccExpiryYear.required'  =>'The expiry year is required',
            'cvvNumber.required'  =>'The CV number is required',
        ]);
    }

    public function saveSub($duration = 1)
    {
        $sub = new VSubscription;
        $sub->user_id = $this->user->id;
        $sub->sub_status = 'active';
        $sub->type = $this->user->program;
        $sub->duration = $duration;
        $sub->expires_at = Carbon::now()->addMonths($duration);
        $sub->save();

        //set user sub type to one-time
        $this->user->sub_type = 'on-off';
        $this->user->status   = 'active';
        $this->user->save();

        return true;
    }

    public function getProgramPrice()
    {
        $user = User::find(request()->id);
        
        if ($user->program === 'diabetes') {
            $price = env('DIABETES_PRICE');
        }elseif ($user->program === 'hypertension') {
            $price = env('CVD_PRICE');
        }else{
            $price = env('CO_MORBIDITY_PRICE');
        }

        return (int) $price;
    }

    public function updatPaidUser(Request $request)
    {
        $this->saveSub($request->duration);
        return response()->json(['res_type'=>'success', 'message'=>'User account updated']);
    }

    public function getUserPlanPrice()
    {
        $price = $this->getProgramPrice();
        return response()->json(['res_type'=>'success', 'price'=> $price]);
    }
}
