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
        $query = 'meme'; 

        // 修正点: 範囲を 0〜499 から 0〜50 に狭める
        // これなら確実にヒットします
        $offset = rand(0, 50);

        $response = Http::get('https://api.giphy.com/v1/gifs/search', [
            'api_key' => $apiKey,
            'q'       => $query,
            'limit'   => 1,
            'offset'  => $offset,
            'rating'  => 'pg-13',
            'lang'    => 'en'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            // データが見つからなかった場合の保険
            // 再検索するか、エラーではなく空の成功レスポンスを返すように変更しても良いですが、
            // まずはオフセットを減らすだけで改善するはずです。
            if (empty($data['data'])) {
                // ここで404が出ているのが原因
                return response()->json(['error' => 'No meme found'], 404);
            }

            return response()->json($data['data'][0]);
        }

        return response()->json(['error' => 'GIPHY API Error'], 500);
    }
}