<?php
require_once 'functions.php';

if (!isset($_GET['code'])) {
    http_response_code(400);
    exit;
}

$code = $_GET['code'];
$conn = db_connect();

$stmt = $conn->prepare("SELECT filename FROM images WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$stmt->bind_result($filename);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($filename && file_exists(UPLOAD_DIR . $filename)) {
    unlink(UPLOAD_DIR . $filename);
}

header('Content-Type: text/plain');
echo 'OK';
?>