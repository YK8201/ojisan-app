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

        .main-wrapper {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        #game-container {
            position: relative;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
            background-color: #444;
            border-radius: 4px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
            align-items: center;
        }

        .score-panel {
            background-color: #fff;
            padding: 15px 40px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 220px;
            transition: background-color 0.2s; /* 色変化用 */
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
            transition: color 0.2s;
        }

        /* ペナルティ演出用クラス */
        .score-panel.penalty {
            background-color: #ffcccc;
            transform: scale(1.1);
        }
        #score.penalty {
            color: #ff0000;
        }

        /* ペナルティポップアップ */
        .penalty-popup {
            position: absolute;
            color: #ff0000;
            font-weight: bold;
            font-size: 24px;
            pointer-events: none;
            animation: floatUp 1s ease-out forwards;
            text-shadow: 2px 2px 0 #fff;
            z-index: 150;
        }

        @keyframes floatUp {
            0% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-50px) scale(1.5); }
        }

        .next-panel {
            background-color: #fff;
            padding: 15px;
            width: 120px;
            height: 140px;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative; 
        }

        .next-label {
            font-size: 14px;
            color: #888;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .next-fruit-circle {
            width: 80px;
            height: 80px;
            background-color: #f9f9f9;
            border-radius: 50%;
            margin-top: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            border: 2px solid #eee;
            position: relative;
        }

        #next-fruit-img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            display: none;
        }
        
        #next-superball-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(255, 0, 255, 0.3);
            border-radius: 50%;
            display: none;
            pointer-events: none;
        }

        #next-bomb-badge {
            position: absolute; bottom: 5px; right: 5px; font-size: 24px; display: none;
            z-index: 10; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.5));
        }

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
            position: absolute; top: 15px; left: 20px; font-size: 16px; font-weight: bold; color: #888;
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
        .evo-item:hover { transform: scale(1.2); z-index: 10; }

        .arrow-decoration {
            position: absolute; font-size: 60px; color: rgba(0, 0, 0, 0.1); font-weight: bold; pointer-events: none;
        }

        #debug-btn {
            background-color: #555; color: #fff; border: none; padding: 10px 20px;
            border-radius: 20px; font-size: 14px; cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2); transition: all 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        #debug-btn:hover { background-color: #777; }
        #debug-btn.active { background-color: #4CAF50; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background-color: #999; }
        #debug-btn.active .status-dot { background-color: #fff; box-shadow: 0 0 5px #fff; }

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
            <div class="score-panel" id="score-panel">
                <div class="score-label">CURRENT SCORE</div>
                <div id="score">0</div>
            </div>

            <div class="next-panel">
                <div class="next-label">NEXT</div>
                <div class="next-fruit-circle">
                    <img id="next-fruit-img" src="" alt="Next">
                    <div id="next-superball-overlay"></div>
                </div>
                <div id="next-bomb-badge">💣</div>
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
        const FRUITS = [
            { name: 'lv1',  radius: 15, score: 10,   color: '#ffe0bd', image: "{{ asset('images/01.png') }}" },
            { name: 'lv2',  radius: 25, score: 20,   color: '#ffcd94', image: "{{ asset('images/02.png') }}" },
            { name: 'lv3',  radius: 35, score: 40,   color: '#eac086', image: "{{ asset('images/03.png') }}" },
            { name: 'lv4',  radius: 45, score: 80,   color: '#ffad60', image: "{{ asset('images/04.png') }}" },
            { name: 'lv5',  radius: 58, score: 160,  color: '#ffe5b4', image: "{{ asset('images/05.png') }}" },
            { name: 'lv6',  radius: 72, score: 320,  color: '#ffcc99', image: "{{ asset('images/06.png') }}" },
            { name: 'lv7',  radius: 88, score: 640,  color: '#e1ad01', image: "{{ asset('images/07.png') }}" },
            { name: 'lv8',  radius: 105, score: 1280, color: '#d4af37', image: "{{ asset('images/08.png') }}" },
            { name: 'lv9',  radius: 125, score: 2560, color: '#c5a000', image: "{{ asset('images/09.png') }}" },
            { name: 'lv10', radius: 145, score: 5120, color: '#b8860b', image: "{{ asset('images/10.png') }}" },
            { name: 'lv11', radius: 165, score: 10240, color: '#a0522d', image: "{{ asset('images/11.png') }}" },
        ];
        // ペナルティ計算のため、スコアを少し増やしました

        const WIDTH = 600;
        const HEIGHT = 800;
        const WALL_THICKNESS = 20;
        const DEADLINE_Y = 150; 

        let showDebugWireframes = true;
        let nextFruitInfo = { index: 0, type: 'normal' };

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

        function createEvolutionCircle() {
            const container = document.getElementById('evolution-container');
            const total = FRUITS.length;
            const radius = 110; const centerX = 160; const centerY = 160;
            FRUITS.forEach((fruit, index) => {
                const img = document.createElement('img');
                if (fruit.image) img.src = fruit.image; else { img.style.backgroundColor = fruit.color; img.src = ''; }
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

        function toggleDebug() {
            showDebugWireframes = !showDebugWireframes;
            const btn = document.getElementById('debug-btn');
            if (showDebugWireframes) btn.classList.add('active'); else btn.classList.remove('active');
        }

        // 精度を上げるための設定を追加 (positionIterations, velocityIterations)
        const Engine = Matter.Engine, Render = Matter.Render, Runner = Matter.Runner,
              Bodies = Matter.Bodies, Composite = Matter.Composite, Events = Matter.Events,
              World = Matter.World, Body = Matter.Body, Vector = Matter.Vector;

        const engine = Engine.create({
            positionIterations: 10, // デフォルト6から増加
            velocityIterations: 10  // デフォルト4から増加
        });
        const world = engine.world;
        const render = Render.create({
            element: document.getElementById('game-container'),
            engine: engine,
            options: { width: WIDTH, height: HEIGHT, wireframes: false, background: '#444' }
        });

        // --- 描画処理 ---
        Events.on(render, 'afterRender', function() {
            const ctx = render.context;
            
            // 1. スーパーボールのピンクオーバーレイ
            Composite.allBodies(world).forEach(body => {
                if (body.customType === 'superball') {
                    ctx.beginPath();
                    ctx.arc(body.position.x, body.position.y, body.circleRadius, 0, 2 * Math.PI);
                    ctx.fillStyle = 'rgba(255, 0, 255, 0.3)';
                    ctx.fill();
                    ctx.strokeStyle = '#FF00FF'; ctx.lineWidth = 2; ctx.stroke();
                }
            });

            // 2. 爆弾・カウントダウン
            Composite.allBodies(world).forEach(body => {
                if (body.customType === 'bomb') {
                    ctx.save();
                    ctx.translate(body.position.x, body.position.y);
                    if (body.isLanded && body.explosionTime) {
                        const timeLeft = Math.ceil((body.explosionTime - Date.now()) / 1000);
                        const pulse = (Date.now() % 500) < 250;
                        if (pulse) {
                            ctx.beginPath();
                            ctx.arc(0, 0, body.circleRadius + 5, 0, 2 * Math.PI);
                            ctx.strokeStyle = '#FF0000'; ctx.lineWidth = 4; ctx.stroke();
                        }
                        ctx.fillStyle = '#FF0000'; ctx.font = 'bold 32px Arial';
                        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                        ctx.shadowColor = 'white'; ctx.shadowBlur = 5;
                        ctx.fillText(timeLeft > 0 ? timeLeft : "!", 0, -5); 
                    } else {
                        ctx.fillStyle = '#000'; ctx.font = '32px Arial';
                        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                        ctx.fillText("💣", 0, -body.circleRadius - 10);
                    }
                    ctx.restore();
                }
            });

            // 3. デバッグワイヤーフレーム
            if (showDebugWireframes) {
                ctx.globalAlpha = 0.5; ctx.lineWidth = 2;
                Composite.allBodies(world).forEach(body => {
                    if (body.label === 'fruit') {
                        ctx.beginPath();
                        if (body.customType === 'fat') ctx.strokeStyle = '#FFA500'; 
                        else if (body.customType === 'bomb') ctx.strokeStyle = '#FF0000'; 
                        else if (body.customType === 'superball') ctx.strokeStyle = '#FF00FF'; 
                        else ctx.strokeStyle = '#00FF00';

                        if (body.vertices && body.vertices.length > 0) {
                            ctx.moveTo(body.vertices[0].x, body.vertices[0].y);
                            for (let j = 1; j < body.vertices.length; j++) ctx.lineTo(body.vertices[j].x, body.vertices[j].y);
                            ctx.lineTo(body.vertices[0].x, body.vertices[0].y);
                        } else {
                            ctx.arc(body.position.x, body.position.y, body.circleRadius, 0, 2 * Math.PI);
                        }
                        ctx.stroke();
                    }
                });
                ctx.globalAlpha = 1.0;
            }
        });

        // --- 壁の設定（壁抜け対策強化） ---
        const wallOptions = { isStatic: true, label: 'wall', render: { fillStyle: '#666' } };
        // 見えない壁（分厚い）
        const invisibleWallOptions = { isStatic: true, label: 'wall', render: { visible: false } };
        const EXTRA_THICKNESS = 1000;

        World.add(world, [
            // 通常の壁（見た目用）
            Bodies.rectangle(WIDTH / 2, HEIGHT, WIDTH, WALL_THICKNESS * 2, wallOptions),
            Bodies.rectangle(0, HEIGHT / 2, WALL_THICKNESS, HEIGHT, wallOptions),
            Bodies.rectangle(WIDTH, HEIGHT / 2, WALL_THICKNESS, HEIGHT, wallOptions),

            // ★壁抜け防止用の見えない激厚壁（外側に配置）★
            // 左側の激厚壁 (x = -500付近)
            Bodies.rectangle(0 - (EXTRA_THICKNESS / 2) - (WALL_THICKNESS / 2), HEIGHT / 2, EXTRA_THICKNESS, HEIGHT * 2, invisibleWallOptions),
            // 右側の激厚壁 (x = WIDTH + 500付近)
            Bodies.rectangle(WIDTH + (EXTRA_THICKNESS / 2) + (WALL_THICKNESS / 2), HEIGHT / 2, EXTRA_THICKNESS, HEIGHT * 2, invisibleWallOptions),
            // 下側の激厚壁
            Bodies.rectangle(WIDTH / 2, HEIGHT + (EXTRA_THICKNESS / 2) + WALL_THICKNESS, WIDTH * 2, EXTRA_THICKNESS, invisibleWallOptions)
        ]);

        let currentFruit = null, isClickable = true, isGameOver = false, score = 0, gameOverTimer = 0;
        const scoreElement = document.getElementById('score');
        const scorePanel = document.getElementById('score-panel');
        const finalScoreElement = document.getElementById('final-score');
        const gameOverElement = document.getElementById('game-over');
        const loadingElement = document.getElementById('loading-screen');
        const nextFruitImg = document.getElementById('next-fruit-img');
        const nextSuperballOverlay = document.getElementById('next-superball-overlay');
        const nextBombBadge = document.getElementById('next-bomb-badge');

        function createFruit(x, y, index, isStatic = false, type = 'normal') {
            const fruitInfo = FRUITS[index];
            let renderOptions = {};
            let restitution = 0.2;

            if (type === 'superball') restitution = 1.2;

            if (fruitInfo.image && fruitInfo.actualWidth && fruitInfo.actualHeight) {
                const targetDiameter = fruitInfo.radius * 2;
                let scaleX = targetDiameter / fruitInfo.actualWidth;
                let scaleY = targetDiameter / fruitInfo.actualHeight;
                if (type === 'fat') { scaleX *= 1.3; scaleY *= 0.7; }
                renderOptions = { sprite: { texture: fruitInfo.image, xScale: scaleX, yScale: scaleY } };
            } else { 
                renderOptions = { fillStyle: fruitInfo.color }; 
            }

            const fruit = Bodies.circle(x, y, fruitInfo.radius, {
                label: 'fruit', isStatic: isStatic, isSensor: isStatic, restitution: restitution,
                render: renderOptions, customIndex: index, customType: type,
                isLanded: false, explosionTime: null
            });

            if (type === 'fat') { Body.scale(fruit, 1.3, 0.7); }
            return fruit;
        }

        function prepareNextFruit() {
            if (isGameOver) return;
            currentFruit = createFruit(WIDTH / 2, 50, nextFruitInfo.index, true, nextFruitInfo.type);
            World.add(world, currentFruit);
            const rand = Math.random();
            let nextType = 'normal';
            if (rand < 0.15) nextType = 'fat'; else if (rand < 0.25) nextType = 'bomb'; else if (rand < 0.35) nextType = 'superball'; 
            nextFruitInfo = { index: Math.floor(Math.random() * 5), type: nextType };
            updateNextDisplay();
        }

        function updateNextDisplay() {
            const nextInfo = FRUITS[nextFruitInfo.index];
            const type = nextFruitInfo.type;

            if (type === 'bomb') nextBombBadge.style.display = 'block'; else nextBombBadge.style.display = 'none';
            if (type === 'superball') nextSuperballOverlay.style.display = 'block'; else nextSuperballOverlay.style.display = 'none';

            if (nextInfo.image) {
                nextFruitImg.src = nextInfo.image;
                nextFruitImg.style.display = 'block';
                if (type === 'fat') nextFruitImg.style.transform = 'scale(1.3, 0.7)'; else nextFruitImg.style.transform = 'none';
            } else {
                nextFruitImg.style.display = 'none';
                nextFruitImg.parentElement.style.backgroundColor = nextInfo.color;
            }
        }

        const container = document.getElementById('game-container');
        container.addEventListener('mousemove', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;
            const rect = container.getBoundingClientRect();
            let x = e.clientX - rect.left;
            const bounds = currentFruit.bounds;
            const widthHalf = (bounds.max.x - bounds.min.x) / 2;
            if (x < widthHalf + WALL_THICKNESS) x = widthHalf + WALL_THICKNESS;
            if (x > WIDTH - widthHalf - WALL_THICKNESS) x = WIDTH - widthHalf - WALL_THICKNESS;
            Body.setPosition(currentFruit, { x: x, y: 50 });
        });
        container.addEventListener('click', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;
            isClickable = false;
            Body.set(currentFruit, { isStatic: false, isSensor: false });
            currentFruit = null; 
            setTimeout(() => { isClickable = true; prepareNextFruit(); }, 1000);
        });

        function explode(bomb) {
            const forceMagnitude = 0.5;
            const explosionRadius = 300;
            World.remove(world, bomb);
            Composite.allBodies(world).forEach(body => {
                if (body === bomb || body.isStatic) return;
                const distanceVector = Vector.sub(body.position, bomb.position);
                const distance = Vector.magnitude(distanceVector);
                if (distance < explosionRadius) {
                    const force = Vector.normalise(distanceVector);
                    const strength = forceMagnitude * (1 - distance / explosionRadius);
                    Body.applyForce(body, body.position, Vector.mult(force, strength * body.mass));
                }
            });
        }

        function triggerPenalty(amount) {
            score -= amount;
            scoreElement.innerText = score;

            // 演出
            scorePanel.classList.add('penalty');
            scoreElement.classList.add('penalty');
            
            // ポップアップ表示
            const popup = document.createElement('div');
            popup.className = 'penalty-popup';
            popup.innerText = `PENALTY -${amount}`;
            // 画面中央付近に出す
            popup.style.left = '50%';
            popup.style.top = '30%';
            popup.style.transform = 'translateX(-50%)';
            document.getElementById('game-container').appendChild(popup);

            // アニメーション後に削除
            setTimeout(() => {
                popup.remove();
                scorePanel.classList.remove('penalty');
                scoreElement.classList.remove('penalty');
            }, 1000);
        }

        Events.on(engine, 'collisionStart', (event) => {
            if (isGameOver) return;
            event.pairs.forEach((pair) => {
                const bodyA = pair.bodyA, bodyB = pair.bodyB;

                if (bodyA.customType === 'bomb' && !bodyA.isLanded) { bodyA.isLanded = true; bodyA.explosionTime = Date.now() + 3000; }
                if (bodyB.customType === 'bomb' && !bodyB.isLanded) { bodyB.isLanded = true; bodyB.explosionTime = Date.now() + 3000; }

                if (bodyA.label === 'fruit' && bodyB.label === 'fruit') {
                    if (bodyA.customIndex === bodyB.customIndex) {
                        const index = bodyA.customIndex;
                        if (bodyA.isRemoved || bodyB.isRemoved) return;
                        
                        const mutationRand = Math.random();
                        let nextType = 'normal';
                        if (mutationRand < 0.1) nextType = 'fat';
                        else if (mutationRand < 0.2) nextType = 'bomb';
                        else if (mutationRand < 0.3) nextType = 'superball';

                        if (index === FRUITS.length - 1) {
                            bodyA.isRemoved = true; bodyB.isRemoved = true;
                            World.remove(world, [bodyA, bodyB]);
                            score += FRUITS[index].score * 2; scoreElement.innerText = score; return; 
                        }
                        bodyA.isRemoved = true; bodyB.isRemoved = true;
                        World.remove(world, [bodyA, bodyB]);
                        const newX = (bodyA.position.x + bodyB.position.x) / 2;
                        const newY = (bodyA.position.y + bodyB.position.y) / 2;
                        
                        World.add(world, createFruit(newX, newY, index + 1, false, nextType));
                        score += FRUITS[index + 1].score; scoreElement.innerText = score;
                    }
                }
            });
        });

        Events.on(engine, 'beforeUpdate', () => {
            const bodies = Composite.allBodies(world);
            bodies.forEach(body => {
                if (body.customType === 'bomb' && body.isLanded && body.explosionTime) {
                    if (Date.now() > body.explosionTime) { explode(body); }
                }

                // ★画面外ペナルティ処理★
                // 画面外（左右または上空高く、または下）へ吹っ飛んだ場合
                if (body.label === 'fruit' && !body.isStatic) {
                    const buffer = 100; // 画面外余裕
                    if (body.position.y > HEIGHT + buffer || 
                        body.position.y < -500 || // 上空へ吹っ飛びすぎた
                        body.position.x < -buffer || 
                        body.position.x > WIDTH + buffer) {
                        
                        // ペナルティ：そのフルーツのスコアの3倍
                        const penalty = FRUITS[body.customIndex].score * 3;
                        triggerPenalty(penalty);
                        
                        World.remove(world, body);
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
            nextFruitInfo = { index: Math.floor(Math.random() * 5), type: 'normal' };
            prepareNextFruit();
        });
    </script>
</body>
</html>