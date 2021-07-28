<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodType;
use App\Models\FoodItem;
use App\Models\Meal;
use App\Models\MealItem;
use App\Models\FoodSelection;
use App\Models\MealTracker;
use Carbon\Carbon;
use App\Models\MealSummary;

class MealController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function createFood(Request $request)
    {
        $validator = $this->validateFood($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()]);
        }

        $food_type = new FoodType;
        $food_type->title = $request->food_type_title;
        $food_type->save();

        $itemsArr = [];
        foreach ($request['names'] as $key => $value) {
            array_push($itemsArr, [
                'food_type_id' => $food_type->id,
                'name'        => $request['names'][$key],
                'calorie_count'=> $request['calorie_counts'][$key],
                'carb_count'=> $request['carb_counts'][$key],
            ]);
        }

        for ($i = 0; $i < count($itemsArr); $i++) { 
            FoodItem::create($itemsArr[$i]);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Food type saved']);
    }

    public function validateFood(Request $request)
    {
        return validator()->make($request->all(),[
            'food_type_title' => 'required|string',
        ],[
            'food_type_title.required'=>'Food type title is required',
            'food_type_title.string'  =>'Food type title must be a valid text',
        ]);
    }

    public function addMeal(Request $request)
    {
        $validator = $this->validateMeal($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()], 422);
        }

        $imageName = time().'.'.$request->meal_img->extension();  
        $request->meal_img->move('assets/img/meals', $imageName);

        $meal = Meal::create( array_merge($request->all(), ['meal_img'=>$imageName]) );

        foreach ($request->items as $item) {
            MealItem::create([
                'meal_id' => $meal->id,
                'food_item_id' => $item
            ]);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Meal added']);
    }

    public function validateMeal(Request $request)
    {
        return validator()->make($request->all(),[
            'title'     => 'required|string',
            'type'      => 'required|string',
            'nutri_info'=>'required|string',
            'prepare_type'=> 'required|string',
            'meal_img'    => 'required|file'
        ],[
            'title.required'=>'Meal title is required',
            'title.string'  =>'Meal title must be a valid text',
            'type.required' => 'Meal type is required',
            'type.string'   => 'Meal type must be a valid text',
            'nutri_info.required'=>'The nutritional information is required',
            'nutri_info.string'=> 'The nutritional information must be a valid text',
            'prepare_type.required'=>'The preparation type is required'
        ]);
    }

    public function getFoodTypes()
    {
        $food_types = FoodType::all();

        if ($food_types->isEmpty()) {
            return response()->json(['res_type'=>'no content', 'message'=>'No food items yet']);
        }

        $foodArr = [];

        foreach ($food_types as $food) {
            $data = [
                'title'     => $food->title,
                'food_items'=> $food->items
            ];

            array_push($foodArr, $data);
        }

        return response()->json(['res_type'=>'success', 'food_types'=>$foodArr]);
    }

    public function createFoodItemSelection(Request $request)
    {
        foreach ($request->food_items as $item) {
            FoodSelection::create([
                'user_id'     => $this->user->id,
                'food_item_id'=> $item
            ]);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Food items selection saved']);
    }

    public function suggestMeals()
    {
        $suggestedMeals = [];

        $breakfast = $this->makeSuggestion('breakfast');
        $lunch = $this->makeSuggestion('lunch');
        $dinner = $this->makeSuggestion('dinner');

        array_push($suggestedMeals, $breakfast);
        array_push($suggestedMeals, $lunch);
        array_push($suggestedMeals, $dinner);

        return response()->json(['res_type'=>'success', 'suggested_meals'=>$suggestedMeals]);
    }

    private function makeSuggestion($meal_type)
    {
        $user_food_items = $this->user->foodItems;
        $meals = Meal::where('type', $meal_type)->get();
        $meal_data = [];

        foreach ($meals as $meal) {
            /*
                gather all the items in the meal
            */
            $food_items = [];
            foreach ($meal->items as $item) {
                array_push($food_items, $item->id);
            }

            /*
                Check if the user selected food items are available in the meal
                and keep track
            */
            $i = 0.0;
            foreach ($user_food_items as $user_item) {
                if (in_array($user_item->food_item_id, $food_items)) {
                    $i++;
                }
            }


            /*
                get percentage of items to be found in a meal
                10%
            */
            $percentage = ceil(( count($food_items) * 10) / 100);


            /*
                if atleast 50% of the meal items are also what the user selected
            */
            if ($i >= $percentage) {
                // push the meal into the suggested meal array
                $eaten = false;
                $tracked = MealTracker::where('meal_id', $meal->id)
                        ->where('user_id', $this->user->id)
                        ->whereDate('created_at', Carbon::today())
                        ->first();
                if ($tracked) {
                    $eaten = true;
                }

                array_push($meal_data, array_merge( $meal->withoutRelations()->toArray(), ['eaten'=>$eaten]));
            }
        }

        if (count($meal_data) < 1) {
            return collect(["meal_type"=>$meal_type, "meal"=>null]);
        }

        /*
            randomize selection
        */
        $lower = 0;
        $upper = count($meal_data) - 1;
        $x = mt_rand($lower, $upper); //random meal index to select from
        $meal = $meal_data[$x];

        return collect(["meal_type"=>$meal_type, "meal"=>$meal]);
    }

    public function markMealAsEaten($id)
    {
        $meal = Meal::find($id);
        if (!$meal) {
           return response()->json(['res_type'=>'not found', 'message'=>'Meal does not exist'],404);
        }

        $tracker = new MealTracker;
        $tracker->user_id = $this->user->id;
        $tracker->meal_id = $id;
        $tracker->type = $meal->type;
        $tracker->save();

        $summary = MealSummary::whereDate('created_at', Carbon::today())->first();

        if (!$summary) {
            $summary = new MealSummary;
            $summary->user_id = $this->user->id;
            $summary->meal_id = $meal->id;
        }

        if ($meal->type == 'breakfast') {
            $summary->breakfast = 'yes';
        }elseif ($meal->type == 'lunch') {
            $summary->lunch = 'yes';
        }elseif ($meal->type == 'dinner') {
            $summary->dinner = 'yes';
        }

        $summary->save();

        return response()->json(['res_type'=>'success', 'message'=>'Eaten']);
    }

    public function markMealAsUneaten($id)
    {
        $meal = Meal::find($id);
        if (!$meal) {
           return response()->json(['res_type'=>'not found', 'message'=>'Meal does not exist'],404);
        }

        $tracked = MealTracker::where('meal_id', $meal->id)
        ->where('user_id', $this->user->id)
        ->whereDate('created_at', Carbon::today())
        ->first();

        $summary = MealSummary::whereDate('created_at', Carbon::today())->first();

        if (!$summary) {
            $summary = new MealSummary;
            $summary->user_id = $this->user->id;
            $summary->meal_id = $meal->id;
        }

        if ($meal->type == 'breakfast') {
            $summary->breakfast = null;
        }elseif ($meal->type == 'lunch') {
            $summary->lunch = null;
        }elseif ($meal->type == 'dinner') {
            $summary->dinner = null;
        }

        $summary->save();

        if (!$tracked) {
            return response()->json(['res_type'=>'not tracked', 'message'=>'Meal not previously eaten']);
        }

        $tracked->delete();

        return response()->json(['res_type'=>'success', 'message'=>'Uneaten']);
    }

    public function geMealReport()
    {
        $summary = MealSummary::where('user_id', $this->user->id)->get();
        return response()->json(['res_type'=>'success', 'summary'=>$summary]);
    }
}
