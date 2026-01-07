<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suika Game Clone</title>
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
        }

        /* スコア表示 */
        #ui-layer {
            position: absolute;
            top: 20px;
            left: 20px;
            pointer-events: none;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-shadow: 1px 1px 0 #fff;
        }

        /* ゲームオーバーラインの警告表示 */
        #danger-line {
            position: absolute;
            top: 150px; /* ゲームオーバーラインのY座標と合わせる */
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
        
        // フルーツの定義
        // radius: 半径, color: 仮の色, image: 画像パス
        const FRUITS = [
            { name: 'cherry', radius: 15, score: 0, color: '#F00', image: null },
            { name: 'strawberry', radius: 25, score: 2, color: '#F80', image: null },
            { name: 'grape', radius: 35, score: 4, color: '#A0F', image: null },
            { name: 'dekopon', radius: 45, score: 8, color: '#FA0', image: null },
            { name: 'orange', radius: 58, score: 16, color: '#F80', image: null },
            { name: 'apple', radius: 72, score: 32, color: '#F00', image: null },
            { name: 'pear', radius: 88, score: 64, color: '#FF8', image: null },
            { name: 'peach', radius: 105, score: 128, color: '#FBC', image: null },
            { name: 'pineapple', radius: 125, score: 256, color: '#FF0', image: null },
            { name: 'melon', radius: 145, score: 512, color: '#8F8', image: null },
            { name: 'watermelon', radius: 165, score: 1024, color: '#080', image: null },
        ];

        // ゲーム画面サイズ
        const WIDTH = 600;
        const HEIGHT = 800;
        const WALL_THICKNESS = 20;
        const DEADLINE_Y = 150; // このラインを超えて積み上がるとゲームオーバー

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

        // レンダラーの作成
        const render = Render.create({
            element: document.getElementById('game-container'),
            engine: engine,
            options: {
                width: WIDTH,
                height: HEIGHT,
                wireframes: false, 
                background: '#FFDEAD' 
            }
        });

        // --- 壁の作成 ---
        const ground = Bodies.rectangle(WIDTH / 2, HEIGHT, WIDTH, WALL_THICKNESS * 2, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#8B4513' }
        });
        const leftWall = Bodies.rectangle(0, HEIGHT / 2, WALL_THICKNESS, HEIGHT, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#8B4513' }
        });
        const rightWall = Bodies.rectangle(WIDTH, HEIGHT / 2, WALL_THICKNESS, HEIGHT, { 
            isStatic: true,
            label: 'wall',
            render: { fillStyle: '#8B4513' }
        });

        World.add(world, [ground, leftWall, rightWall]); 

        // --- ゲーム状態管理 ---
        let currentFruit = null;
        let isClickable = true;
        let isGameOver = false;
        let score = 0;
        let gameOverTimer = 0; // デッドライン超過時間の計測用
        const scoreElement = document.getElementById('score');
        const finalScoreElement = document.getElementById('final-score');
        const gameOverElement = document.getElementById('game-over');

        // フルーツを生成する関数
        function createFruit(x, y, index, isStatic = false) {
            const fruitInfo = FRUITS[index];
            
            let renderOptions = {};
            if (fruitInfo.image) {
                renderOptions = {
                    sprite: {
                        texture: fruitInfo.image,
                        xScale: (fruitInfo.radius * 2) / 512, 
                        yScale: (fruitInfo.radius * 2) / 512
                    }
                };
            } else {
                renderOptions = {
                    fillStyle: fruitInfo.color
                };
            }

            // 【修正2】落下前の干渉防止: 
            // isStatic(保持中)の場合は isSensor: true にして、物理干渉（衝突）を無効化する
            const fruit = Bodies.circle(x, y, fruitInfo.radius, {
                label: 'fruit',
                isStatic: isStatic,
                isSensor: isStatic, // 保持中はセンサー（幽霊）扱い
                restitution: 0.2, 
                render: renderOptions,
                customIndex: index 
            });

            return fruit;
        }

        // 次に落とすフルーツを準備
        function prepareNextFruit() {
            if (isGameOver) return;
            const randomIndex = Math.floor(Math.random() * 5); 
            // 保持位置はデッドラインより十分上(Y=50)にする
            currentFruit = createFruit(WIDTH / 2, 50, randomIndex, true);
            World.add(world, currentFruit);
        }

        // --- マウス操作 ---
        const container = document.getElementById('game-container');
        
        container.addEventListener('mousemove', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;
            
            const rect = container.getBoundingClientRect();
            let x = e.clientX - rect.left;
            
            // 壁にめり込まないように制限
            const r = currentFruit.circleRadius;
            if (x < r + WALL_THICKNESS) x = r + WALL_THICKNESS;
            if (x > WIDTH - r - WALL_THICKNESS) x = WIDTH - r - WALL_THICKNESS;

            Body.setPosition(currentFruit, { x: x, y: 50 });
        });

        container.addEventListener('click', (e) => {
            if (!isClickable || !currentFruit || isGameOver) return;

            isClickable = false;
            
            // 【修正2の続き】落下開始:
            // 物理干渉を有効化(isSensor: false)し、物理演算を開始(isStatic: false)
            Body.set(currentFruit, { 
                isStatic: false, 
                isSensor: false 
            });
            
            currentFruit = null; // 手離れさせる

            // 次のフルーツ生成までのクールダウン
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
                        
                        // 多重処理防止
                        if (bodyA.isRemoved || bodyB.isRemoved) return;

                        // 【修正3】スイカ(最大サイズ)同士の処理
                        if (index === FRUITS.length - 1) {
                            bodyA.isRemoved = true;
                            bodyB.isRemoved = true;
                            World.remove(world, [bodyA, bodyB]);
                            
                            // スイカ消滅ボーナス（例: スイカのスコアx2）
                            score += FRUITS[index].score * 2;
                            scoreElement.innerText = score;
                            return; 
                        }

                        // 通常の合体処理
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

        // --- 【修正1】ゲームオーバー判定 ---
        Events.on(engine, 'afterUpdate', () => {
            if (isGameOver) return;

            let isDanger = false;

            // 全ての物体をチェック
            Composite.allBodies(world).forEach(body => {
                // 1. フルーツであること
                // 2. 静止状態でない（保持中のフルーツは除外）
                // 3. センサー状態でない（落下直後や保持中は除外）
                if (body.label === 'fruit' && !body.isStatic && !body.isSensor) {
                    
                    // デッドラインを超えている(Y座標が小さい) かつ 動きがほぼ止まっている
                    if (body.position.y < DEADLINE_Y && body.speed < 0.2) {
                        isDanger = true;
                    }
                }
            });

            if (isDanger) {
                gameOverTimer++;
                // 60FPS想定で約3秒間(180フレーム)デッドライン上に留まったらアウト
                if (gameOverTimer > 180) {
                    isGameOver = true;
                    showGameOver();
                }
            } else {
                // 危険な状態が解消されたらタイマーリセット
                gameOverTimer = 0;
            }
        });

        function showGameOver() {
            finalScoreElement.innerText = "Score: " + score;
            gameOverElement.style.display = 'block';
            isClickable = false; // 操作不能にする
        }

        // --- ゲーム開始 ---
        Render.run(render);
        const runner = Runner.create();
        Runner.run(runner, engine);

        prepareNextFruit();

    </script>
</body>
</html>