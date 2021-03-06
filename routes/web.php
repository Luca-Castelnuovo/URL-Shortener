<?php

use CQ\Middleware\JSON;
use CQ\Middleware\Session;
use CQ\Routing\Middleware;
use CQ\Routing\Route;

Route::$router = $router->get();
Middleware::$router = $router->get();

Route::get('/', 'GeneralController@index');
Route::get('/error/{code}', 'GeneralController@error');

Route::get('/{short_url}/?{option?}', 'LinkController@index');

Middleware::create(['prefix' => '/auth'], function () {
    Route::get('/request', 'AuthController@request');
    Route::get('/callback', 'AuthController@callback');

    Route::get('/request/device', 'AuthController@requestDevice');
    Route::post('/callback/device', 'AuthController@callbackDevice');

    Route::get('/logout', 'AuthController@logout');
});

Middleware::create(['middleware' => [Session::class]], function () {
    Route::get('/dashboard', 'UserController@dashboard');
    Route::get('/admin', 'UserController@admin');
});

Middleware::create(['prefix' => '/link', 'middleware' => [Session::class]], function () {
    Route::post('', 'LinkController@create', JSON::class);
    Route::put('/{id}', 'LinkController@update', JSON::class);
    Route::delete('/{id}', 'LinkController@delete');
});
