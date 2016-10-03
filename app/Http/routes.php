<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'chat/client'],function(){

    Route::get('init','ChatController@init');
    Route::get('ping','ChatController@pingServer');
    Route::post('sendMessage','ChatController@saveMessage');
    Route::get('test','ChatController@test');

});

Route::group(['prefix' => 'admin'],function(){

    Route::post('doLogin','AdminController@doLogin');

    Route::group(['middleware' => 'auth'],function(){
        Route::get('chat','AdminController@viewChatPage');
        Route::get('getChatRequests','AdminController@getChatRequests');
        Route::get('getOldChat','AdminController@getOldChat');
        Route::get('getChatMessages','AdminController@getChatMessages');
        Route::post('sendMessage','AdminController@saveMessage');
        Route::get('listen','AdminController@listen');
        Route::post('getNotifications','AdminController@getNotifications');
        Route::get('logout','AdminController@doLogout');
    });
});



Route::get('contact-us', function () {
    return view('contact-us');
});
Route::post('submitContactUs','ContactUsController@saveContactUs');