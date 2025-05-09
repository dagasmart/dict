<?php

use DagaSmart\Dict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/basic/dict/dict_type_options', [Controllers\DictController::class, 'dictTypeOptions']);
Route::resource('/basic/dict', Controllers\DictController::class);
