<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $conn = db_connect();
    clean_expired_images();
    
    $file = $_FILES['image'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($ext, $allowed)) {
        die('<div class="error">只允许上传JPG, PNG或GIF图片</div>');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        die('<div class="error">图片大小不能超过5MB</div>');
    }
    
    $code = generate_random_string(12);
    $filename = $code . '.' . $ext;
    $path = UPLOAD_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        $view_url = SITE_URL . 'view.php?code=' . $code;
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $conn->prepare("INSERT INTO images (code, filename, expires_at, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $code, $filename, $expires_at, $password);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        
        echo '<div class="result">
                <h2>上传成功！</h2>
                <p>分享链接：</p>
                <input type="text" value="'.htmlspecialchars($view_url).'" id="share-url" readonly>
                '.($password ? '<p>访问密码：'.htmlspecialchars($_POST['password']).'</p>' : '').'
                <button type="button" id="copy-button">复制链接</button>
                <p>注意：图片将在查看3秒后自动销毁</p>
              </div>
              <script>
                document.getElementById("copy-button").onclick = function() {
                    const copyText = document.getElementById("share-url");
                    copyText.select();
                    document.execCommand("copy");
                    this.textContent = "✓ 已复制";
                    setTimeout(() => {
                        this.textContent = "复制链接";
                    }, 2000);
                }
              </script>';
        exit;
    } else {
        echo '<div class="error">上传失败，请重试</div>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>闪照系统</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>闪照系统</h1>
        <p>上传图片生成限时查看的分享链接</p>
        
        <form action="" method="post" enctype="multipart/form-data" id="upload-form">
            <div class="upload-area" id="upload-area">
                <input type="file" name="image" id="image-input" accept="image/*" required>
                <div class="upload-label">
                    <p>点击或拖拽上传图片</p>
                    <p class="hint">支持JPG, PNG, GIF，最大5MB</p>
                </div>
            </div>
            
            <div class="password-field">
                <label for="password">设置查看密码（可选）：</label>
                <input type="password" name="password" id="password" placeholder="留空则不设密码">
            </div>
            
            <button type="submit">生成闪照链接</button>
        </form>
    </div>
    
    <script src="assets/script.js"></script>
</body>
</html>