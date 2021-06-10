<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Telemonitoring;
use Carbon\Carbon;

class TeleMonitoringController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function saveReading(Request $request)
    {
        $validator = $this->validateReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

    	$reading_type = $request->reading_type;

    	$tele_record = Telemonitoring::whereDate('created_at', Carbon::today())
    									->where('user_id', $this->user->id)
    									->first();
    	if (!$tele_record) {
    		$tele_record = new Telemonitoring;
    		$tele_record->user_id = $this->user->id;
    	}

    	switch ($reading_type) {
    		case 'blood_pressure':
    			return $this->saveBPRecord($request, $tele_record);
    			break;

    		case 'blood_sugar':
    			return $this->saveBSRecord($request, $tele_record);
    			break;

    		case 'weight':
    			return $this->saveWeightRecord($request, $tele_record);
    			break;
    		
    		default:
    			return response()->json(['res_type'=> 'error', 'message'=>'Reading type not found.']);
    			break;
    	}
    }

    public function validateReading(Request $request)
    {
        $msg = [
            'reading_type.required' => 'The Reading Type is required',
            'reading_type.string'   => 'The Reading Type must be  a string',
        ];
        return validator()->make($request->all(), [
            'reading_type' => 'required|string',
        ], $msg);
    }

    private function saveBPRecord(Request $request, Telemonitoring $record)
    {
        $validator = $this->validateBPReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

    	$record->blood_pressure_systolic = $request->systolic;
    	$record->blood_pressure_diastolic= $request->diastolic;
    	$record->save();

    	if ($record->blood_pressure_systolic <= 120 && $record->blood_pressure_diastolic <= 80) {
    		$level = 'Normal';
            $msg = 'Your blood pressure is on a normal levels.';

    	}elseif ($record->blood_pressure_systolic >= 120 && $record->blood_pressure_systolic < 141 && $record->blood_pressure_diastolic >= 80 && $record->blood_pressure_diastolic < 91) {
            $level = 'Slightly High';
            $msg = 'Your blood pressure is slightly high. You need to watch it.';

    	}elseif ($record->blood_pressure_systolic >= 140 && $record->blood_pressure_systolic < 151 && $record->blood_pressure_diastolic >= 90 && $record->blood_pressure_diastolic < 101) {
            $level = 'Really High';
            $msg = 'Your blood pressure is really high. You need to talk to your doctor about starting or changing your treatment.';

        }elseif ($record->blood_pressure_systolic >= 150 && $record->blood_pressure_systolic < 161 && $record->blood_pressure_diastolic >= 99 && $record->blood_pressure_diastolic < 111) {
            $level = 'Dangerously High';
            $msg = 'Your blood pressure is dangerously high. You need urgent treatment to prevent it from increasing.';

        }elseif ($record->blood_pressure_systolic >= 159 && $record->blood_pressure_diastolic >= 110) {
            $level = 'Very High';
            $msg = 'Your blood pressure has reached very dangerous levels. This can cause a complication that will lead to significant health problems or death. You need to see a doctor immediately.';

        }

    	return response()->json(['res_type'=> 'success', 'level'=>$level, 'message'=>$msg]);
    }

    public function validateBPReading(Request $request)
    {
        $msg = [
            'systolic.required'     => 'The Systolic Reading is required',
            'systolic.integer'      => 'The Systolic Reading must be a valid number',
            'diastolic.required'    => 'The Diastolic Reading is required',
            'diastolic.integer'     => 'The Diastolic Reading must be a valid number',
        ];
        return validator()->make($request->all(), [
            'systolic'  => 'required|integer',
            'diastolic' => 'required|integer'
        ], $msg);
    }

    public function saveBSRecord(Request $request, Telemonitoring $record)
    {
        $validator = $this->validateBSReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

    	$reading_val = $request->bs_unit === 'mmol/l' ? $request->reading_val : $this->checkConvertUnit($request->reading_val);
        $record->blood_sugar = $reading_val;
        $record->save();

        if ($reading_val < 4.2 && $reading_val > 3.4) {
            $level = 'Low';
            $msg = 'Your blood sugar is getting low. You may need to eat or adjust your medication to prevent it from getting lower.';
        }

        if ($reading_val < 3.5) {
            $level = 'Dangerously Low';
            $msg = 'Your blood sugar is getting dangerously low. You will need to take a glass of juice or another sugary drink and adjust your medication to prevent it from getting lower.';
        }

        if ($reading_val > 11.1) {
            $level = 'very very high';
            $msg = 'Your blood sugar is very very high. You need to start or change your medication and also make changes in your diet and physical activity to keep it within normal limits. We will work with you to bring it to normal levels';
        }

        if ($request->bs_type === 'first_wake' && $reading_val > 4.1 && $reading_val < 5.7) {
            $level = 'Normal';
            $msg = 'Your blood sugar is within normal range. Keep it up';
        }elseif ($request->bs_type === 'first_wake' && $reading_val > 5.6 && $reading_val < 7) {
            $level = 'Slightly High';
            $msg = 'Your blood sugar is slightly high and we will work with you to keep it at normal level.';
        }elseif ($request->bs_type === 'first_wake' && $reading_val > 6.9) {
            $level = 'Very High';
            $msg = 'Your blood sugar is really high and we suggest starting or changing your medications and also make needed changes in your diet and physical activity. We are happy to keep working with you until we get to a normal level.';
        }

        if ($request->bs_type === 'before_meal' && $reading_val > 4.1 && $reading_val < 6) {
            $level = 'Normal';
            $msg = 'Your blood sugar is within normal range. Keep it up.';
        }elseif ($request->bs_type === 'before_meal' && $reading_val > 5.9 && $reading_val < 6.9) {
            $level = 'Slightly High';
            $msg = 'Your blood sugar is slightly high and we will work with you to keep it at normal level.';
        }elseif ($request->bs_type === 'before_meal' && $reading_val > 7) {
            $level = 'Very High';
            $msg = 'Your blood sugar is really high and we suggest starting or changing your medications and also make needed changes in your diet and physical activity. We are happy to keep working with you until we get to a normal level.';
        }

        if ($request->bs_type === '2h_after_meal' && $reading_val > 4.1 && $reading_val < 7.9) {
            $level = 'Normal';
            $msg = 'You had no blood sugar spike after the meal. Keep it up.';
        }elseif ($request->bs_type === '2h_after_meal' && $reading_val > 7.8 && $reading_val < 8.4) {
            $level = 'Slightly High';
            $msg = 'There is a slight spike after the meal. We will work with you to avoid this from happening all the time.';
        }elseif ($request->bs_type === '2h_after_meal' && $reading_val > 8.5) {
            $level = 'Very High';
            $msg = 'There is a high spike after the meal. We will work with you to prevent this from happening again.';
        }

        if ($request->bs_type === 'bedtime' && $reading_val > 4.1 && $reading_val < 8.6) {
            $level = 'Normal';
            $msg = 'Your blood sugar reading before bed is fine. Have a sound sleep.';
        }elseif ($request->bs_type === 'bedtime' && $reading_val < 4.2) {
            $level = 'Low';
            $msg = 'Your blood sugar is low. Have a high fiber snack before bed.';
        }elseif ($request->bs_type === 'bedtime' && $reading_val > 8.5) {
            $level = 'High';
            $msg = 'Your blood sugar is high. We will work with you to prevent this from happening often.';
        }

        return response()->json(['res_type'=> 'success', 'level'=>$level, 'message'=>$msg]);
    }

     public function validateBSReading(Request $request)
    {
        $msg = [
            'bs_unit.required'      => 'The Blood Sugar unit is required',
            'reading_val.required'  => 'The reading value is required',
            'reading_val.integer'   => 'The reading value must be a valid number',
            'bs_type.required'      => 'The reading scenario is required: First Wake, Before Meal, Before Bedtime, etc.',
        ];
        return validator()->make($request->all(), [
            'bs_unit'   => 'required',
            'bs_type'   => 'required',
            'reading_val' => 'required|between:0,99.99'
        ], $msg);
    }

    public function checkConvertUnit($val)
    {
        return (int) $val / 18; //convert mg/dl to mmol/l
    }

    public function saveWeightRecord(Request $request, Telemonitoring $record)
    {
        $validator = $this->validateWeightReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

    	$weight = $request->w_unit === 'kg' ? $request->reading_val : $this->poundToKg($request->reading_val);
        $record->weight = $weight;
        $record->save();

        if ($record->weight <= $this->user->goal()->target_weight) {
            $this->user->goal()->status = 'completed';
            return response()->json(['res_type'=> 'success', 'message'=>'Well done! You have met your weight goal']);
        }

        return response()->json(['res_type'=> 'success', 'message'=>'Weight saved.']);
    }

    public function validateWeightReading(Request $request)
    {
        $msg = [
            'w_unit.required'       => 'The Weight unit is required',
            'reading_val.required'  => 'The reading value is required',
            'reading_val.integer'   => 'The reading value must be a valid number',
        ];
        return validator()->make($request->all(), [
            'w_unit'   => 'required',
            'reading_val' => 'required|between:0,99.99'
        ], $msg);
    }

    public function poundToKg($val)
    {
        return (int) $val * 0.453592;
    }

    public function todayReadings(Request $request)
    {
        $todays = Telemonitoring::whereDate('created_at', Carbon::today())->where('user_id', $this->user->id)->get();

        if ($todays->isEmpty()) {
            return response()->json(['res_type'=> 'not found', 'message'=>'No readings for today'], 404);
        }
        return response()->json(['res_type'=> 'success', 'todays_readings'=>$todays]);
    }

    public function allReadings()
    {
        $readings = Telemonitoring::where('user_id', $this->user->id)->latest()->get();

        if ($readings->isEmpty()) {
            return response()->json(['res_type'=> 'not found', 'message'=>'No readings found'], 404);
        }
        return response()->json(['res_type'=> 'success', 'all_readings'=>$readings]);
    }
}
