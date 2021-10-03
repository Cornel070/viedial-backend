<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Telemonitoring;
use Carbon\Carbon;
use App\Models\RemoteMonitoring;

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

    	// $tele_record = Telemonitoring::whereDate('created_at', Carbon::today())
    	// 								->where('user_id', $this->user->id)
    	// 								->first();
    	// if (!$tele_record) {
    	// 	$tele_record = new Telemonitoring;
    	// 	$tele_record->user_id = $this->user->id;
    	// }

    	switch ($reading_type) {
    		case 'blood_pressure':
    			return $this->saveBPRecord($request);
    			break;

    		case 'blood_sugar':
    			return $this->saveBSRecord($request);
    			break;

    		case 'weight':
    			return $this->saveWeightRecord($request);
    			break;

            case 'waist_line':
                return $this->saveWaistlineRecord($request);
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

    private function saveBPRecord(Request $record)
    {
        $validator = $this->validateBPReading($record);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

        $reading = new RemoteMonitoring;
        $reading->type = 'blood_pressure';
        $reading->systolic = $record->systolic;
        $reading->diastolic = $record->diastolic;
        $reading->user_id = $this->user->id;

        $level = 'Slightly High';
        $msg = 'Your blood pressure is slightly high. You need to watch it.';

    	if ($record->systolic <= 120 && $record->diastolic <= 80) {
    		$level = 'Normal';
            $msg = 'Your blood pressure is on a normal level';
    	}elseif (($record->systolic >= 121 && $record->systolic < 141) && ($record->diastolic >= 81 && $record->diastolic < 91)) {
            $level = 'Slightly High';
            $msg = 'Your blood pressure is slightly high. You need to watch it.';

    	}elseif (($record->systolic >= 141 && $record->systolic < 151) && ($record->diastolic >= 91 && $record->diastolic < 101)) {
            $level = 'Really High';
            $msg = 'Your blood pressure is really high. You need to talk to your doctor about starting or changing your treatment.';

        }elseif ($record->systolic >= 151 && $record->diastolic >= 100) {
            $level = 'Dangerously High';
            $msg = 'Your blood pressure is dangerously high. You need urgent treatment to prevent it from increasing';

        }
        // elseif ($record->systolic > 160 && $record->diastolic > 110) {
        //     $level = 'Very High';
        //     $msg = 'Your blood pressure has reached very dangerous levels. This can cause a complication that will lead to significant health problems or death. You need to see a doctor immediately.';

        // }

        $reading->level = $level;
        $reading->save();
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

    public function saveBSRecord(Request $request)
    {
        $validator = $this->validateBSReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

        $reading = new RemoteMonitoring;
        $reading->type = 'blood_sugar';

    	$reading_val = $request->bs_unit === 'mmol/l' ? $request->reading_val : $this->checkConvertUnit($request->reading_val);
        $reading->blood_sugar_val = $reading_val;
        $reading->timing = $request->bs_type;
        $reading->user_id = $this->user->id;

        $level = 'Low';
        $msg = 'Your blood sugar is getting low. You may need to eat or adjust your medication to prevent it from getting lower.';

        if (strtolower($request->bs_type) == 'random' && $reading_val > 3.4 && $reading_val <= 4.2) {
            $level = 'Low';
            $msg = 'Your blood sugar is getting low. You may need to eat or adjust your medication to prevent it from getting lower.';
        }

        if (strtolower($request->bs_type) == 'random' && $reading_val < 3.5) {
            $level = 'Dangerously Low';
            $msg = 'Your blood sugar is getting dangerously low. You will need to take a glass of juice or another sugary drink and reduce your medication to prevent it from getting lower.';
        }

        if (strtolower($request->bs_type) == 'random' && $reading_val > 4.2 && $reading_val <= 11.1) {
            $level = 'Normal';
            $msg = 'Your blood sugar is within normal range. Keep it up';
        }

        if (strtolower($request->bs_type) == 'random' &&  $reading_val > 11.1) {
            $level = 'Very very high';
            $msg = 'Your blood sugar is very very high. You need to start or change your medication and also make changes in your diet and physical activity to keep it within normal limits. We will work with you to bring it to normal levels';
        }
        if ($request->bs_type === 'first_wake' && $reading_val < 4) {
            $level = 'Dangerously Low';
            $msg = 'Your blood sugar is getting dangerously low. You will need to take a glass of juice or another sugary drink and reduce your medication to prevent it from getting lower';
        }
        elseif ($request->bs_type === 'first_wake' && $reading_val > 4 && $reading_val < 5.7) {
            $level = 'Normal';
            $msg = 'Your blood sugar is within normal range. Keep it up';
        }elseif ($request->bs_type === 'first_wake' && $reading_val > 5.6 && $reading_val < 7) {
            $level = 'Slightly High';
            $msg = 'Your blood sugar is slightly high and we will work with you to keep it at normal level.';
        }elseif ($request->bs_type === 'first_wake' && $reading_val > 6.9) {
            $level = 'Very High';
            $msg = 'Your blood sugar is really high and we suggest starting or changing your medications and also make needed changes in your diet and physical activity. We are happy to keep working with you until we get to a normal level.';
        }

        if ($request->bs_type === 'before_meal' && $reading_val >= 4.2 && $reading_val < 6) {
            $level = 'Normal';
            $msg = 'Your blood sugar is within normal range. Keep it up.';
        }elseif ($request->bs_type === 'before_meal' && $reading_val > 5.9 && $reading_val < 6.9) {
            $level = 'Slightly High';
            $msg = 'Your blood sugar is slightly high and we will work with you to keep it at normal level.';
        }elseif ($request->bs_type === 'before_meal' && $reading_val > 7) {
            $level = 'Very High';
            $msg = 'Your blood sugar is really high and we suggest starting or changing your medications and also make needed changes in your diet and physical activity. We are happy to keep working with you until we get to a normal level.';
        }

        if ($request->bs_type === '2h_after_meal' && $reading_val >= 4.2 && $reading_val < 7.9) {
            $level = 'Normal';
            $msg = 'You had no blood sugar spike after the meal. Keep it up.';
        }elseif ($request->bs_type === '2h_after_meal' && $reading_val > 7.8 && $reading_val < 8.4) {
            $level = 'Slightly High';
            $msg = 'There is a slight spike after the meal. We will work with you to avoid this from happening all the time.';
        }elseif ($request->bs_type === '2h_after_meal' && $reading_val >= 8.5) {
            $level = 'Very High';
            $msg = 'There is a high spike after the meal. We will work with you to prevent this from happening again.';
        }

        if ($request->bs_type === 'bedtime' && $reading_val < 4.2) {
            $level = 'Low';
            $msg = 'Your blood sugar is low. Have a high fiber snack before bed.';
        }elseif ($request->bs_type === 'bedtime' && $reading_val >= 4.2 && $reading_val <= 8.5) {
            $level = 'Normal';
            $msg = 'Your blood sugar reading before bed is fine. Have a sound sleep.';
        }elseif ($request->bs_type === 'bedtime' && $reading_val > 8.5) {
            $level = 'High';
            $msg = 'Your blood sugar is high. We will work with you to prevent this from happening often.';
        }

        $reading->level = $level;
        $reading->save();
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

    public function saveWeightRecord(Request $request)
    {
        $validator = $this->validateWeightReading($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

        $reading = new RemoteMonitoring;
        $reading->type = 'weight';

        $weight = $request->w_unit === 'kg' ? $request->reading_val : $this->poundToKg($request->reading_val);
        $reading->user_id = $this->user->id;
        $reading->weight_val = $weight;
        $reading->save();

        if ($this->user->goal()) {
            if ($reading->weight_val <= $this->user->goal()->target_weight) {
                $this->user->goal()->status = 'completed';
                $this->user->goal()->save();
                return response()->json(['res_type'=> 'success', 'message'=>'Well done! You have met your weight goal']);
            }   
        }

        return response()->json(['res_type'=> 'success', 'message'=>'Weight saved.']);
    }

    public function saveWaistlineRecord(Request $request)
    {
        $reading = new RemoteMonitoring;
        $reading->type = 'waist_line';

        $reading->user_id = $this->user->id;
        $reading->waist_line_val = $request->reading_val;
        $reading->save();

        return response()->json(['res_type'=> 'success', 'message'=>'Waistline reading saved.']);
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
        $today_readings = [];

        // Blood Pressure Today 
        $todays_bp = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'blood_pressure')
                                      ->latest()
                                      ->first();
        if (!$todays_bp) {
            $today_readings['blood_pressure_systolic'] = null;
            $today_readings['blood_pressure_diastolic'] = null;
        }else{
            $today_readings['blood_pressure_systolic'] = $todays_bp->systolic;
            $today_readings['blood_pressure_diastolic'] = $todays_bp->diastolic;
        }

        // Blood Sugar Today
        $todays_bs = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'blood_sugar')
                                      ->latest()
                                      ->first();
        if (!$todays_bs) {
            $today_readings['blood_sugar'] = null;
        }else{
            $today_readings['blood_sugar'] = $todays_bs->blood_sugar_val;
        }

        // Weight Today
        $todays_weight = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'weight')
                                      ->latest()
                                      ->first();
        if (!$todays_weight) {
            $today_readings['weight'] = null;
        }else{
            $today_readings['weight'] = $todays_weight->weight_val;
        }

        return response()->json(['res_type'=> 'success', 'todays_readings'=>$today_readings]);
    }

    public function allReadings()
    {
        $readings = [];

        $bp_readings = RemoteMonitoring::where('user_id', $this->user->id)
                                        ->where('type', 'blood_pressure')
                                        ->select('type', 'level', 'systolic', 'diastolic', 'created_at')
                                        ->latest()
                                        ->get();
        $bs_readings = RemoteMonitoring::where('user_id', $this->user->id)
                                        ->where('type', 'blood_sugar')
                                        ->select('type', 'blood_sugar_val', 'timing', 'level', 'created_at')
                                        ->latest()
                                        ->get();
        $weight_readings = RemoteMonitoring::where('user_id', $this->user->id)
                                        ->where('type', 'weight')
                                        ->select('type', 'weight_val', 'created_at')
                                        ->latest()
                                        ->get();
        $waistline_readings = RemoteMonitoring::where('user_id', $this->user->id)
                                        ->where('type', 'waist_line')
                                        ->select('type', 'waist_line_val', 'created_at')
                                        ->latest()
                                        ->get();
        $readings['blood_pressure'] = $bp_readings->filter(function ($value, $key) {
                                        return $value != null;
                                    });
        $readings['blood_sugar'] = $bs_readings;
        $readings['weight'] = $weight_readings;
        $readings['waist_line'] = $waistline_readings;
        if (empty($readings)) {
            return response()->json(['res_type'=> 'not found', 'message'=>'No readings found']);
        }
        return response()->json(['res_type'=> 'success', 'all_readings'=>$readings]);
    }

    public function readingsSummary($period)
    {
        $summary = '';

        // BP
        $bps = $this->getReadings($period, 'blood_pressure');
        if ($bps->count() < 1) {
            $data = [
                'level' => 'Normal',
                'message' => 'No blood pressure readings entered for this '.$period
            ];
            $summary .= 'No blood pressure readings entered for this '.$period.' & ';
        }else{
            $abnormal_bps = 0;
            $normal_bps = 0;
            foreach ($bps as $bp) {
                if ($bp->level != 'Normal') {
                    $abnormal_bps += 1;
                }else{
                    $normal_bps += 1;
                }
            }

            if ($abnormal_bps === 0) {
                if ($bps->count() > 1) {
                    $msg = 'All your '.$bps->count().' blood pressure readings for this '.$period.' are normal & ';
                }else{
                    $msg = 'Your blood pressure reading for this '.$period.' is normal & ';
                }
                $data = [
                    'level' => 'Normal',
                    'message' => $msg
                ];
                $summary .= $msg;
            }elseif($abnormal_bps > 0){
                $percentage = $this->getPercentage($abnormal_bps, $bps->count());
                if ($percentage >= 80) {
                     $msg = '';
                    if ($bps->count() > 1) {
                        $msg = 'Most of your ('.$abnormal_bps.') '.$bps->count().' blood pressure readings for this '.$period.' are higher than normal and you need to step up your treatment and care & ';
                    }else{
                        $msg = 'Your blood pressure reading for this '.$period.' is above normal & ';
                    }
                        $data = [
                    'level' => 'Abnormal',
                    'message' => $msg 
                    ];
                    $summary .= $msg;
                }else{
                    $msg = '';
                    if ($bps->count() > 1) {
                        $msg = $normal_bps.' of your '.$bps->count().' blood pressure readings for this '.$period.' are normal & ';
                    }else{
                        $msg = 'Your blood pressure reading for this '.$period.' is normal & ';
                    }
                    $data = [
                    'level' => 'Okay',
                    'message' => $msg
                    ];
                    $summary .= $msg;
                }
            }
        }

        // BS
        $blood_sugars = $this->getReadings($period, 'blood_sugar');
        
        if ($blood_sugars->count() < 1) {
            $data = [
                'level' => 'Normal',
                'message' => 'No blood sugar readings entered for this '.$period
            ];
            $summary .= 'no blood sugar readings entered for this '.$period.'.';
        }else{
            $abnormal_bs = 0;
            $normal_bs = 0;

            foreach ($blood_sugars as $bs) {
                if ($bs->level == 'Normal') {
                    $normal_bs += 1;
                }else{
                    $abnormal_bs += 1;
                }
            }

            if ($abnormal_bs === 0) {
                if ($blood_sugars->count() > 1) {
                    $msg = 'all your '.$blood_sugars->count().' blood sugar reading(s) for this '.$period.' are normal.';
                }else{
                    $msg = 'your blood sugar reading for this '.$period.' is normal.';
                }
                $data = [
                    'level' => 'Normal',
                    'message' => $msg
                ];
                $summary .= $msg;
            }elseif($abnormal_bs > 0){
                $percentage = $this->getPercentage($abnormal_bs, $blood_sugars->count());
                if ($percentage >= 80) {
                     $msg = '';
                    if ($blood_sugars->count() > 1) {
                        $msg = 'more than 80% ('.$abnormal_bs.') of your '.$blood_sugars->count().' blood sugar readings for this '.$period.' are abonormal.';
                    }else{
                        $msg = 'your blood sugar reading for this '.$period.' is abnormal.';
                    }
                    $data = [
                    'level' => 'Abnormal',
                    'message' => $msg
                    ];
                    $summary .= $msg;
                }else{
                    $msg = '';
                    if ($blood_sugars->count() > 1) {
                        $msg = $normal_bs.' of your '.$blood_sugars->count().' blood sugar readings for this '.$period.' are normal.';
                    }else{
                        $msg = 'your blood sugar reading for this '.$period.' is normal.';
                    }
                    $data = [
                    'level' => 'Okay',
                    'message' => $msg
                    ];
                    $summary .= $msg;
                }
            }
        }

        return response()->json(['res_type'=>'success', 'summary'=>$summary]);
    }

    public function getReadings($period, $type)
    {
        switch ($period) {
            case 'week':
                return RemoteMonitoring::whereBetween('created_at', [
                        Carbon::now()->startOfWeek(), 
                        Carbon::now()->endOfWeek()
                    ])->where('user_id', $this->user->id)->where('type', $type)->get();
            break;

            case 'month':
                return RemoteMonitoring::whereBetween('created_at', [
                        Carbon::now()->startOfMonth(), 
                        Carbon::now()->endOfMonth()
                    ])->where('user_id', $this->user->id)->where('type', $type)->get();
            break;
            
            default:
                return RemoteMonitoring::whereBetween('created_at', [
                        Carbon::now()->startOfWeek(), 
                        Carbon::now()->endOfWeek()
                    ])->where('user_id', $this->user->id)->where('type', $type)->get();
                break;
        }
    }

    public function getPercentage($part, $whole)
    {
        return (int) ceil(($part * 100) / $whole);
    }

    public function emailReadingsSummary($period)
    {
        // create emailing script later
        return response()->json(['res_type'=>'success', 'message'=>'A detailed summary of your readings for this '.$period.' has been sent to your email - '.$this->user->email]);
    }
}
