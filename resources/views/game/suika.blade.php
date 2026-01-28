<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ojisan Game</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.19.0/matter.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* --- 全体のレイアウト --- */
        .main-wrapper {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        /* --- 左側：ゲームフィールド --- */
        #game-container {
            position: relative;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
            background-color: #444;
            border-radius: 4px;
        }

        /* --- 右側：サイドバー --- */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
            align-items: center;
        }

        /* スコアパネル */
        .score-panel {
            background-color: #fff;
            padding: 15px 40px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 220px;
        }

        .score-label {
            font-size: 14px;
            color: #888;
            margin-bottom: 5px;
            font-weight: bold;
        }

        #score {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }

        /* --- 進化の輪 --- */
        #evolution-container {
            position: relative;
            width: 320px;
            height: 320px;
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .evolution-ring {
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: conic-gradient(
                from 0deg,
                #ff6b6b, #feca57, #48dbfb, #1dd1a1, #ff6b6b
            );
            -webkit-mask: radial-gradient(transparent 64%, black 65%);
            mask: radial-gradient(transparent 64%, black 65%);
            opacity: 0.8;
        }

        .evolution-title {
            position: absolute;
            top: 15px;
            left: 20px;
            font-size: 16px;
            font-weight: bold;
            color: #888;
        }

        .evo-item {
            position: absolute;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            background-color: #fff;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .evo-item:hover {
            transform: scale(1.2);
            z-index: 10;
        }

        .arrow-decoration {
            position: absolute;
            font-size: 60px;
            color: rgba(0, 0, 0, 0.1);
            font-weight: bold;
            pointer-events: none;
        }

        /* --- デバッグボタン（新規追加） --- */
        #debug-btn {
            background-color: #555;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #debug-btn:hover {
            background-color: #777;
        }
        #debug-btn.active {
            background-color: #4CAF50; /* ONのときは緑色 */
        }
        /* インジケーター */
        .status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background-color: #999;
        }
        #debug-btn.active .status-dot {
            background-color: #fff;
            box-shadow: 0 0 5px #fff;
        }


        /* --- ゲーム内のオーバーレイ要素 --- */
        #loading-screen {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: #444; display: flex; justify-content: center; align-items: center;
            color: white; font-size: 24px; z-index: 200;
        }
        #danger-line {
            position: absolute; top: 150px; left: 0; width: 100%; height: 2px;
            background-color: rgba(255, 0, 0, 0.5); pointer-events: none; display: block; z-index: 10;
        }
        #danger-line::after {
            content: "DEAD LINE"; position: absolute; right: 5px; top: -14px;
            color: rgba(255, 0, 0, 0.8); font-size: 10px; font-weight: bold;
        }
        #game-over {
            display: none; position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); background: rgba(255, 255, 255, 0.95);
            padding: 30px 50px; border-radius: 15px; text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3); z-index: 100; min-width: 200px;
        }
        #game-over h2 { margin-top: 0; color: #e74c3c; font-size: 32px; }
        #final-score { font-size: 24px; margin: 10px 0; color: #333; }
        .retry-btn {
            background-color: #4CAF50; color: white; border: none; padding: 12px 24px;
            font-size: 18px; cursor: pointer; border-radius: 50px; margin-top: 15px;
            transition: background-color 0.3s;
        }
        .retry-btn:hover { background-color: #45a049; }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <div id="game-container">
            <div id="loading-screen">Loading Assets...</div>
            <div id="danger-line"></div>
            <div id="game-over">
                <h2>GAME OVER</h2>
                <div id="final-score">Score: 0</div>
                <button class="retry-btn" onclick="location.reload()">RETRY</button>
            </div>
        </div>

        <div class="sidebar">
            <div class="score-panel">
                <div class="score-label">CURRENT SCORE</div>
                <div id="score">0</div>
            </div>

            <div id="evolution-container">
                <div class="evolution-title">シンカの輪</div>
                <div class="evolution-ring"></div>
                <div class="arrow-decoration">↻</div>
                </div>

            <button id="debug-btn" class="active" onclick="toggleDebug()">
                <div class="status-dot"></div>
                当たり判定を表示
            </button>
        </div>
    </div>

    <script>
        // --- 設定エリア ---
        const FRUITS = [
            { name: 'lv1',  radius: 15, score: 0,    color: '#ffe0bd', image: "{{ asset('images/01.png') }}" },
            { name: 'lv2',  radius: 25, score: 2,    color: '#ffcd94', image: "{{ asset('images/02.png') }}" },
            { name: 'lv3',  radius: 35, score: 4,    color: '#eac086', image: "{{ asset('images/03.png') }}" },
            { name: 'lv4',  radius: 45, score: 8,    color: '#ffad60', image: "{{ asset('images/04.png') }}" },
            { name: 'lv5',  radius: 58, score: 16,   color: '#ffe5b4', image: "{{ asset('images/05.png') }}" },
            { name: 'lv6',  radius: 72, score: 32,   color: '#ffcc99', image: "{{ asset('images/06.png') }}" },
            { name: 'lv7',  radius: 88, score: 64,   color: '#e1ad01', image: "{{ asset('images/07.png') }}" },
            { name: 'lv8',  radius: 105, score: 128,  color: '#d4af37', image: "{{ asset('images/08.png') }}" },
            { name: 'lv9',  radius: 125, score: 256,  color: '#c5a000', image: "{{ asset('images/09.png') }}" },
            { name: 'lv10', radius: 145, score: 512,  color: '#b8860b', image: "{{ asset('images/10.png') }}" },
            { name: 'lv11', radius: 165, score: 1024, color: '#a0522d', image: "{{ asset('images/11.png') }}" },
        ];

        const WIDTH = 600;
        const HEIGHT = 800;
        const WALL_THICKNESS = 20;
        const DEADLINE_Y = 150; 

        // デバッグ表示フラグ (デフォルトON)
        let showDebugWireframes = true;

        // --- 画像読み込みチェック ---
        function preloadImages(callback) {
            let loadedCount = 0;
            const total = FRUITS.length;
            FRUITS.forEach(fruit => {
                if (!fruit.image) { loadedCount++; if (loadedCount === total) callback(); return; }
                const img = new Image(); img.src = fruit.image;
                img.onload = () => { fruit.actualWidth = img.naturalWidth; fruit.actualHeight = img.naturalHeight; loadedCount++; if (loadedCount === total) callback(); };
                img.onerror = () => { console.warn(`Image not found: ${fruit.image}`); fruit.image = null; loadedCount++; if (loadedCount === total) callback(); };
            });
        }

        // --- 進化の輪 ---
        function createEvolutionCircle() {
            const container = document.getElementById('evolution-container');
            const total = FRUITS.length;
            const radius = 110; const centerX = 160; const centerY = 160;

            FRUITS.forEach((fruit, index) => {
                const img = document.createElement('img');
                if (fruit.image) img.src = fruit.image;
                else { img.style.backgroundColor = fruit.color; img.src = ''; }
                img.className = 'evo-item';
                const angle = (index / total) * 2 * Math.PI - (Math.PI / 2);
                const x = centerX + radius * Math.cos(angle);
                const y = centerY + radius * Math.sin(angle);
                const size = 30 + (index * 2.5); 
                img.style.width = size + 'px'; img.style.height = size + 'px';
                img.style.left = x + 'px'; img.style.top = y + 'px';
                img.style.marginLeft = -(size / 2) + 'px'; img.style.marginTop = -(size / 2) + 'px';
                container.appendChild(img);
            });
        }

        // --- トグル機能 ---
        function toggleDebug() {
            showDebugWireframes = !showDebugWireframes;
            const btn = document.getElementById('debug-btn');
            if (showDebugWireframes) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }

        // --- Matter.js 初期化 ---
        const Engine = Matter.Engine, Render = Matter.Render, Runner = Matter.Runner,
              Bodies = Matter.Bodies, Composite = Matter.Composite, Events = Matter.Events,
              World = Matter.World, Body = Matter.Body;

        const engine = Engine.create();
        const world = engine.world;

        const render = Render.create({
            element: document.getElementById('game-container'),
            engine: engine,
            options: {
                width: WIDTH, height: HEIGHT,
                wireframes: false, background: '#444' 
            }
        });

        // --- 描画ループ後の処理（当たり判定の描画） ---
        Events.on(render, 'afterRender', function() {
            // フラグがOFFなら何もしない
            if (!showDebugWireframes) return;

            const ctx = render.context;
            ctx.globalAlpha = 0.5;
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#00FF00';

            Composite.allBodies(world).forEach(body => {
                if (body.label === 'fruit') {
                    ctx.beginPath();
                    ctx.arc(body.position.x, body.position.y, body.circleRadius, 0, 2 * Math.PI);
                    ctx.stroke();
                }
            });
            ctx.globalAlpha = 1.0;
        });

        // --- 壁 ---
        const wallOptions = { isStatic: true, label: 'wall', render: { fillStyle: '#666' } };
        World.add(world, [
            Bodies.rectangle(WIDTH / 2, HEIGHT, WIDTH, WALL_THICKNESS * 2, wallOptions),
            Bodies.rectangle(0, HEIGHT / 2, WALL_THICKNESS, HEIGHT, wallOptions),
            Bodies.rectangle(WIDTH, HEIGHT / 2, WALL_THICKNESS, HEIGHT, wallOptions)
        ]);

        // --- ゲームロジック ---
        let currentFruit = null, isClickable = true, isGameOver = false, score = 0, gameOverTimer = 0;
        const scoreElement = document.getElementById('score');
        const finalScoreElement = document.getElementById('final-score');
        const gameOverElement = document.getElementById('game-over');
        const loadingElement = document.getElementById('loading-screen');

        function createFruit(x, y, index, isStatic = false) {
            const fruitInfo = FRUITS[index];
            let renderOptions = {};
            if (fruitInfo.image && fruitInfo.actualWidth && fruitInfo.actualHeight) {
                const targetDiameter = fruitInfo.radius * 2;
                renderOptions = {
                    sprite: {
                        texture: fruitInfo.image,
                        xScale: targetDiameter / fruitInfo.actualWidth,
                        yScale: targetDiameter / fruitInfo.actualHeight
                    }
                };
            } else { renderOptions = { fillStyle: fruitInfo.color }; }

            return Bodies.circle(x, y, fruitInfo.radius, {
                label: 'fruit', isStatic: isStatic, isSensor: isStatic,
                restitution: 0.2, render: renderOptions, customIndex: index
            });
        }

        function prepareNextFruit() {
            if (isGameOver) return;
            const randomIndex = Math.floor(Math.random() * 5); 
            currentFruit = createFruit(WIDTH / 2, 50, randomIndex, true);
            World.add(world, currentFruit);
        }

        const container = document.getElementById('game-container');
        container.addEventListener('mousemove', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;
            const rect = container.getBoundingClientRect();
            let x = e.clientX - rect.left;
            const r = currentFruit.circleRadius;
            if (x < r + WALL_THICKNESS) x = r + WALL_THICKNESS;
            if (x > WIDTH - r - WALL_THICKNESS) x = WIDTH - r - WALL_THICKNESS;
            Body.setPosition(currentFruit, { x: x, y: 50 });
        });
        container.addEventListener('click', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;
            isClickable = false;
            Body.set(currentFruit, { isStatic: false, isSensor: false });
            currentFruit = null; 
            setTimeout(() => { isClickable = true; prepareNextFruit(); }, 1000);
        });

        Events.on(engine, 'collisionStart', (event) => {
            if (isGameOver) return;
            event.pairs.forEach((pair) => {
                const bodyA = pair.bodyA, bodyB = pair.bodyB;
                if (bodyA.label === 'fruit' && bodyB.label === 'fruit') {
                    if (bodyA.customIndex === bodyB.customIndex) {
                        const index = bodyA.customIndex;
                        if (bodyA.isRemoved || bodyB.isRemoved) return;
                        if (index === FRUITS.length - 1) {
                            bodyA.isRemoved = true; bodyB.isRemoved = true;
                            World.remove(world, [bodyA, bodyB]);
                            score += FRUITS[index].score * 2; scoreElement.innerText = score; return; 
                        }
                        bodyA.isRemoved = true; bodyB.isRemoved = true;
                        World.remove(world, [bodyA, bodyB]);
                        const newX = (bodyA.position.x + bodyB.position.x) / 2;
                        const newY = (bodyA.position.y + bodyB.position.y) / 2;
                        World.add(world, createFruit(newX, newY, index + 1));
                        score += FRUITS[index + 1].score; scoreElement.innerText = score;
                    }
                }
            });
        });

        Events.on(engine, 'afterUpdate', () => {
            if (isGameOver) return;
            let isDanger = false;
            Composite.allBodies(world).forEach(body => {
                if (body.label === 'fruit' && !body.isStatic && !body.isSensor) {
                    if (body.position.y < DEADLINE_Y && body.speed < 0.2) isDanger = true;
                }
            });
            if (isDanger) {
                gameOverTimer++;
                if (gameOverTimer > 180) { isGameOver = true; showGameOver(); }
            } else { gameOverTimer = 0; }
        });

        function showGameOver() {
            finalScoreElement.innerText = "Score: " + score;
            gameOverElement.style.display = 'block'; isClickable = false; 
        }

        preloadImages(() => {
            loadingElement.style.display = 'none';
            createEvolutionCircle();
            Render.run(render); const runner = Runner.create(); Runner.run(runner, engine);
            prepareNextFruit();
        });
    </script>
</body>
</html>