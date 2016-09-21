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

use Illuminate\Http\Request;

Route::group(['middleware' => ['web']], function () {


    Route::get('/', 'MainController@index');

    // // 认证路由...
    // Route::get('/auth/login', 'Auth\AuthController@getLogin');
    // Route::post('/auth/login', 'Auth\AuthController@postLogin');
    // Route::get('/auth/logout', 'Auth\AuthController@getLogout');
    // Route::get('/login', 'Auth\AuthController@getLogin');
    // Route::post('/login', 'Auth\AuthController@postLogin');
    // Route::get('/logout', 'Auth\AuthController@getLogout');

    // // 注册路由...
    // Route::get('/auth/register', 'Auth\AuthController@getRegister');
    // Route::post('/auth/register', 'Auth\AuthController@postRegister');

    // 用户信息...
    Route::get('/user/{uid?}', 'UserController@index');

    // 关注/粉丝路由...
    Route::post('/follow/{follow_uid}', 'FollowController@add');
    Route::delete('/follow/{follow_uid}', 'FollowController@delete');

    // 动态...
    // /feeds 获取关注人的最新前x条动态
    // /feeds/0/{end_time} 获取关注人的在{end_time}前发布的最新x条动态
    // /feeds/{start_time}/{end_time} 获取关注人在{start_time}和{end_time}之间的前x条动态
    // /feeds/0/{start_time}/{end_time} 获取某个人的最新前x条动态
    // /feeds/{start_time}/{end_time}/{uid} 获取某人在{start_time}和{end_time}之间的前x条动态
    // created_at E [start_time,end_time] js需要注意取值范围，避免重复取值
    Route::get('/feeds/{start_time?}/{end_time?}/{uid?}', 'FeedController@index');
    Route::get('/feed/{feed_id}', 'FeedController@feed');
    Route::post('/feed', 'FeedController@add');
    Route::delete('/feed/{feed_id}', 'FeedController@delete');
    Route::post('/like/{feed_id}', 'FeedController@like');
    Route::delete('/like/{feed_id}', 'FeedController@unlike');
    Route::get('/clear', 'FeedController@redis');

});
