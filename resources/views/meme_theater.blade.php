<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEME THEATER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
        
        body {
            background-color: #000;
            color: #0f0;
            font-family: 'Courier New', Courier, monospace;
            overflow: hidden;
        }

        /* --- ホームへ戻るボタン（サイバー風） --- */
        .home-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 15px;
            background: #000;
            border: 2px solid #0f0;
            color: #0f0;
            font-family: 'Press Start 2P', cursive;
            font-size: 12px;
            text-decoration: none;
            text-transform: uppercase;
            box-shadow: 3px 3px 0px #0f0;
            transition: transform 0.1s, box-shadow 0.1s;
            z-index: 50;
        }
        .home-btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px #0f0;
            background: #0f0;
            color: #000;
        }

        .curtain {
            transition: transform 1s ease-in-out;
            z-index: 10;
        }
        .curtain-left { transform-origin: top left; }
        .curtain-right { transform-origin: top right; }
        
        .spotlight {
            background: radial-gradient(circle, rgba(255,255,255,0.4) 0%, rgba(0,0,0,0) 70%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .glitch-text {
            text-shadow: 2px 0 #f0f, -2px 0 #0ff;
            animation: glitch 1s infinite alternate;
        }

        @keyframes glitch {
            0% { text-shadow: 2px 0 #f0f, -2px 0 #0ff; }
            25% { text-shadow: -2px 0 #ff0, 2px 0 #0f0; }
            50% { text-shadow: 1px 0 #f0f, -1px 0 #0ff; }
            100% { text-shadow: -1px 0 #ff0, 1px 0 #0f0; }
        }
    </style>
</head>
<body class="h-screen w-screen flex flex-col items-center justify-center">

    <a href="{{ url('/') }}" class="home-btn">
        < HOME
    </a>

    <div class="relative w-[800px] h-[500px] border-4 border-green-500 bg-gray-900 shadow-[0_0_20px_rgba(0,255,0,0.5)] overflow-hidden" id="stage">
        
        <div class="curtain curtain-left absolute top-0 left-0 w-1/2 h-full bg-green-900 border-r-2 border-black" id="c-left"></div>
        <div class="curtain curtain-right absolute top-0 right-0 w-1/2 h-full bg-green-900 border-l-2 border-black" id="c-right"></div>

        <div class="spotlight absolute top-0 left-1/4 w-1/2 h-full transform -rotate-12" id="spot-1"></div>
        <div class="spotlight absolute top-0 right-1/4 w-1/2 h-full transform rotate-12" id="spot-2"></div>

        <div class="absolute inset-0 flex items-center justify-center p-10">
            <img id="meme-img" class="max-w-full max-h-full object-contain opacity-0 transition-opacity duration-500" src="" alt="Meme">
        </div>

        <div id="loading-text" class="absolute inset-0 flex items-center justify-center text-green-400 font-bold text-2xl hidden">
            <span class="glitch-text">ACCESSING MEME DATABASE...</span>
        </div>
    </div>

    <div class="mt-8 text-center">
        <button onclick="startShow()" class="px-8 py-4 bg-black border-2 border-green-500 text-green-500 font-bold text-xl hover:bg-green-500 hover:text-black transition-colors shadow-[4px_4px_0_#00ff00] active:shadow-[1px_1px_0_#00ff00] active:translate-x-[3px] active:translate-y-[3px]">
            GENERATE MEME
        </button>
        <p id="status" class="mt-4 text-sm text-gray-500">SYSTEM READY</p>
    </div>

    <script>
        const leftCurtain = document.getElementById('c-left');
        const rightCurtain = document.getElementById('c-right');
        const memeImg = document.getElementById('meme-img');
        const statusText = document.getElementById('status');
        const loadingText = document.getElementById('loading-text');
        const spot1 = document.getElementById('spot-1');
        const spot2 = document.getElementById('spot-2');

        let isRunning = false;

        async function startShow() {
            if (isRunning) return;
            isRunning = true;

            // 1. Reset & Close Curtains
            statusText.innerText = "INITIALIZING SEQUENCE...";
            memeImg.style.opacity = '0';
            leftCurtain.style.transform = 'translateX(0)';
            rightCurtain.style.transform = 'translateX(0)';
            spot1.style.opacity = '0';
            spot2.style.opacity = '0';

            await new Promise(r => setTimeout(r, 1200));

            // 2. Fetch Data (with fake loading time)
            loadingText.classList.remove('hidden');
            statusText.innerText = "DOWNLOADING...";

            try {
                // キャッシュ回避のために現在時刻を付与
                const [response, _] = await Promise.all([
                    fetch('/meme/fetch?t=' + new Date().getTime()), 
                    new Promise(r => setTimeout(r, 2000)) 
                ]);

                if (!response.ok) throw new Error('API Error');
                const data = await response.json();

                // Preload Image
                const imgUrl = data.images?.original?.url || data.images?.fixed_height?.url;
                memeImg.src = imgUrl;
                await new Promise((resolve, reject) => {
                    memeImg.onload = resolve;
                    memeImg.onerror = reject;
                });

                // 3. Open Curtains & Show
                loadingText.classList.add('hidden');
                statusText.innerText = "RENDER COMPLETE";
                
                leftCurtain.style.transform = 'translateX(-100%)';
                rightCurtain.style.transform = 'translateX(100%)';
                
                await new Promise(r => setTimeout(r, 500));
                
                memeImg.style.opacity = '1';
                spot1.style.opacity = '1';
                spot2.style.opacity = '1';

            } catch (e) {
                console.error(e);
                statusText.innerText = "ERROR: CONNECTION LOST";
                loadingText.classList.add('hidden');
            } finally {
                isRunning = false;
            }
        }
    </script>
</body>
</html>