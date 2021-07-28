<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Models\VSubscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function stripeOneTime(Request $request)
    {
        $price = $this->getProgramPrice();

        try {
            $this->user->charge($price, $request->paymentMethodId);

            $this->saveSub('one-time', $request->duration);

            return response()->json(['res_type'=>'success', 'message'=>'Payment successful']);
        } catch (IncompletePayment $e) {
            return response()->json(['res_type'=>'failed_payment', 'message'=>'Unable to complete payment']);
        }
    }

    public function saveSub($type, $duration = 1)
    {
        $sub = new VSubscription;
        $sub->user_id = $this->user->id;
        $sub->sub_status = 'active';
        $sub->type = $type;
        $table->duration = $duration;
        $sub->expires_at = Carbon::now()->addMonths($duration);
        $sub->save();

        //set user sub type to one-time
        $this->user->sub_type = $type;
        $this->user->status   = 'active';
        $this->user->save();

        return true;
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

    public function updatPaidUser(Request $request)
    {
        if ($request->type == 'single') {
            $this->saveSub($request->type, $request->duration);
            return response()->json(['res_type'=>'success', 'message'=>'User account updated']);
        }
        $this->saveSub($request->type);
        return response()->json(['res_type'=>'success', 'message'=>'User account updated']);
    }
}
