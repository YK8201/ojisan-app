<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * スイカゲーム（物理演算パズル）のビューを表示する
     *
     * @return \Illuminate\View\View
     */
    public function suika()
    {
        // 必要なデータがあればここで処理を行いますが、
        // 今回はシンプルにビューを返します。
        return view('game.suika');
    }
}