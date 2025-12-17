<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OjisanController;

// メイン画面
Route::get('/ojisan', [OjisanController::class, 'index']);

// おじさんを1人だけJSONで返すAPI用ルート
Route::get('/ojisan/fetch', [OjisanController::class, 'fetchOne']);