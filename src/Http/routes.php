<?php

use DagaSmart\Dict\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/admin_dict/options', [Controllers\DictController::class, 'dictOptions']);
Route::get('/admin_dict/dict_type_options', [Controllers\DictController::class, 'dictTypeOptions']);
Route::resource('/admin_dict', Controllers\DictController::class);
