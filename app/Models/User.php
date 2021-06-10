<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use App\Traits\Encrypt;
use Carbon\Carbon;
use Laravel\Cashier\Billable;
use App\Models\SingleSub;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory, Encrypt, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'dob', 'password', 'gender',
        'phone', 'annon_name', 'status','program',
    ];

    
    protected $hidden = [
        'password'
    ];
    /**
         * The attributes to be encrypted.
         *
         * @var array
    */
    protected $encryptable = [
        'name', 'email', 'password',
        'acct_key','phone'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function foodItems()
    {
        return $this->hasMany(FoodSelection::class);

        // return FoodSelection::whereBetween('created_at', 
        //                 [
        //                     Carbon::now()->startOfWeek(), 
        //                     Carbon::now()->endOfWeek()
        //                 ])->where('user_id', auth()->user()->id)->get();
    }

    public function breakfast()
    {
        return MealTracker::whereDate('created_at', Carbon::today())
        ->where('user_id', auth()->user()->id)
        ->where('type', 'breakfast')
        ->first();
    }

    public function lunch()
    {
        return MealTracker::whereDate('created_at', Carbon::today())
        ->where('user_id', auth()->user()->id)
        ->where('type', 'lunch')
        ->first();
    }

    public function dinner()
    {
        return MealTracker::whereDate('created_at', Carbon::today())
        ->where('user_id', auth()->user()->id)
        ->where('type', 'dinner')
        ->first();
    }

    public function workouts()
    {
        return WorkoutTracker::whereDate('created_at', Carbon::today())
        ->where('user_id', auth()->user()->id)
        ->get();
    }

    public function goal()
    {
        return Goal::where('status', 'in progress')->where('user_id', auth()->user()->id)->first();
    }

    public function activeOnetimeSub()
    {
        return SingleSub::where('user_id', auth()->user()->id)
                         ->whereDate('expires_at', '>=', Carbon::today())
                         ->where('sub_status', 'active')
                         ->first();
    }
}
