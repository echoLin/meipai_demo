<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

use App\Task;
use Illuminate\Http\Request;

Route::group(['middleware' => ['web']], function () {


    Route::get('/', 'MainController@index');

    // 认证路由...
    Route::get('/auth/login', 'Auth\AuthController@getLogin');
    Route::post('/auth/login', 'Auth\AuthController@postLogin');
    Route::get('/auth/logout', 'Auth\AuthController@getLogout');
    Route::get('/login', 'Auth\AuthController@getLogin');
    Route::post('/login', 'Auth\AuthController@postLogin');
    Route::get('/logout', 'Auth\AuthController@getLogout');

    // 注册路由...
    Route::get('/auth/register', 'Auth\AuthController@getRegister');
    Route::post('/auth/register', 'Auth\AuthController@postRegister');

    // 用户信息...
    Route::get('/user/{uid?}', 'UserController@index');

    // 关注/粉丝路由...
    Route::post('/follow/{follow_uid}', 'FollowController@add');
    Route::delete('/follow/{follow_uid}', 'FollowController@delete');

    // 动态
    Route::get('/feed/{max?}/{min?}', 'FeedController@index');
    Route::get('/feed/{feed_id}', 'FeedController@feed');
    Route::post('/feed', 'FeedController@add');
    Route::delete('/feed/{feed_id}', 'FeedController@delete');
    Route::post('/like/{feed_id}', 'FeedController@like');
    Route::delete('/like/{feed_id}', 'FeedController@unlike');

    // Redis
    Route::get('/redis/{$key?}', 'FeedController@redis');

});
