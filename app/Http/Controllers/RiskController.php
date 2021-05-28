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

    public function checkRisk(Request $request)
    {
        $assessment = $request->risk_assessment_type;
        $score      = $request->score;
        $gender     = strtolower($request->gender);

        switch ($assessment) {
            case 'hypertension':
                return $this->CVDRiskLevel($score, $gender);
                break;

            case 'diabetes':
                return $this->diabetesRiskLevel($score);
                break;
            
            default:
                # code...
                break;
        }
    }

    public function CVDRiskLevel($score, $gender)
    {
        switch (true) {
            case ($score < 11 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Low',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;

            case ($score < 13 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Low',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;

            case ($score > 10  && $score < 15 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Intermediate',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;

            case ($score > 12  && $score < 18 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Intermediate',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;

            case ($score > 14 && $gender === 'male'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'High',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;

            case ($score > 17 && $gender === 'female'):
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension',
                    'score'     => $score,
                    'risk_level'=> 'High',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;
            
            default:
                return response()->json([
                    'res_type'  => 'success',
                    'assessment_type'=> 'hypertension', 
                    'score'     => $score,
                    'risk_level'=> 'Low',
                    'implication'=>'Hypertension is called a "silent killer". Most people with hypertension are unaware of the problem because it may have no warning signs or symptoms. For this reason, it is essential that blood pressure is measured regularly.'
                ]);
                break;
        }
    }

    public function diabetesRiskLevel($score)
    {
        switch (true) {
            case ($score < 7):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Low',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin. One of every four people with diabetes doesn't know they have it."
            ]);
            break;

            case ($score > 6 && $score < 12):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Slightly elevated',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin. One of every four people with diabetes doesn't know they have it."
            ]);
            break;

            case ($score > 11 && $score < 15):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Moderate',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin. One of every four people with diabetes doesn't know they have it."
            ]);
            break;

            case ($score > 14 && $score < 21):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'High',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin. One of every four people with diabetes doesn't know they have it."
            ]);
            break;

            case ($score > 20):
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Very High',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin.One of every four people with diabetes doesn't know they have it."
            ]);
            break;
            
            default:
                return response()->json([
                'res_type'  => 'success',
                'assessment_type'=> 'diabetes', 
                'score'     => $score,
                'risk_level'=> 'Low',
                'implication'=>"There are three major types of the disease: type 1, type 2, and gestational diabetes. With all three, your body can't make or use insulin. One of every four people with diabetes doesn't know they have it."
            ]);
            break;
        }
    }
}