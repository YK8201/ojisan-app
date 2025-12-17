<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OjisanController extends Controller
{
    // メイン画面（変更なし、または新しいビューを指定）
    public function index()
    {
        return view('meme_theater');
    }

    // Unsplash用（既存のまま残してもOK）
    // public function fetchOne() ...

    // ★追加: GIPHY用API
    public function fetchMeme()
    {
        $apiKey = env('GIPHY_API_KEY');
        
        // 検索ワード: ここを変えると出てくるミームが変わります
        // "funny old man", "grandpa dancing", "senior citizen reaction" など
        $query = 'funny old man'; 

        // ランダム性を出すために offset (取得開始位置) をランダムにする
        // GIPHYの検索結果は膨大なので、0〜499くらいの間でランダム化
        $offset = rand(0, 499);

        $response = Http::get('https://api.giphy.com/v1/gifs/search', [
            'api_key' => $apiKey,
            'q'       => $query,
            'limit'   => 1,
            'offset'  => $offset,
            'rating'  => 'pg-13', // 過激すぎるものを除外 (g, pg, pg-13, r)
            'lang'    => 'en'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if (empty($data['data'])) {
                return response()->json(['error' => 'No meme found'], 404);
            }

            // GIPHYは 'data' 配列の中に結果が入っています
            return response()->json($data['data'][0]);
        }

        return response()->json(['error' => 'GIPHY API Error'], 500);
    }
}