<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Models\VSubscription;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;

class PaymentController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function stripeOneTime(Request $request)
    {
        $price = $request->price * $request->duration; //price for the users duration
        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            \Stripe\Charge::create ([
                    "amount" => $price * 100,
                    "currency" => "usd",
                    "source" => $request->stripeToken,
                    "description" => "Payment for ".$this->user->program." plan" 
            ]);

            $this->saveSub($request->duration);
            return response()->json(['res_type'=>'success', 'message'=>'Payment successful']);

        } catch(\Stripe\Exception\CardException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Card declined');
        } catch (\Stripe\Exception\RateLimitException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Too many attempts in a short time');
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Something went wrong');
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Something went wrong');
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Connection to stripe failed');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Something went wrong');
        } catch (Exception $e) {
            return response()->json('res_type'=>'failed', 'message'=>'Something went wrong');
        }
    }

    public function saveSub($duration = 1)
    {
        $sub = new VSubscription;
        $sub->user_id = $this->user->id;
        $sub->sub_status = 'active';
        $sub->type = $this->user->program;
        $table->duration = $duration;
        $sub->expires_at = Carbon::now()->addMonths($duration);
        $sub->save();

        //set user sub type to one-time
        $this->user->sub_type = $type;
        $this->user->status   = 'active';
        $this->user->save();

        return true;
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
        $this->saveSub($request->duration);
        return response()->json(['res_type'=>'success', 'message'=>'User account updated']);
    }

    public function getUserPlanPrice()
    {
        $price = getProgramPrice();
        return response()->json(['res_type'=>'success', 'price'=> $price]);
    }
}
