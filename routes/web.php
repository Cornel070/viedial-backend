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
    // Authentication Routes
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', ['uses' => 'AuthController@login']);
        $router->post('register', ['uses' => 'AuthController@register']);
        $router->get('verify/{code}', ['uses' => 'AuthController@verifyFromEmail']);
    });

    // Risk Assessment Routes
    $router->group(['prefix' => 'risk'], function () use ($router) {
        $router->get('intro-questions', ['uses' => 'RiskController@introQuestions']);
        $router->get('get-assessment', ['uses' => 'RiskController@checkScenarios']);
        $router->post('check-risk', ['uses' => 'RiskController@checkRisk']);
    });

    $router->group(['middleware' => ['auth']], function () use ($router) {
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
            $router->get('make-vid-call/{vendor_id}', ['uses' => 'CommController@makeVidCall']);
            $router->get('join-vid-call/{roomName}', ['uses' => 'CommController@joinVidMeeting']);
            // Voice Calls
            $router->get('voice-call/{number}', ['uses' => 'CommController@makeVoiceCall']);//research how to mmake in-app calls
            // Direct Chat
            $router->post('send-chat', ['uses' => 'ChatController@sendDirectChat']);
            $router->get('all-chats', ['uses' => 'ChatController@viewAllChats']);
            $router->get('chat/{id}/messages', ['uses' => 'ChatController@allChatMessages']);
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
    });
});
