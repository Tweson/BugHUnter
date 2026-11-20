<?php
session_save_path(__DIR__ . '/sessions');
session_start();

$dbPath = __DIR__ . '/data/bughunter.db';
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
if (!is_dir(__DIR__ . '/sessions')) mkdir(__DIR__ . '/sessions', 0777, true);

if (!file_exists($dbPath)) {
    die("База данных не найдена. Запустите <a href='init.php'>init.php</a> для её создания.");
}

$pdo = new PDO("sqlite:" . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = ON;");

function redirect($url, $msg = '', $type = 'success') {
    if ($msg) { $_SESSION['flash'] = $msg; $_SESSION['flash_type'] = $type; }
    header("Location: $url"); exit;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

function checkAuth() {
    if (!isset($_SESSION['user'])) redirect('index.php?page=login', 'Сначала авторизуйтесь.', 'error');
}
?>