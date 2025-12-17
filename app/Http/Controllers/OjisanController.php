<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OjisanController extends Controller
{
    // メイン画面表示
    public function index()
    {
        return view('ojisan');
    }

    // AJAX用：ランダムなおじさんを1人取得してJSONで返す
    public function fetchOne()
    {
        // 検索クエリ
        $query = 'middle aged man';
        
        // 毎回違う人が出るようにランダムなページ番号(1~100)を指定
        $randomPage = rand(1, 100);

        $response = Http::withHeaders([
            'Authorization' => 'Client-ID ' . config('services.unsplash.access_key'),
            'Accept-Version' => 'v1',
        ])->get('https://api.unsplash.com/search/photos', [
            'query'       => $query,
            'page'        => $randomPage, // ランダムページ
            'per_page'    => 1,           // 1人だけ取得
            'orientation' => 'landscape',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // 1件もなければエラーを返す
            if (empty($data['results'])) {
                return response()->json(['error' => 'No ojisan found'], 404);
            }
            return response()->json($data['results'][0]);
        }

        return response()->json(['error' => 'API Error'], 500);
    }
}