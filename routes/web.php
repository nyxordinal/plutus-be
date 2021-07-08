<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Response;

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

$router->group(
    ['prefix' => '/public/v1/'],
    function ($router) {
        $router->get('/', function () use ($router) {
            return response()->json(['code' => Response::HTTP_OK, 'message' => 'Welcome to Nyxordinal Plutus API']);
        });

        $router->group(['prefix' => 'auth'], function ($router) {
            $router->get('me', 'AuthController@me');
            $router->get('refresh', 'AuthController@refresh');
            $router->post('login', 'AuthController@login');
            $router->post('register', 'AuthController@register');
        });

        $router->group(['prefix' => 'expense'], function ($router) {
            $router->get('/', 'ExpenseController@getExpense');
            $router->post('/', 'ExpenseController@createExpense');
            $router->put('/', 'ExpenseController@updateExpense');
            $router->delete('/', 'ExpenseController@deleteExpense');
        });
    }
);
