<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OjisanController;
use App\Http\Controllers\GameController;

// メイン画面（おじさんシアター）
Route::get('/ojisan', [OjisanController::class, 'index']);

// おじさんを1人だけJSONで返すAPI用ルート
Route::get('/ojisan/fetch', [OjisanController::class, 'fetchOne']);

// MEME THEATER用ルート
// ★ここを 'meme' メソッドに変更します
Route::get('/meme', [OjisanController::class, 'meme']); 
Route::get('/meme/fetch', [OjisanController::class, 'fetchMeme']);

//スイカゲーム
Route::get('/game/suika', [GameController::class, 'suika'])->name('game.suika');