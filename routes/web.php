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

        $router->get('login-state', 'AuthController@getServerTimestamp');
        $router->post('upload', 'ExpenseController@uploadExpense');

        $router->group(['prefix' => 'auth'], function ($router) {
            $router->get('me', 'AuthController@me');
            $router->get('refresh', 'AuthController@refresh');
            $router->post('login', 'AuthController@login');
            $router->post('register', 'AuthController@register');
            $router->post('forgot', 'AuthController@sendResetPasswordEmail');
            $router->post('reset', 'AuthController@resetPassword');
        });

        $router->group(['prefix' => 'home'], function ($router) {
            $router->get('/stat', 'HomeController@getSummaryStat');
        });

        $router->group(['prefix' => 'expense'], function ($router) {
            $router->group(['prefix' => 'draft'], function ($router) {
                $router->get('/', 'ExpenseDraftController@getExpenseDrafts');
                $router->put('/', 'ExpenseDraftController@updateExpenseDraft');
                $router->post('/approve', 'ExpenseDraftController@approveExpenseDraft');
                $router->post('/deny', 'ExpenseDraftController@denyExpenseDraft');
            });

            $router->post('/delete/bulk', 'ExpenseController@bulkDeleteExpense');
            $router->get('/summary', 'ExpenseController@getExpenseSummary');
            $router->get('/', 'ExpenseController@getExpense');
            $router->post('/', 'ExpenseController@createExpense');
            $router->put('/', 'ExpenseController@updateExpense');
        });

        $router->group(['prefix' => 'income'], function ($router) {
            $router->post('/delete/bulk', 'IncomeController@bulkDeleteIncome');
            $router->get('/summary', 'IncomeController@getIncomeSummary');
            $router->get('/', 'IncomeController@getIncome');
            $router->post('/', 'IncomeController@createIncome');
            $router->put('/', 'IncomeController@updateIncome');
        });

        $router->group(['prefix' => 'setting'], function ($router) {
            $router->get('/', 'SettingController@getSettings');
            $router->put('/', 'SettingController@updateSettings');
        });
    }
);

$router->group(
    ['prefix' => '/public/v2/'],
    function ($router) {
        $router->group(['prefix' => 'auth'], function ($router) {
            $router->post('login', 'AuthController@loginV2');
        });
    }
);

$router->group(['prefix' => '/internal/v1/'], function ($router) {
    $router->group(['prefix' => '/expense', 'middleware' => 'verify.internal.client'], function ($router) {
        $router->post('/', 'ExpenseInternalController@createExpenseInternal');
    });
});
