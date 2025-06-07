<?php
require_once 'functions.php';

if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit;
}

$code = $_GET['code'];
$conn = db_connect();
clean_expired_images();

// 检查密码
$stmt = $conn->prepare("SELECT filename, password FROM images WHERE code = ? AND expires_at > NOW()");
$stmt->bind_param("s", $code);
$stmt->execute();
$stmt->bind_result($filename, $hashed_password);
$stmt->fetch();
$stmt->close();

if (!$filename) {
    die('图片不存在或已过期');
}

// 需要密码验证
if ($hashed_password) {
    if (!isset($_POST['view_password'])) {
        show_password_form($code);
        exit;
    }
    
    if (!password_verify($_POST['view_password'], $hashed_password)) {
        show_password_form($code, true);
        exit;
    }
}

// 删除数据库记录
$stmt = $conn->prepare("DELETE FROM images WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$stmt->close();
$conn->close();

function show_password_form($code, $error = false) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>输入查看密码</title>
        <style>
            body { 
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
            }
            .password-box {
                padding: 30px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 400px;
                text-align: center;
            }
            h2 {
                color: #333;
                margin-bottom: 20px;
            }
            input[type="password"] {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px;
            }
            button {
                padding: 12px 20px;
                background: #3498db;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                width: 100%;
            }
            button:hover {
                background: #2980b9;
            }
            .error {
                color: #e74c3c;
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <div class="password-box">
            <h2>需要查看密码</h2>
            <?php if ($error): ?>
                <p class="error">密码错误，请重试</p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                <input type="password" name="view_password" placeholder="输入查看密码" required>
                <button type="submit">查看图片</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>闪照查看</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f5f5f5;
            font-family: 'Arial', sans-serif;
        }
        .flash-container {
            text-align: center;
            max-width: 100%;
            padding: 20px;
        }
        .image-wrapper {
            position: relative;
            margin: 0 auto;
            max-width: 100%;
            max-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #flash-image {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: opacity 0.5s;
        }
        .countdown {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        .hint {
            margin-top: 20px;
            color: #666;
            font-size: 16px;
        }
        .expired-message {
            color: #e74c3c;
            font-size: 18px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .image-wrapper {
                max-height: 70vh;
            }
            #flash-image {
                max-height: 70vh;
            }
        }
    </style>
</head>
<body>
    <div class="flash-container">
        <div class="image-wrapper">
            <img src="<?= UPLOAD_DIR . htmlspecialchars($filename) ?>" alt="闪照" id="flash-image">
            <div class="countdown">3</div>
        </div>
        <p class="hint">图片将在 <span id="seconds">3</span> 秒后销毁...</p>
    </div>
    
    <script>
        // 图片加载处理
        document.getElementById('flash-image').onload = function() {
            startCountdown();
        };
        
        // 图片加载失败处理
        document.getElementById('flash-image').onerror = function() {
            document.querySelector('.hint').innerHTML = 
                '<p class="expired-message">图片加载失败，可能已被销毁</p>';
            document.querySelector('.countdown').style.display = 'none';
        };
        
        // 倒计时函数
        function startCountdown() {
            let seconds = 3;
            const countdown = setInterval(() => {
                seconds--;
                document.getElementById('seconds').textContent = seconds;
                document.querySelector('.countdown').textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    // 删除图片
                    fetch('delete_image.php?code=<?= htmlspecialchars($code) ?>')
                        .then(() => {
                            document.getElementById('flash-image').style.opacity = '0';
                            document.querySelector('.countdown').style.display = 'none';
                            document.querySelector('.hint').innerHTML = 
                                '<p class="expired-message">图片已销毁，无法再次查看</p>';
                        })
                        .catch(err => console.error('删除失败:', err));
                }
            }, 1000);
        }
    </script>
</body>
</html>