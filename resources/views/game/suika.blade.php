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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        #game-container {
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #444;
        }

        /* スコア表示 */
        #ui-layer {
            position: absolute;
            top: 20px;
            left: 20px;
            pointer-events: none;
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            text-shadow: 1px 1px 0 #000;
        }

        /* ロード画面 */
        #loading-screen {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #444;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
            z-index: 200;
        }

        /* ゲームオーバーラインの警告表示 */
        #danger-line {
            position: absolute;
            top: 150px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: rgba(255, 0, 0, 0.5);
            pointer-events: none;
            display: block;
            z-index: 10;
        }
        #danger-line::after {
            content: "DEAD LINE";
            position: absolute;
            right: 5px;
            top: -14px;
            color: rgba(255, 0, 0, 0.8);
            font-size: 10px;
            font-weight: bold;
        }

        /* ゲームオーバー画面 */
        #game-over {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 50px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 100;
        }

        #game-over h2 {
            margin-top: 0;
            color: #e74c3c;
            font-size: 32px;
        }

        #final-score {
            font-size: 24px;
            margin: 10px 0;
            color: #333;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 18px;
            cursor: pointer;
            border-radius: 50px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

    <div id="game-container">
        <div id="loading-screen">Loading Assets...</div>

        <div id="ui-layer">Score: <span id="score">0</span></div>
        <div id="danger-line"></div>
        <div id="game-over">
            <h2>GAME OVER</h2>
            <div id="final-score">Score: 0</div>
            <button onclick="location.reload()">RETRY</button>
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

        // --- 画像読み込みチェック機能（修正版） ---
        function preloadImages(callback) {
            let loadedCount = 0;
            const total = FRUITS.length;

            FRUITS.forEach(fruit => {
                if (!fruit.image) {
                    loadedCount++;
                    if (loadedCount === total) callback();
                    return;
                }

                const img = new Image();
                img.src = fruit.image;
                
                // 画像読み込み成功時
                img.onload = () => {
                    // ★重要：画像の本当のサイズを記録する
                    fruit.actualWidth = img.naturalWidth;
                    fruit.actualHeight = img.naturalHeight;

                    loadedCount++;
                    if (loadedCount === total) callback();
                };
                
                // 画像読み込み失敗時
                img.onerror = () => {
                    console.warn(`Image not found: ${fruit.image}. Fallback to color.`);
                    fruit.image = null;
                    loadedCount++;
                    if (loadedCount === total) callback();
                };
            });
        }

        // --- Matter.js 初期化 ---
        const Engine = Matter.Engine,
              Render = Matter.Render,
              Runner = Matter.Runner,
              Bodies = Matter.Bodies,
              Composite = Matter.Composite,
              Events = Matter.Events,
              World = Matter.World,
              Body = Matter.Body;

        const engine = Engine.create();
        const world = engine.world;

        const render = Render.create({
            element: document.getElementById('game-container'),
            engine: engine,
            options: {
                width: WIDTH,
                height: HEIGHT,
                wireframes: false, 
                background: '#444' 
            }
        });

        // --- 壁の作成 ---
        const ground = Bodies.rectangle(WIDTH / 2, HEIGHT, WIDTH, WALL_THICKNESS * 2, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#666' }
        });
        const leftWall = Bodies.rectangle(0, HEIGHT / 2, WALL_THICKNESS, HEIGHT, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#666' }
        });
        const rightWall = Bodies.rectangle(WIDTH, HEIGHT / 2, WALL_THICKNESS, HEIGHT, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#666' }
        });

        World.add(world, [ground, leftWall, rightWall]); 

        // --- ゲーム状態管理 ---
        let currentFruit = null;
        let isClickable = true;
        let isGameOver = false;
        let score = 0;
        let gameOverTimer = 0; 
        const scoreElement = document.getElementById('score');
        const finalScoreElement = document.getElementById('final-score');
        const gameOverElement = document.getElementById('game-over');
        const loadingElement = document.getElementById('loading-screen');

        // 生成関数（修正版）
        function createFruit(x, y, index, isStatic = false) {
            const fruitInfo = FRUITS[index];
            
            let renderOptions = {};
            
            // チェック済みの image プロパティを使用
            if (fruitInfo.image && fruitInfo.actualWidth && fruitInfo.actualHeight) {
                // ★重要：目標の直径（半径*2）を、実際の画像サイズで割ってスケールを計算
                const targetDiameter = fruitInfo.radius * 2;
                const scaleX = targetDiameter / fruitInfo.actualWidth;
                const scaleY = targetDiameter / fruitInfo.actualHeight;

                renderOptions = {
                    sprite: {
                        texture: fruitInfo.image,
                        xScale: scaleX,
                        yScale: scaleY
                    }
                };
            } else {
                renderOptions = {
                    fillStyle: fruitInfo.color
                };
            }

            // 保持中はセンサー（幽霊）扱いで当たり判定なし
            const fruit = Bodies.circle(x, y, fruitInfo.radius, {
                label: 'fruit',
                isStatic: isStatic,
                isSensor: isStatic, 
                restitution: 0.2, 
                render: renderOptions,
                customIndex: index 
            });

            return fruit;
        }

        // 次に落とす物体を準備
        function prepareNextFruit() {
            if (isGameOver) return;
            const randomIndex = Math.floor(Math.random() * 5); 
            currentFruit = createFruit(WIDTH / 2, 50, randomIndex, true);
            World.add(world, currentFruit);
        }

        // --- マウス操作 ---
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
            
            // 落下開始
            Body.set(currentFruit, { 
                isStatic: false, 
                isSensor: false 
            });
            
            currentFruit = null; 

            // クールダウン
            setTimeout(() => {
                isClickable = true;
                prepareNextFruit();
            }, 1000);
        });

        // --- 衝突・合体ロジック ---
        Events.on(engine, 'collisionStart', (event) => {
            if (isGameOver) return;
            const pairs = event.pairs;

            pairs.forEach((pair) => {
                const bodyA = pair.bodyA;
                const bodyB = pair.bodyB;

                if (bodyA.label === 'fruit' && bodyB.label === 'fruit') {
                    if (bodyA.customIndex === bodyB.customIndex) {
                        const index = bodyA.customIndex;
                        
                        if (bodyA.isRemoved || bodyB.isRemoved) return;

                        // 最大サイズ同士なら消滅＆ボーナス
                        if (index === FRUITS.length - 1) {
                            bodyA.isRemoved = true;
                            bodyB.isRemoved = true;
                            World.remove(world, [bodyA, bodyB]);
                            
                            score += FRUITS[index].score * 2;
                            scoreElement.innerText = score;
                            return; 
                        }

                        // 進化合体
                        bodyA.isRemoved = true;
                        bodyB.isRemoved = true;
                        World.remove(world, [bodyA, bodyB]);
                        
                        const newX = (bodyA.position.x + bodyB.position.x) / 2;
                        const newY = (bodyA.position.y + bodyB.position.y) / 2;
                        
                        const newFruit = createFruit(newX, newY, index + 1);
                        World.add(world, newFruit);

                        score += FRUITS[index + 1].score;
                        scoreElement.innerText = score;
                    }
                }
            });
        });

        // --- ゲームオーバー判定 ---
        Events.on(engine, 'afterUpdate', () => {
            if (isGameOver) return;

            let isDanger = false;

            Composite.allBodies(world).forEach(body => {
                if (body.label === 'fruit' && !body.isStatic && !body.isSensor) {
                    if (body.position.y < DEADLINE_Y && body.speed < 0.2) {
                        isDanger = true;
                    }
                }
            });

            if (isDanger) {
                gameOverTimer++;
                if (gameOverTimer > 180) { // 約3秒
                    isGameOver = true;
                    showGameOver();
                }
            } else {
                gameOverTimer = 0;
            }
        });

        function showGameOver() {
            finalScoreElement.innerText = "Score: " + score;
            gameOverElement.style.display = 'block';
            isClickable = false; 
        }

        // --- 起動処理 ---
        preloadImages(() => {
            loadingElement.style.display = 'none';

            Render.run(render);
            const runner = Runner.create();
            Runner.run(runner, engine);

            prepareNextFruit();
        });

    </script>
</body>
</html>