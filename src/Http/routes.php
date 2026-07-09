<?php

use DagaSmart\Dict\Http\Controllers;
use DagaSmart\Dict\Http\Middleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

// 需登录与鉴权
Route::group([
    'prefix' => 'basic',
    'middleware' => [
        Middleware\Middleware::class,
    ],
], function (Router $router) {
    $router->get('dict/type_options', [Controllers\DictController::class, 'dictTypeOptions']);
    $router->get('dict/options', [Controllers\DictController::class, 'dictOptions']);
    $router->post('dict/save_order', [Controllers\DictController::class, 'saveOrder']);
    // resource必须放最后面
    $router->resource('dict', Controllers\DictController::class);
});
