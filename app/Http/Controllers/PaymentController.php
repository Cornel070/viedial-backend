<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Exceptions\IncompletePayment;
use Illuminate\Http\Request;
use App\Models\SingleSub;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function stripeOnetime(Request $request)
    {
        $price = $this->getProgramPrice();

        try {
            $this->user->charge($price, $request->paymentMethodId);

            $single_sub = new SingleSub;
            $single_sub->user_id = $this->user->id;
            $single_sub->sub_status = 'active';
            $single_sub->expires_at = Carbon::now()->addMonth(1);
            $single_sub->save();

            //set user sub type to one-time
            $this->user->sub_type = 'one-time';
            $this->user->save();

            return response()->json(['res_type'=>'success', 'message'=>'Payment successfull']);
        } catch (IncompletePayment $e) {
            return response()->json(['res_type'=>'failed_payment', 'message'=>'Unable to complete payment']);
        }
    }

    public function stripeSetupIntent()
    {
        $intent = $this->user->createSetupIntent();

        return response()->json(['res_type'=>'success', 'intent'=>$intent]);
    }

    public function stripeSubscribe(Request $request)
    {
        try {
            $price_monthly = $this->getProgramPrice();
            $this->user->newSubscription($this->user->program, $price_monthly)
                       ->create($request->paymentMethodId);

            //set user sub type to one-time
            $this->user->sub_type = 'subscription';
            $this->user->save();

            return response()->json(['res_type'=>'success', 'message'=>'Subscribed']);
        } catch (IncompletePayment $e) {
            return response()->json(['res_type'=>'failed_payment', 'message'=>'Unable to complete payment']);
        }
    }

    public function getProgramPrice()
    {
        if ($this->user->program === 'diabetes') {
            $price = env('DIABETES_PRICE');
        }elseif ($this->user->program === 'hypertension') {
            $price = env('CVD_PRICE');
        }else{
            $price = env('CO_MORBIDITY_PRICE');
        }

        return $price;
    }
}
