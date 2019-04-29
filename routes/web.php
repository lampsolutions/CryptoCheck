<?php

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

$router->get('/', function () use ($router) {
    return redirect(route('swagger-lume.api'));
});


$router->group(['prefix' => 'api/v1/', 'middleware' => 'api_auth'], function () use ($router) {
    $router->get('registerListener/callback/', 'RegisterListenerController@registerListenerCallback');
    $router->get('removeListener/callback/', 'RegisterListenerController@removeListenerCallback');
});

