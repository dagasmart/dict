<?php

use DagaSmart\Dict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/basic/dict/options', [Controllers\DictController::class, 'dictOptions']);
Route::get('/basic/dict/type_options', [Controllers\DictController::class, 'dictTypeOptions']);
Route::resource('/basic/dict', Controllers\DictController::class);
