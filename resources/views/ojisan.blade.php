<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The OJISAN Stage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap');

        body {
            background-color: #1a1a1a;
            font-family: 'Cinzel', serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        /* --- 劇場（ステージ） --- */
        .stage-container {
            position: relative;
            width: 800px;
            max-width: 90vw;
            height: 500px;
            max-height: 60vh;
            border: 8px solid #c5a059;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
            background-color: #000;
            overflow: hidden;
            border-radius: 4px;
        }

        /* --- 幕 --- */
        .curtain {
            position: absolute;
            top: 0; width: 50%; height: 100%;
            background: repeating-linear-gradient(90deg, #800000 0%, #600000 5%, #900000 10%);
            z-index: 20;
            transition: transform 0.8s cubic-bezier(0.4, 0.0, 0.2, 1); /* 開く速度を少し調整 */
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
        .searching .beam-1 { animation: searchMotion1 3s infinite ease-in-out alternate; }
        .searching .beam-2 { animation: searchMotion2 3.5s infinite ease-in-out alternate-reverse; }

        @keyframes searchMotion1 {
            0%   { transform: translate(-200%, -100%) scale(1); }
            30%  { transform: translate(50%, 50%) scale(1.2); }
            60%  { transform: translate(-80%, 80%) scale(0.9); }
            100% { transform: translate(150%, -50%) scale(1.1); }
        }
        @keyframes searchMotion2 {
            0%   { transform: translate(150%, -100%) scale(1); }
            40%  { transform: translate(-100%, 20%) scale(1.3); }
            70%  { transform: translate(50%, -60%) scale(0.8); }
            100% { transform: translate(-150%, 80%) scale(1.1); }
        }

        /* --- おじさんエリア --- */
        .actor-area {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            z-index: 10; opacity: 0; transition: opacity 0.5s;
        }
        .stage-container.open .actor-area { opacity: 1; }

        /* --- ボタン --- */
        .control-panel { margin-top: 40px; }
        .btn-start {
            background: linear-gradient(to bottom, #d4af37, #aa8c2c);
            border: 2px solid #fff; color: #3e2723;
            padding: 15px 40px; font-size: 1.2rem; font-weight: bold;
            cursor: pointer; border-radius: 50px;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
            transition: all 0.2s;
        }
        .btn-start:active { transform: scale(0.95); }
        .btn-start:disabled { background: #555; color: #999; cursor: not-allowed; border-color: #777; box-shadow: none; }
    </style>
</head>
<body>
    <div class="text-center mb-6">
        <h1 class="text-4xl text-yellow-500 tracking-widest drop-shadow-md">OJISAN THEATER</h1>
    </div>

    <div id="stage" class="stage-container">
        <div class="actor-area">
            <img id="ojisan-img" src="" alt="Waiting..." class="max-w-full max-h-full object-contain shadow-2xl">
            <div class="absolute bottom-2 right-2 text-white/50 text-xs bg-black/50 px-2 py-1 rounded">
                Photo by <span id="credit-name"></span>
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
            OPEN THE CURTAIN
        </button>
    </div>

    <script>
        const stage = document.getElementById('stage');
        const img = document.getElementById('ojisan-img');
        const credit = document.getElementById('credit-name');
        const btn = document.getElementById('summon-btn');

        // --- オーディオ設定 ---
        // public/sounds/ にファイルを置いてください
        // ファイルがない場合のテスト用URL（動作確認用）
        // 本番では '/sounds/drumroll.mp3' などに書き換えてください
        const drumAudio = new Audio('/sounds/drumroll.mp3');
        const cymbalAudio = new Audio('/sounds/cymbal.mp3');
        // ※↑同様に 'sounds/cymbal.mp3' に書き換えてください。

        // 設定：ループさせる
        drumAudio.loop = true;
        drumAudio.volume = 0.6;
        cymbalAudio.volume = 0.8;


        async function startShow() {
            // 1. 状態リセット
            if (stage.classList.contains('open')) {
                stage.classList.remove('open');
                btn.disabled = true;
                await new Promise(r => setTimeout(r, 1000));
            } else {
                btn.disabled = true;
            }

            // 2. 演出開始
            stage.classList.add('searching');
            
            // ★音：ドラムロール開始
            // ユーザー操作(クリック)直後なので再生可能です
            drumAudio.currentTime = 0;
            drumAudio.play().catch(e => console.log('Audio play error:', e));

            try {
                // 3. API取得 & 待機 (3秒)
                const [response, _] = await Promise.all([
                    fetch('/ojisan/fetch'),
                    new Promise(r => setTimeout(r, 3000)) 
                ]);

                if (!response.ok) throw new Error('Network error');
                const data = await response.json();

                // 4. 画像プリロード
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = data.urls.regular;
                });

                credit.textContent = data.user.name;

                // 5. 準備完了
                stage.classList.remove('searching');

                // ★音：ドラムロール停止 & シンバル再生
                drumAudio.pause();
                drumAudio.currentTime = 0; // 頭出し
                cymbalAudio.play().catch(e => console.log('Audio play error:', e));

                // 6. 幕を開ける
                stage.classList.add('open');
                
                // 7. 紙吹雪
                fireConfetti();

            } catch (error) {
                console.error(error);
                alert('エラーが発生しました。');
                stage.classList.remove('searching');
                drumAudio.pause();
            } finally {
                btn.disabled = false;
                btn.innerText = "NEXT OJISAN";
            }
        }

        function fireConfetti() {
            setTimeout(() => {
                confetti({
                    particleCount: 150,
                    spread: 100,
                    origin: { y: 0.6 },
                    colors: ['#FFD700', '#FFFFFF', '#FFA500']
                });
            }, 100); // シンバルとほぼ同時に発射
        }
    </script>
</body>
</html>