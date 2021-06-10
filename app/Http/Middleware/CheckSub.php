<?php

namespace App\Http\Middleware;

use Closure;

class CheckSub
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if ($user->sub_type == 'subscription') {
            if ($user && !$user->subscribed($user->program)) {
                // This user is not a paying customer...
                return $this->unsubscribed();
            }
        }else{

            $sub = $this->user->activeOnetimeSub();

            if ($user && !$sub) {
                return $this->unsubscribed();
            }

            if ($sub->expires_at->lessThan( Carbon::today() )) {
                $sub->sub_status = 'expired';
                $sub->save();

                return $this->unsubscribed();
            }
        }

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }

    private function unsubscribed()
    {
        return response()->json(['res_type'=>'unsubscribed', 'message'=>'No active subscription']);
    }
}
