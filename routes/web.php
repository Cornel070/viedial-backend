<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'api'], function () use ($router) {
    /*
        (Admin Access) Routes Starts
    */
    $router->group(['prefix' => 'admin'], function () use ($router) {
        // Educational Curriculum 
        $router->group(['prefix' => 'edu'], function () use ($router) {
            $router->post('create-series', ['uses' => 'EduController@createSeries']);
            $router->post('series/{series_id}/video', ['uses' => 'EduController@saveVidoes']);
        });

        // Physical Activity 
        $router->group(['prefix' => 'phy'], function () use ($router) {
            $router->post('create-series', ['uses' => 'PhysicalController@createSeries']);
            $router->post('series/{series_id}/video', ['uses' => 'PhysicalController@saveVidoes']);
        });

        // Meal Planning
        $router->group(['prefix' => 'meal'], function () use ($router) {
            //Food types and Items
            $router->post('create-food', ['uses' => 'MealController@createFood']);
            $router->post('add', ['uses' => 'MealController@addMeal']);
            $router->get('food-types', ['uses' => 'MealController@getFoodTypes']);
        });
    });
    /*
        (Admin Access) Routes Ends
    */


    // Authentication Routes
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', ['uses' => 'AuthController@login']);
        $router->post('register', ['uses' => 'AuthController@register']);
        $router->get('verify/{code}', ['uses' => 'AuthController@verifyFromEmail']);
        $router->get('key/{key}/check', ['uses' => 'AuthController@checkAcctKey']); //forgot password
        $router->post('update-password', ['uses' => 'AuthController@updatePassword']); //forgot password
        $router->get('check-token', ['uses' => 'AuthController@checkToken']);//check if token has expired
        $router->get('users', ['uses' => 'AuthController@getUsers']);//All details (For test purpose only: don't deploy to live)
    });

    // Risk Assessment Routes
    $router->group(['prefix' => 'risk'], function () use ($router) {
        $router->get('intro-questions', ['uses' => 'RiskController@introQuestions']);
        $router->get('get-assessment', ['uses' => 'RiskController@checkScenarios']);
        $router->post('check-risk', ['uses' => 'RiskController@checkRisk']);
    });

    $router->group(['middleware' => ['auth']], function () use ($router) {

        // Payment Routes
        $router->group(['prefix' => 'pay'], function () use ($router) {
            //price for user's plan
            $router->get('user_price', ['uses' => 'PaymentController@getUserPlanPrice']);
            //Stripe
            $router->post('stripe/onetime', ['uses' => 'PaymentController@stripeOneTime']);
            //payment confirmation
            $router->post('user-paid', ['uses' => 'PaymentController@updatPaidUser']);
        });

        // Tele-monitoring Routes
        $router->group(['prefix' => 'tele'], function () use ($router) {
            $router->post('save-reading', ['uses' => 'TeleMonitoringController@saveReading']);
            $router->get('today-readings', ['uses' => 'TeleMonitoringController@todayReadings']);
            $router->get('all-readings', ['uses' => 'TeleMonitoringController@allReadings']);
        });


        // Goal Setting Routes
        $router->group(['prefix' => 'goal'], function () use ($router) {
            $router->post('set-goal', ['uses' => 'GoalController@saveGoal']);
            $router->get('/{id}', ['uses' => 'GoalController@showGoal']);
            $router->post('/{id}/update', ['uses' => 'GoalController@updateGoal']);
            $router->get('/', ['uses' => 'GoalController@allGoals']);
            $router->delete('/{id}/delete', ['uses' => 'GoalController@deleteGoal']);
        });

        // Communication Routes
        $router->group(['prefix' => 'comm'], function () use ($router) {
            //Video Calls
            $router->post('create-vid-meeting', ['uses' => 'CommController@createVidMeeting']);
            $router->get('make-vid-call/{recipient_id}', ['uses' => 'CommController@makeVidCall']);
            $router->get('vid/notify/{id}/{roomName}', ['uses' => 'CommController@notifyVidRecipient']);
            $router->get('join-vid-call/{roomName}', ['uses' => 'CommController@joinVidMeeting']);
            $router->post('device-token', ['uses' => 'CommController@saveDeviceID']);
            // Voice Calls
            $router->get('voice-call/{number}', ['uses' => 'CommController@makeVoiceCall']);//research how to make in-app calls
            
            // Direct Chat
            $router->post('send-chat', ['uses' => 'ChatController@sendDirectChat']);
            $router->get('all-chats', ['uses' => 'ChatController@viewAllChats']);
            $router->get('chat/{id}/messages', ['uses' => 'ChatController@allChatMessages']);
            $router->get('recipients', ['uses' => 'ChatController@allDoctors']);
            $router->get('check-chat/{user_id}', ['uses' => 'ChatController@checkPrevChat']);//###########
            $router->get('chat/{id}/read', ['uses' => 'ChatController@markChatAsRead']);//###########
            $router->get('msg/{id}/read', ['uses' => 'ChatController@markMsgAsRead']);//###########
            $router->delete('delete-message/{id}', ['uses' => 'ChatController@deleteMessage']);
            $router->delete('delete-chat/{id}', ['uses' => 'ChatController@deleteChat']);
            // Multi deletes
            $router->post('multi-delete-messages', ['uses' => 'ChatController@multiDeleteMsgs']);
            $router->post('multi-delete-chats', ['uses' => 'ChatController@multiDeleteChats']);
            // Appointment Routes
            $router->post('send-appt-request', ['uses' => 'ApptController@setAppt']);
            $router->get('appt/{id}/accept', ['uses' => 'ApptController@acceptApptRequest']);
            $router->post('appt/{id}/decline', ['uses' => 'ApptController@declineApptRequest']);
        });

        // Educational Curriculum Routes
        $router->group(['prefix' => 'edu'], function () use ($router) {
            //Series
            $router->get('/', ['uses' => 'EduController@index']);
            $router->get('series/{id}/videos', ['uses' => 'EduController@seriesVideos']);
            $router->post('series/{id}/comment', ['uses' => 'EduController@commentOnSeries']);
            $router->get('series/{id}/comments', ['uses' => 'EduController@seriesComments']);
            $router->get('series/{id}/like', ['uses' => 'EduController@likeSeries']);
            $router->get('series/{id}/dislike', ['uses' => 'EduController@dislikeSeries']);
            //Videos
            $router->post('video/{id}/comment', ['uses' => 'EduController@commentOnVideo']);
            $router->get('video/{id}/comments', ['uses' => 'EduController@videoComments']);
            $router->post('video-comment/{id}/reply', ['uses' => 'EduController@replyVideoComments']);
            $router->get('video-comment/{id}', ['uses' => 'EduController@singleComment']);
            $router->get('video-comment/{id}/like', ['uses' => 'EduController@likeComment']);
            $router->get('video-comment/{id}/dislike', ['uses' => 'EduController@dislikeComment']);
            $router->get('video/{id}/like', ['uses' => 'EduController@likeVideo']);
            $router->get('video/{id}/dislike', ['uses' => 'EduController@dislikeVideo']);
            $router->get('video-reply/{id}/like', ['uses' => 'EduController@likeVideoCommtReply']);
            $router->get('video-reply/{id}/dislike', ['uses' => 'EduController@dislikeVideoCommtReply']);
        });

        // Viedial Community Routes
        $router->group(['prefix' => 'cmt'], function () use ($router) {
            //Post
            $router->post('make-post', ['uses' => 'CommunityController@makePost']);
            $router->get('topics', ['uses' => 'CommunityController@getTopics']);
            $router->get('posts', ['uses' => 'CommunityController@allPosts']);
            $router->get('post/{id}', ['uses' => 'CommunityController@singlePost']);
            $router->get('post/{id}/like', ['uses' => 'CommunityController@likePost']);
            $router->get('post/{id}/dislike', ['uses' => 'CommunityController@dislikePost']);
            $router->post('post/{id}/comment', ['uses' => 'CommunityController@commentOnPost']);
            $router->get('post/{id}/comments', ['uses' => 'CommunityController@getPostComments']);
            $router->post('comment/{id}/reply', ['uses' => 'CommunityController@sendReply']);
            $router->get('comment/{id}', ['uses' => 'CommunityController@singleComment']);
            $router->get('comment/{id}/like', ['uses' => 'CommunityController@likeComment']);
            $router->get('comment/{id}/dislike', ['uses' => 'CommunityController@dislikeComment']);
            $router->get('reply/{id}/like', ['uses' => 'CommunityController@likeCommentReply']);
            $router->get('reply/{id}/dislike', ['uses' => 'CommunityController@dislikeCommentReply']);
        });

        // Physical Activity Routes
        $router->group(['prefix' => 'phy'], function () use ($router) {
            //Series
            $router->get('/', ['uses' => 'PhysicalController@newIndex']);
            $router->get('series/{id}/videos', ['uses' => 'PhysicalController@seriesWorkouts']);
            $router->get('series/{id}/comments', ['uses' => 'PhysicalController@seriesComments']);
            $router->get('series/{id}/like', ['uses' => 'PhysicalController@likeSeries']);
            $router->get('series/{id}/dislike', ['uses' => 'PhysicalController@dislikeSeries']);
            $router->post('series/{id}/comment', ['uses' => 'PhysicalController@commentOnPhy']);
            //Videos
            $router->post('video/{id}/comment', ['uses' => 'PhysicalController@commentOnWorkout']);
            $router->get('video/{id}/comments', ['uses' => 'PhysicalController@workoutComments']);
            $router->get('video/{id}/like', ['uses' => 'PhysicalController@likeVideo']);
            $router->get('video/{id}/dislike', ['uses' => 'PhysicalController@dislikeVideo']);
            $router->get('video/{id}/done', ['uses' => 'PhysicalController@doneWorkout']);
            $router->post('done-videos', ['uses' => 'PhysicalController@multiDoneWorkouts']);//#######
            $router->get('video/{id}/undone', ['uses' => 'PhysicalController@undoWorkout']);
            $router->post('undone-videos', ['uses' => 'PhysicalController@multiUnDoneWorkouts']);//#######
        });

        // Update different parts of the apps
        $router->group(['prefix' => 'update'], function () use ($router) {
            //update phone
            $router->post('phone', ['uses' => 'UpdateController@updatePhone']);
        });

        // Notifications and Reminders Routes
        $router->group(['prefix' => 'notification'], function () use ($router) {
            $router->get('all', ['uses' => 'NotificationController@allNotifications']);
        });

        // Meal Planning
        $router->group(['prefix' => 'meal'], function () use ($router) {
            $router->get('food-types', ['uses' => 'MealController@getFoodTypes']);
            $router->post('select-food-items', ['uses' => 'MealController@createFoodItemSelection']);
            $router->get('suggestions', ['uses' => 'MealController@suggestMeals']);
            $router->get('{id}/eaten', ['uses' => 'MealController@markMealAsEaten']);
            $router->get('{id}/uneaten', ['uses' => 'MealController@markMealAsUneaten']);
            $router->get('summary', ['uses' => 'MealController@geMealReport']);
        });

        // App Dashboard Route
        $router->group(['prefix' => 'dash'], function () use ($router) {
            $router->get('/', ['uses' => 'UpdateController@dashboard']);
        });
    });
});
