<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Option;

/**
 * 
 */
class RiskController extends Controller
{
	public function addQuestion(Request $request)
	{
		$validator = $this->validator($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()], 422);
        }

        $question = new Question;
        $question->question_text = $request->question_text;
        $question->question_type = $request->question_type;
        $question->save();

        $optionsArr = [];
        foreach ($request['option'] as $key => $value) {
        	array_push($optionsArr, [
        		'question_id' => $question->id,
                'option_text' => $request['option'][$key],
                'male_point'  => $request['male_point'][$key],
                'female_point'=> $request['female_point'][$key],
        	]);
        }

        for ($i = 0; $i < count($optionsArr); $i++) { 
        	Option::create($optionsArr[$i]);
        }

        return response()->json(['success'=>true], 200);
	}

	public function validator(Request $request)
    {
        return validator()->make($request->all(), [
            'question_text' => 'required|string',
            'question_type' => 'required|string'
        ]);
    }

    public function introQuestions()
    {
        $introQs = Question::where('question_type', 'intro')->get();
        $questions = [];

        foreach ($introQs as $qst) {
            $q = [
                'question' => $qst->question_text,
                'options'  => $qst->options
            ];

            array_push($q, $questions); 
        }

        return response()->json(['res_type'=> 'success', 'questions'=>$introQs]);
    }

    public function checkScenarios(Request $request)
    {
        $hypertension   = strtolower($request->hypertension);
        $diabetes       = strtolower($request->diabetes);

        switch (true) {
            case ($hypertension == 'yes' && $diabetes == 'yes'):
                return $this->getQuestions('cvd');
                break;

            case ($hypertension == 'no' && $diabetes == 'yes'):
                return $this->getQuestions('cvd');
                break;

            case ($hypertension == 'yes' && $diabetes == 'no'):
                return $this->getQuestions('diabetes');
                break;

            case ($hypertension == 'no' && $diabetes == 'no'):
                return $this->getQuestions('diabetes');
                break;
            
            default:
                return $this->getQuestions('diabetes');
                break;
        }
    }

    public function getQuestions($type)
    {
        $questions = Question::where('question_type', $type)->get();

        $qts = [];

        foreach ($questions as $qst) {
            $q = [
                'question' => $qst->question_text,
                'options'  => $qst->options
            ];

            array_push($q, $qts); 
        }

        if ($type === 'diabetes') {
            $assessment_type = 'diabetes';
        }else{
            $assessment_type = 'hypertension';
        }

        return response()->json(['res_type'=> 'success', 'assessment_type'=> $assessment_type, 'questions'=>$questions]);
    }

    public function getQuestion()
    {
        $next  = request()->next;
        $type = request()->test_type == 'diabetes' ? 'diabetes' : 'cvd';
        $question = Question::where('question_type', $type)->where('indicator', $next)->first();
        if (!$question) {
            return response()->json(['res_type'=>'done']);
        }
        return response()->json(['res_type'=>'success', 'question'=>$question]);
    }

    public function analyzeRisk(Request $request)
    {
        $score = 0;
        $bmi = 0;
        $gender = '';
        $assessment = $request->test_type;
        if ($request->test_type == 'diabetes') {
            list($score, $bmi, $gender) = $this->calculateDiabetesScore($request);
        }else{
            list($score, $bmi, $gender) = $this->calculateCVDScore($request);
        }

        return $this->checkRisk($score, $gender, $assessment, $bmi);
    }

    public function calculateDiabetesScore(Request $request)
    {
        $score = 0;
        $bmi = 0;
        $test = $request->all();
        $gender = $test[13];
        for ($i = 13; $i  < 23; $i++) { 
            switch ($i) {
                case 13:
                    $score += 0;
                    break;
                case 14:
                    if ((int) $test[$i] >= 0 && (int) $test[$i] < 45) {
                        $score += 0;
                    }elseif ((int) $test[$i] >= 45 && (int) $test[$i] < 55) {
                        $score += 2;
                    }elseif ((int) $test[$i] >= 55 && (int) $test[$i] < 65) {
                        $score += 3;
                    }elseif ((int) $test[$i] > 65) {
                        $score += 4;
                    }else{
                        $score += 0;
                    }
                    break;
                case 15:
                    $height = (float) $test[$i];
                    $weight = (float) $test[16];
                    $bmi    = round( (float) $weight/($height * $height), 3);
                    if ($bmi >= 0 && $bmi < 25) {
                        $score += 0;
                    }elseif ($bmi >= 25 && $bmi < 30.01) {
                        $score += 1;
                    }elseif ($bmi > 30.01) {
                        $score += 3;
                    }else{
                        $score += 0;
                    }
                    break;
                case 17:
                    if ($gender == 'male' && $test[$i] >= 0 && $test[$i] <= 93.99) {
                        $score += 0;
                    }elseif ($gender == 'male' && $test[$i] >= 94 && $test[$i] <= 102.99) {
                        $score += 3;
                    }elseif ($gender == 'male' && $test[$i] >= 103) {
                        $score += 4;
                    }elseif ($gender == 'female' && $test[$i] >= 0 && $test[$i] <= 79.99) {
                        $score += 0;
                    }elseif ($gender == 'female' && $test[$i] >= 80 && $test[$i] <= 88.99) {
                        $score += 3;
                    }elseif ($gender == 'female' && $test[$i] >= 89) {
                        $score += 4;
                    }
                    break;
                case 18:
                    if ($test[$i] == 'yes') {
                        $score += 0;
                    }else{
                        $score += 2;
                    }
                    break;
                case 19:
                    if ($test[$i] == 'yes') {
                        $score += 0;
                    }else{
                        $score += 1;
                    }
                    break;
                case 20:
                    if ($test[$i] == 'yes') {
                        $score += 2;
                    }else{
                        $score += 0;
                    }
                    break;
                case 21:
                    if ($test[$i] == 'yes') {
                        $score += 5;
                    }else{
                        $score += 0;
                    }
                    break;
                case 22:
                    if ($test[$i] == 'no') {
                        $score += 0;
                    }elseif ($test[23] == 'Grand Parent' || $test[23] == 'Uncle, Aunt or First Cousin') {
                        $score += 3;
                    }elseif ($test[23] == 'Parent' || $test[23] == 'Sibling' || $test[23] == 'Own Child') {
                        $score += 5;
                    }
                    break;
                default:
                    $score += 0;
                    break;
            }
        }

        return array($score, $bmi, $gender);
    }

    public function calculateCVDScore(Request $request)
    {
        $score = 0;
        $bmi = 0;
        $test = $request->all();
        $gender = $test[3];

        for ($i = 3; $i < 10; $i++) { 
            switch ($i) {
                case 3:
                    $score += 0;
                    break;
                case 4:
                    if ($test[$i] <= 30 && $test[$i] < 35) {
                        $score += 0;
                    }elseif ($test[$i] <= 35 && $test[$i] < 40) {
                        $score += 2;
                    }elseif ($test[$i] <= 40 && $test[$i] < 45) {
                        $score += 5;
                    }elseif ($test[$i] <= 45 && $test[$i] < 50 && $gender == 'male') {
                        $score += 7;
                    }elseif ($test[$i] <= 45 && $test[$i] < 50 && $gender == 'female') {
                        $score += 6;
                    }elseif ($test[$i] <= 50 && $test[$i] < 55) {
                        $score += 8;
                    }elseif ($test[$i] <= 55 && $test[$i] < 60) {
                        $score += 10;
                    }elseif ($test[$i] <= 60 && $test[$i] < 65) {
                        $score += 11;
                    }elseif ($test[$i] <= 65 && $test[$i] < 70 && $gender == 'male') {
                        $score += 13;
                    }elseif ($test[$i] <= 65 && $test[$i] < 70 && $gender == 'female') {
                        $score += 12;
                    }elseif ($test[$i] <= 70 && $test[$i] < 75) {
                        $score += 14;
                    }elseif ($test[$i] >=75) {
                        $score += 15;
                    }
                    break;
                case 5:
                    $height = (float) $test[6];
                    $weight = (float) $test[$i];
                    $bmi    = round( (float) $weight/($height * $height), 3);
                    if ($bmi >= 0 && $bmi < 25) {
                        $score += 0;
                    }elseif ($bmi >= 25 && $bmi < 30.01) {
                        $score += 1;
                    }elseif ($bmi > 30.01) {
                        $score += 2;
                    }else{
                        $score += 0;
                    }
                    break;
                case 7:
                    if ($gender == 'male' && $test[$i] == 'no' && $test[8] < 120) {
                        $score += -2;
                    }elseif ($gender == 'male' && $test[$i] == 'no' && $test[8] >= 120 && $test[8] < 130) {
                        $score += 0;
                    }elseif ($gender == 'male' && $test[$i] == 'no' && $test[8] >= 130 && $test[8] < 140) {
                        $score += 1;
                    }elseif ($gender == 'male' && $test[$i] == 'no' && $test[8] >= 140 && $test[8] < 160) {
                        $score += 2;
                    }elseif ($gender == 'male' && $test[$i] == 'no' && $test[8] >= 160) {
                        $score += 3;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] < 120) {
                        $score += -3;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 120 && $test[8] < 130) {
                        $score += 0;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 130 && $test[8] < 140) {
                        $score += 1;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 140 && $test[8] < 150) {
                        $score += 3;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 150 && $test[8] < 160) {
                        $score += 4;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 150 && $test[8] < 160) {
                        $score += 4;
                    }elseif ($gender == 'female' && $test[$i] == 'no' && $test[8] >= 160) {
                        $score += 5;
                    }elseif ($gender == 'male' && $test[$i] == 'yes' && $test[8] < 120) {
                        $score += 0;
                    }elseif ($gender == 'male' && $test[$i] == 'yes' && $test[8] >= 120 && $test[8] < 130) {
                        $score += 2;
                    }elseif ($gender == 'male' && $test[$i] == 'yes' && $test[8] >= 130 && $test[8] < 140) {
                        $score += 3;
                    }elseif ($gender == 'male' && $test[$i] == 'yes' && $test[8] >= 140 && $test[8] < 160) {
                        $score += 4;
                    }elseif ($gender == 'male' && $test[$i] == 'yes' && $test[8] >= 160) {
                        $score += 5;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] < 120) {
                        $score += -1;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] >= 120 && $test[8] < 130) {
                        $score += 2;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] >= 130 && $test[8] < 140) {
                        $score += 3;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] >= 140 && $test[8] < 150) {
                        $score += 5;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] >= 150 && $test[8] < 160) {
                        $score += 6;
                    }elseif ($gender == 'female' && $test[$i] == 'yes' && $test[8] >= 160) {
                        $score += 8;
                    }
                    break;
                case 9:
                    if ($test[$i] == 'yes') {
                        $score += 4;
                    }else{
                        $score += 0;
                    }
                    break;
                
                default:
                    $score += 0;
                    break;
            }
        }

        if ($request->diabetic == 'no') {
            $score += 0;
        }elseif ($request->diabetic == 'yes' && $gender == 'male') {
            $score += 3;
        }elseif ($request->diabetic == 'yes' && $gender == 'female') {
            $score += 5;
        }

        return array($score, $bmi, $gender);
    }

    public function checkRisk($score, $gender, $assessment, $bmi)
    {
        switch ($assessment) {
            case 'hypertension':
                return $this->CVDRiskLevel($score, $gender, $bmi);
                break;

            case 'diabetes':
                return $this->diabetesRiskLevel($score, $gender, $bmi);
                break;
            
            default:
                # code...
                break;
        }
    }

    public function CVDRiskLevel($score, $gender, $bmi)
    {
        // determine risk percentage

        switch (true) {
            case ($score <= -5 && $gender == 'male'):
                return response()->json([
                    'res_type'       => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'          => $score,
                    'risk_level'     => 'Below 1%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == -4 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.1%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == -3 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.4%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == -2 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.6%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == -1 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.9%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 0 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '2.3%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 1 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '2.8%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 2 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '3.3%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 3 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '4.0%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 4 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '4.7%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 5 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '5.6%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 6 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '6.7%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 7 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '8.0%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 8 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '9.5%',
                    'class'     => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 9 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '11.2%',
                    'class'     => 'moderate',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 10 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '13.3%',
                    'class'     => 'moderate',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 11 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '15.7%',
                    'class'     => 'moderate',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 12 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '18.5%',
                    'class'     => 'moderate',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 13 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '21.7%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 14 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '25.4%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 15 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '29.6%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score >= 16 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Above 30%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

                //********************Female Area*************************
                // **********************************************************

                case ($score <= -2 && $gender == 'female'):
                return response()->json([
                    'res_type'       => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'          => $score,
                    'risk_level'     => 'Below 1%',
                    'class'          => 'low',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == -1 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.0%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 0 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.1%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 1 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.5%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 2 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '1.8%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 3 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '2.1%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 4 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '2.5%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 5 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '2.9%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 6 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '3.4%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 7 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '3.9%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 8 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '4.6%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 9 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '5.4%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 10 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '6.3%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 11 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '7.4%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 12 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '8.6%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to maintain this low risk level.'
                ]);
                break;

            case ($score == 13 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '10.0%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 14 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '11.6%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 15 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '13.5%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 16 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '15.6%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 17 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '18.1%',
                    'bmi'       => $bmi,
                    'implication'    => 'You have moderate risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 18 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '20.9%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 19 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> '24.0%',
                    'class'     => 'high',
                    'bmi'       => $bmi,
                    'implication'    => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score == 20 && $gender === 'female'):
                return response()->json([
                    'res_type'       => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'         => $score,
                    'risk_level'    => '27.5%',
                    'class'         => 'high',
                    'bmi'       => $bmi,
                    'implication'   => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;

            case ($score >= 21 && $gender === 'female'):
                return response()->json([
                    'res_type'       => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'         => $score,
                    'risk_level'    => 'Above 30%',
                    'class'         => 'high',
                    'bmi'       => $bmi,
                    'implication'   => 'You have high risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;
            
            default:
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Lower than 1%',
                    'class'     => 'low',
                    'bmi'       => $bmi,
                    'implication'=>'You have low risk of having a cardiovascular disease like heart attack, stroke or others within 10 years.',
                    'recommendation' => 'Sign up and learn how to reduce your risk of having a cardiovascular disease in future.'
                ]);
                break;
        }
    }

    public function diabetesRiskLevel($score, $bmi)
    {
        switch (true) {
            case ($score < 7):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Very Low',
                'class'     => 'very-low',
                'bmi'       => $bmi,
                'implication'=>"You have very low risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 100 will develop type 2 diabetes.",
                'recommendation' => 'Sign up and learn how to maintain a very low risk for developing type 2 diabetes.'
            ]);
            break;

            case ($score >= 7 && $score < 12):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Low',
                'class'     => 'low',
                'bmi'       => $bmi,
                'implication'=>"You have low risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 25 will develop type 2 diabetes.",
                'recommendation' => 'Sign up and learn how to maintain a very low risk for developing type 2 diabetes.'
            ]);
            break;

            case ($score >= 12 && $score < 15):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Moderate',
                'class'     => 'moderate',
                'bmi'       => $bmi,
                'implication'=>"You have moderate risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 6 will develop type 2 diabetes.",
                'recommendation' => 'Sign up for Viedial’s type 2 diabetes prevention program to reduce your risk of developing type 2 diabetes. This program will teach you how to lower the chance of developing type 2 diabetes by as much as 80%.'
            ]);
            break;

            case ($score >= 15 && $score < 21):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'High',
                'class'     => 'high',
                'bmi'       => $bmi,
                'implication'=>"You have a high risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 3 will develop type 2 diabetes.",
                'recommendation' => 'Sign up for Viedial’s type 2 diabetes prevention program to reduce your risk of developing type 2 diabetes. This program will teach you how to lower the chance of developing type 2 diabetes by as much as 80%.'
            ]);
            break;

            case ($score >= 21):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Very High',
                'bmi'       => $bmi,
                'class'     => 'very-high',
                'implication'=>"ou have a very high risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 2 will develop type 2 diabetes.",
                'recommendation' => 'Sign up for Viedial’s type 2 diabetes prevention program to reduce your risk of developing type 2 diabetes. This program will teach you how to lower the chance of developing type 2 diabetes by as much as 80%.'
            ]);
            break;
            
            default:
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Very Low',
                'class'     => 'very-low',
                'bmi'       => $bmi,
                'implication'=>"You have very low risk of developing type 2 diabetes within 10 years. It is estimated that 1 in 100 will develop type 2 diabetes.",
                'recommendation' => 'Sign up and learn how to maintain a very low risk for developing type 2 diabetes.'
            ]);
            break;
        }
    }
}