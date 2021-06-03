<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use App\Models\VidMeeting;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\VidMeetingsUser;
use App\Events\VideoStarted;
use App\Models\VideoCall;
use App\Models\User;
use App\Models\VideoCallLog;
use App\Http\Controllers\NotificationController;

class CommController extends Controller
{
    protected $sid;
	protected $token;
	protected $key;
	protected $secret;
	protected $from;
	protected $client;
    protected $user;

	public function __construct()
	{
	   $this->sid = env('TWILIO_ACCOUNT_SID');
	   $this->token = env('TWILIO_ACCOUNT_TOKEN');
	   $this->key = env('TWILIO_API_KEY');
	   $this->secret = env('TWILIO_API_SECRET');
	   $this->from = env('TWILIO_PHONE_NUMBER');
	   $this->client = new Client($this->sid, $this->token);
       $this->user = auth()->user();
	}

	public function makeVidCall(Request $request, $vendor_id)
    {
    	$device_token = $request->device_token;
    	$recipient_name = $request->recipient;

    	$video_call = $this->createVidMeeting($recipient_name);
    	return $this->joinVidMeeting($video_call->room_name, $device_token);
    }

	public function createVidMeeting($recipient_name)
    {
       $roomName = $this->generateVidGroup();

       $video_call = New VideoCallLog;
       $video_call->room_name = $roomName;
       $video_call->caller = $this->user->name;
       $video_call->recipient = $recipient_name;
       $video_call->call_date = Carbon::now();
       $video_call->save();

       return $video_call;
    }

    private function generateVidGroup()
    {
       $client = new Client($this->sid, $this->token);
       $roomName = $this->genRoomName();
	   $exists = $client->video->rooms->read([ 'uniqueName' => $roomName]);

	   if (empty($exists)) {
	       $client->video->rooms->create([
	           'uniqueName' => $roomName,
	           'type' => 'group',
	           'recordParticipantsOnConnect' => false
	       ]);
	   }

	   return $roomName;
    }

    private function genRoomName()
    {
    	return Str::random(5);
    }

    // Join video meeting function
    public function joinVidMeeting($roomName, $device_token = null)
    {
       $identity = $this->user->name;
	   $token = new AccessToken($this->sid, $this->key, $this->secret, 3600, $identity);

	   $videoGrant = new VideoGrant();
	   $videoGrant->setRoom($roomName);

	   $token->addGrant($videoGrant);
	   $call = VideoCallLog::where('room_name', $roomName)->first();

	   if ($call) {
		   	// broadcast(new VideoStarted($identity, $call))->toOthers();

	   		if ($device_token) {
	   			// send the push notification here
	   			NotificationController::push($device_token, $roomName, $identity);
	   		}

		   	return response()->json([
		   		'res_type'=>'success', 'accessToken'=> $token->toJWT(), 
		   		'roomName' =>$roomName, 'user_identity'=>$identity
		   	]);
	   }

	   return response()->json(['res_type'=>'error', 'message'=>'Video callroom not found'],404);
    }

    public function makeVoiceCall($number)
    {
    	$validator = $this->validateNO($number);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        try {
        	$phone_number = $this->client->lookups->v1->phoneNumbers($number)->fetch();

	       if($phone_number) {
		        // Initiate call and record call
		        $call = $this->client->account->calls->create(
		          $number, // Destination phone number
		          $this->from, // Valid Twilio phone number
		          array(
		              "record" => True,
		              "url" => "http://demo.twilio.com/docs/voice.xml")
		          );

		        if($call) {
		          return response()->json(['res_type'=>'success', 'message'=>'Call initiated'],200);
		        } else {
		          return response()->json(['res_type'=>'error', 'message'=>'Unable to initiate call'],424);
		        }
	        }else{
	        	return response()->json(['res_type'=>'error', 'message'=>'Invalid phone number'],424);
	        }
        } catch (Exception $e) {
        	return response()->json(['res_type'=>'error', 'message'=>$e->getMessage()]);
        }catch (RestException $rest) {
        	return response()->json(['res_type'=>'error', 'message'=>$rest->getMessage()],424);
    	}
    }

    public function validateNO($number)
    {
    	$data = ['number'=>$number];
    	return validator()->make($data, [
            'number' => 'required|string',
        ]);
    }
}
