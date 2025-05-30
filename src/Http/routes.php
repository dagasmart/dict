<?php

use DagaSmart\Dict\Http\Controllers;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

//需登录与鉴权
//Route::group([
//    'middleware' => config('admin.route.middleware'),
//], function (Router $router) {
//
//    $router->get('/basic/dict/type_options', [Controllers\DictController::class, 'dictTypeOptions']);
//    $router->get('/basic/dict/options', [Controllers\DictController::class, 'dictOptions']);
//
//    //resource必须放最后面
//    $router->resource('/basic/dict', Controllers\DictController::class);
//
//});


Route::get('/basic/dict/type_options', [Controllers\DictController::class, 'dictTypeOptions']);
Route::get('/basic/dict/options', [Controllers\DictController::class, 'dictOptions']);

//resource必须放最后面
Route::resource('/basic/dict', Controllers\DictController::class);
