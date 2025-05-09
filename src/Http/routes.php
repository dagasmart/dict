<?php

use DagaSmart\Dict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::resource('/basic/dict', Controllers\BasicDictController::class);
Route::get('/basic/dict/options', [Controllers\BasicDictController::class, 'dictOptions']);
Route::get('/basic/dict/dict_type_options', [Controllers\BasicDictController::class, 'dictTypeOptions']);
