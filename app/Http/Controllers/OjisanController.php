<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OjisanController extends Controller
{
    // メイン画面（おじさんシアター）
    // ★ここを 'ojisan' に戻します
    public function index()
    {
        return view('ojisan');
    }

    // MEME THEATER用画面（新規追加）
    // ★新しくメソッドを作ります
    public function meme()
    {
        return view('meme_theater');
    }

    // Unsplash用API（おじさんシアター用）
    // ★もしコメントアウトされていたら復活させてください
    public function fetchOne()
    {
        // 検索クエリ
        $query = 'middle aged man';
        
        // 毎回違う人が出るようにランダムなページ番号を指定
        $randomPage = rand(1, 100);

        $response = Http::withHeaders([
            'Authorization' => 'Client-ID ' . config('services.unsplash.access_key'), // .envのUNSPLASH_ACCESS_KEYを使用
            'Accept-Version' => 'v1',
        ])->get('https://api.unsplash.com/search/photos', [
            'query'       => $query,
            'page'        => $randomPage,
            'per_page'    => 1,
            'orientation' => 'landscape',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (empty($data['results'])) {
                return response()->json(['error' => 'No ojisan found'], 404);
            }
            return response()->json($data['results'][0]);
        }

        return response()->json(['error' => 'API Error'], 500);
    }

    // GIPHY用API（MEME THEATER用）
    public function fetchMeme()
    {
        $apiKey = env('GIPHY_API_KEY');
        $query = 'meme'; 

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
            
            if (empty($data['data'])) {
                return response()->json(['error' => 'No meme found'], 404);
            }

            return response()->json($data['data'][0]);
        }

        return response()->json(['error' => 'GIPHY API Error'], 500);
    }
}