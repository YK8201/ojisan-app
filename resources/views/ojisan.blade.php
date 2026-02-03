<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ojisan Theater</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #1a0000;
            color: #ffd700;
            font-family: 'Cinzel', serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* --- ホームへ戻るボタン --- */
        .home-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            text-decoration: none;
            color: #ffd700;
            border: 2px solid #ffd700;
            padding: 10px 20px;
            font-size: 16px;
            background-color: rgba(50, 0, 0, 0.8);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
            letter-spacing: 2px;
        }

        .home-btn:hover {
            background-color: #ffd700;
            color: #1a0000;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
        }

        /* --- 劇場レイアウト --- */
        .stage {
            position: relative;
            width: 800px;
            height: 600px;
            background: #000;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            border: 10px solid #4a0000;
            overflow: hidden;
        }

        /* カーテン */
        .curtain {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, #800000 0%, #400000 50%, #800000 100%);
            background-size: 40px 100%;
            transition: transform 1.5s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 10;
            box-shadow: 5px 0 20px rgba(0,0,0,0.5);
        }
        .curtain.left { left: 0; transform-origin: top left; }
        .curtain.right { right: 0; transform-origin: top right; }

        .stage.open .curtain.left { transform: translateX(-90%); }
        .stage.open .curtain.right { transform: translateX(90%); }

        /* --- スポットライト --- */
        .spotlight {
            position: absolute;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
            /* フェードアウトを少しゆっくり(1s)にして余韻を残す */
            transition: opacity 1s, transform 1s;
            z-index: 20; 
            mix-blend-mode: screen;
            filter: blur(10px);
        }

        /* 初期位置 */
        .spotlight-1 { top: 20%; left: 30%; }
        .spotlight-2 { top: 30%; right: 30%; }
        
        /* 探索中 */
        .stage.searching .spotlight { opacity: 1; }
        .stage.searching .spotlight-1 { animation: search1 3s infinite alternate ease-in-out; }
        .stage.searching .spotlight-2 { animation: search2 4s infinite alternate ease-in-out; }

        /* ★変更点：おじさん登場時（フェードアウトして消える） */
        .stage.revealed .spotlight {
            opacity: 0; /* 完全に透明にする */
            width: 500px;
            height: 500px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) !important;
            animation: none;
        }

        @keyframes search1 {
            0%   { transform: translate(0, 0) scale(1); }
            33%  { transform: translate(100px, 150px) scale(1.1); }
            66%  { transform: translate(-50px, 200px) scale(0.9); }
            100% { transform: translate(-100px, 0) scale(1.2); }
        }

        @keyframes search2 {
            0%   { transform: translate(0, 0) scale(1.1); }
            33%  { transform: translate(-120px, 100px) scale(0.9); }
            66%  { transform: translate(80px, -50px) scale(1.2); }
            100% { transform: translate(50px, 150px) scale(1); }
        }

        /* おじさん画像エリア */
        #ojisan-img {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0;
            transition: opacity 0.5s;
            z-index: 5;
        }

        /* 操作パネル */
        .controls {
            margin-top: 30px;
            text-align: center;
        }

        .summon-btn {
            background: #800000;
            color: #fff;
            border: 2px solid #ffd700;
            padding: 15px 40px;
            font-size: 24px;
            font-family: 'Cinzel', serif;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .summon-btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .status {
            margin-top: 10px;
            height: 20px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>

    <a href="{{ url('/') }}" class="home-btn">
        ← EXIT THEATER
    </a>

    <div class="stage" id="stage">
        <div class="curtain left"></div>
        <div class="curtain right"></div>
        
        <div class="spotlight spotlight-1"></div>
        <div class="spotlight spotlight-2"></div>
        
        <img id="ojisan-img" src="" alt="Ojisan">
    </div>

    <div class="controls">
        <button class="summon-btn" onclick="summonOjisan()">SUMMON OJISAN</button>
        <div class="status" id="status-text">Click button to start show</div>
    </div>

    <script>
        const drumRoll = new Audio('/sounds/drumroll.mp3');
        const cymbal = new Audio('/sounds/cymbal.mp3');
        
        let isActivating = false;

        async function summonOjisan() {
            if (isActivating) return;
            isActivating = true;

            const stage = document.getElementById('stage');
            const img = document.getElementById('ojisan-img');
            const status = document.getElementById('status-text');

            // 1. リセット
            stage.classList.remove('open', 'revealed', 'searching');
            img.style.opacity = 0;
            status.innerText = "Closing curtains...";
            
            // 閉まる待機
            await new Promise(r => setTimeout(r, 1000));

            // 2. サーチ開始
            status.innerText = "Searching for talent...";
            drumRoll.currentTime = 0;
            drumRoll.loop = true;
            drumRoll.play().catch(e => {}); 
            
            stage.classList.add('searching');

            try {
                // 3. API取得
                const [response, _] = await Promise.all([
                    fetch('/ojisan/fetch'), 
                    new Promise(r => setTimeout(r, 2500)) 
                ]);

                if (!response.ok) throw new Error('Summon failed');
                const data = await response.json();
                
                img.src = data.urls.regular;
                
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                });

                // 4. お披露目
                drumRoll.pause();
                cymbal.currentTime = 0;
                cymbal.play().catch(e => {});

                stage.classList.remove('searching');
                stage.classList.add('open', 'revealed'); // ここでCSSによりスポットライトが消える
                img.style.opacity = 1;
                status.innerText = "The Ojisan has arrived!";

            } catch (error) {
                console.error(error);
                drumRoll.pause();
                status.innerText = "Summoning failed... (Check API)";
                stage.classList.remove('searching');
            } finally {
                isActivating = false;
            }
        }
    </script>
</body>
</html>