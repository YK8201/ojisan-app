<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEME THEATER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bangers&display=swap'); /* アメコミ風フォント */

        body {
            background-color: #0d0d0d;
            font-family: 'Bangers', cursive; /* フォント変更 */
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: #fff;
        }

        /* --- 劇場（ステージ） --- */
        .stage-container {
            position: relative;
            width: 800px;
            max-width: 90vw;
            height: 500px;
            max-height: 60vh;
            border: 10px solid #ff0055; /* ポップなネオンピンク枠 */
            box-shadow: 0 0 40px rgba(255, 0, 85, 0.4);
            background-color: #000;
            overflow: hidden;
            border-radius: 8px;
        }

        /* --- 幕 --- */
        .curtain {
            position: absolute;
            top: 0; width: 50%; height: 100%;
            background: repeating-linear-gradient(90deg, #330000 0%, #cc0000 5%, #ff0000 10%); /* 明るい赤 */
            z-index: 20;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); /* バウンドして開く */
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }
        .curtain-left { left: 0; transform-origin: left top; }
        .curtain-right { right: 0; transform-origin: right top; }

        .stage-container.open .curtain-left { transform: translateX(-100%); }
        .stage-container.open .curtain-right { transform: translateX(100%); }

        /* --- スポットライト --- */
        .spotlight-layer {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 30; pointer-events: none; opacity: 0;
            transition: opacity 0.5s; mix-blend-mode: overlay;
        }
        .spotlight-beam {
            position: absolute; width: 180px; height: 180px;
            background: radial-gradient(circle, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            filter: blur(15px);
        }
        .searching .spotlight-layer { opacity: 1; }
        /* 動きを激しくする */
        .searching .beam-1 { animation: searchMotion1 1.5s infinite ease-in-out alternate; }
        .searching .beam-2 { animation: searchMotion2 1.8s infinite ease-in-out alternate-reverse; }

        @keyframes searchMotion1 {
            0%   { transform: translate(-200%, -100%) scale(1); }
            100% { transform: translate(150%, 150%) scale(1.2); }
        }
        @keyframes searchMotion2 {
            0%   { transform: translate(200%, -50%) scale(0.8); }
            100% { transform: translate(-150%, 80%) scale(1.3); }
        }

        /* --- おじさんGIFエリア --- */
        .actor-area {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            z-index: 10; opacity: 0; transition: opacity 0.5s;
            background: radial-gradient(circle, #222 0%, #000 100%);
        }
        .stage-container.open .actor-area { opacity: 1; }

        /* --- ボタン --- */
        .control-panel { margin-top: 40px; }
        .btn-start {
            background: #ff0055;
            border: 4px solid #fff;
            color: #fff;
            padding: 15px 40px; font-size: 1.5rem; letter-spacing: 2px;
            cursor: pointer; border-radius: 10px;
            box-shadow: 5px 5px 0px #88002d;
            transition: all 0.1s;
        }
        .btn-start:active { transform: translate(4px, 4px); box-shadow: 0px 0px 0px; }
        .btn-start:disabled { background: #555; border-color: #888; box-shadow: none; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="text-center mb-6">
        <h1 class="text-5xl tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-yellow-500 drop-shadow-md">
            MEME THEATER
        </h1>
        <p class="text-gray-400 text-sm font-sans mt-2">Powered by GIPHY</p>
    </div>

    <div id="stage" class="stage-container">
        <div class="actor-area">
            <img id="ojisan-img" src="" alt="Waiting..." class="max-w-full max-h-full object-contain">
            
            <div class="absolute bottom-2 right-2 text-white/70 text-sm font-sans bg-black/60 px-2 py-1 rounded">
                <span id="meme-title"></span>
            </div>
        </div>
        <div class="curtain curtain-left"></div>
        <div class="curtain curtain-right"></div>
        <div id="spotlight" class="spotlight-layer">
            <div class="spotlight-beam beam-1"></div>
            <div class="spotlight-beam beam-2"></div>
        </div>
    </div>

    <div class="control-panel">
        <button id="summon-btn" onclick="startShow()" class="btn-start">
            SHOW ME MEME!
        </button>
    </div>

    <script>
        const stage = document.getElementById('stage');
        const img = document.getElementById('ojisan-img');
        const titleEl = document.getElementById('meme-title');
        const btn = document.getElementById('summon-btn');

        // ★音声設定（ご自身のファイルパスに合わせてください）
        const drumAudio = new Audio('https://actions.google.com/sounds/v1/foley/rhythmic_panting.ogg'); // 仮素材
        const cymbalAudio = new Audio('https://actions.google.com/sounds/v1/cartoon/clang_and_wobble.ogg'); // 仮素材
        drumAudio.loop = true;

        async function startShow() {
            // 1. リセット
            if (stage.classList.contains('open')) {
                stage.classList.remove('open');
                btn.disabled = true;
                await new Promise(r => setTimeout(r, 800)); // 幕が閉まるのを待つ
            } else {
                btn.disabled = true;
            }

            // 2. 演出開始
            stage.classList.add('searching');
            drumAudio.currentTime = 0;
            drumAudio.play().catch(e => console.log('Audio error:', e));

            try {
                // 3. API取得 (MEME用エンドポイント) & 待機
                // URLの後ろに ?t=時刻 をつけて、毎回違うURLに見せかける（キャッシュ回避）
                const [response, _] = await Promise.all([
                    fetch('/meme/fetch?t=' + new Date().getTime()), 
                    new Promise(r => setTimeout(r, 3000)) 
                ]);

                if (!response.ok) throw new Error('Network error');
                const data = await response.json();

                // 4. 画像プリロード (GIFの読み込み待ち)
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    // GIPHYのレスポンス構造に合わせてURLを取得
                    // data.images.original.url がGIFの本体
                    img.src = data.images.original.url;
                });

                // タイトル表示 (なければUsername)
                titleEl.textContent = data.title || data.username || "Unknown Meme";

                // 5. 準備完了
                stage.classList.remove('searching');
                drumAudio.pause();
                cymbalAudio.currentTime = 0;
                cymbalAudio.play().catch(e => console.log('Audio error:', e));

                // 6. 幕オープン
                stage.classList.add('open');
                
                // 7. 紙吹雪 (少しポップな色に変更)
                fireConfetti();

            } catch (error) {
                console.error(error);
                alert('ミーム取得失敗！');
                stage.classList.remove('searching');
                drumAudio.pause();
            } finally {
                btn.disabled = false;
                btn.innerText = "NEXT MEME";
            }
        }

        function fireConfetti() {
            setTimeout(() => {
                confetti({
                    particleCount: 200,
                    spread: 120,
                    origin: { y: 0.6 },
                    // ポップな色合い
                    colors: ['#ff0055', '#00ddff', '#ffff00', '#ffffff'] 
                });
            }, 100);
        }
    </script>
</body>
</html>