<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ojisan App Portal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.19.0/matter.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: "Helvetica Neue", Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* --- 背景の物理演算キャンバス --- */
        #background-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* --- メインコンテンツ --- */
        .container-wrapper {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            pointer-events: none;
        }

        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 40px 60px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 90%;
            pointer-events: auto;
            backdrop-filter: blur(5px);
        }

        h1 {
            font-size: 32px;
            margin-bottom: 40px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .menu-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .menu-item {
            display: block;
            padding: 20px;
            background-color: #fff;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-item:hover {
            border-color: #3498db;
            background-color: #f0f8ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .menu-item .description {
            display: block;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
            font-weight: normal;
        }

        /* アクセントカラー */
        .item-theater:hover { border-color: #e67e22; color: #e67e22; background-color: #fdf5e6; }
        .item-meme:hover { border-color: #9b59b6; color: #9b59b6; background-color: #f5eef8; }
        .item-game:hover { border-color: #2ecc71; color: #2ecc71; background-color: #eafaf1; }

        /* --- 床抜けボタン --- */
        .drop-btn {
            display: block;
            margin-top: 30px;
            padding: 12px 24px;
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 0 #c0392b;
            transition: all 0.1s;
            width: 100%;
        }
        .drop-btn:hover { background-color: #ec7063; }
        .drop-btn:active { transform: translateY(4px); box-shadow: none; }
        .drop-btn:disabled { background-color: #95a5a6; box-shadow: none; transform: translateY(4px); cursor: not-allowed; }

        footer {
            margin-top: 30px;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>

    <div id="background-canvas"></div>

    <div class="container-wrapper">
        <div class="container">
            <h1>おじさんアプリ総合受付</h1>

            <div class="menu-list">
                <a href="{{ url('/ojisan') }}" class="menu-item item-theater">
                    おじさんシアター
                    <span class="description">ランダムなおじさん画像を閲覧できます</span>
                </a>

                <a href="{{ url('/meme') }}" class="menu-item item-meme">
                    Memeシアター
                    <span class="description">海外のミーム動画を楽しめます</span>
                </a>

                <a href="{{ route('game.suika') }}" class="menu-item item-game">
                    おじさんスイカゲーム
                    <span class="description">物理演算パズル（爆発あり）</span>
                </a>
            </div>

            <button id="drop-btn" class="drop-btn" onclick="dropFloor()">
                ⚠ DROP THE FLOOR ⚠
            </button>

            <footer>
                &copy; 2026 Ojisan Application
            </footer>
        </div>
    </div>

    <script>
        // 画像リスト
        const OJI_IMAGES = [
            "{{ asset('images/01.png') }}", "{{ asset('images/02.png') }}", "{{ asset('images/03.png') }}",
            "{{ asset('images/04.png') }}", "{{ asset('images/05.png') }}", "{{ asset('images/06.png') }}",
            "{{ asset('images/07.png') }}", "{{ asset('images/08.png') }}", "{{ asset('images/09.png') }}",
            "{{ asset('images/10.png') }}", "{{ asset('images/11.png') }}"
        ];

        // Matter.js 初期化
        const Engine = Matter.Engine,
              Render = Matter.Render,
              Runner = Matter.Runner,
              Bodies = Matter.Bodies,
              Composite = Matter.Composite,
              Events = Matter.Events,
              World = Matter.World;

        const engine = Engine.create();
        const world = engine.world;

        const width = window.innerWidth;
        const height = window.innerHeight;

        const render = Render.create({
            element: document.getElementById('background-canvas'),
            engine: engine,
            options: {
                width: width, height: height,
                wireframes: false, background: 'transparent'
            }
        });

        // 壁と床
        const wallThickness = 100;
        const wallOptions = { isStatic: true, render: { visible: false } };

        const ground = Bodies.rectangle(width / 2, height + wallThickness/2, width, wallThickness, wallOptions);
        const leftWall = Bodies.rectangle(0 - wallThickness/2, height / 2, wallThickness, height * 5, wallOptions);
        const rightWall = Bodies.rectangle(width + wallThickness/2, height / 2, wallThickness, height * 5, wallOptions);

        World.add(world, [ground, leftWall, rightWall]);

        // おじさん生成
        function spawnOjisan() {
            const x = Math.random() * (width - 100) + 50;
            const y = -100; 
            const imagePath = OJI_IMAGES[Math.floor(Math.random() * OJI_IMAGES.length)];
            const radius = Math.random() * 25 + 15; 

            const ojisan = Bodies.circle(x, y, radius, {
                restitution: 0.5, friction: 0.05,
                render: {
                    sprite: {
                        texture: imagePath,
                        xScale: (radius * 2) / 256, yScale: (radius * 2) / 256
                    }
                }
            });
            World.add(world, ojisan);
        }

        // 床を抜く処理
        const dropBtn = document.getElementById('drop-btn');
        function dropFloor() {
            // 床を削除
            World.remove(world, ground);
            
            // ボタン無効化
            dropBtn.disabled = true;
            dropBtn.innerText = "FALLING...";

            // 3秒後に復活
            setTimeout(() => {
                World.add(world, ground);
                dropBtn.disabled = false;
                dropBtn.innerText = "⚠ DROP THE FLOOR ⚠";
            }, 3000);
        }

        // 定期実行
        setInterval(spawnOjisan, 200);

        // クリーンアップ処理（画面外に落ちたおじさんを削除）
        Events.on(engine, 'beforeUpdate', () => {
            const bodies = Composite.allBodies(world);
            bodies.forEach(body => {
                if (!body.isStatic && body.position.y > height + 500) {
                    World.remove(world, body);
                }
            });
        });

        // 実行
        Render.run(render);
        const runner = Runner.create();
        Runner.run(runner, engine);

        window.addEventListener('resize', () => {
            render.canvas.width = window.innerWidth;
            render.canvas.height = window.innerHeight;
        });
    </script>
</body>
</html>