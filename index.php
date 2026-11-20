<?php
require 'config.php';
$page = $_GET['page'] ?? 'login';
$msg = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$msgType = $_SESSION['flash_type'] ?? 'success'; unset($_SESSION['flash_type']);

if (isset($_SESSION['user']) && in_array($page, ['login', 'register'])) {
    redirect('index.php?page=projects');
}

if ($page === 'logout') { session_destroy(); redirect('index.php?page=login'); }

if (in_array($page, ['login', 'register'])) {
    require 'auth.php';
} elseif (strpos($page, 'project') === 0) {
    checkAuth();
    require 'projects.php';
} elseif (strpos($page, 'bug') === 0 || $page === 'bugs') {
    checkAuth();
    require 'bugs.php';
} else {
    require 'auth.php';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BugHunter</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <a href="index.php?page=projects" class="brand">BugHunter</a>
    <?php if (isset($_SESSION['user'])): ?>
    <nav class="main-nav">
        <a href="index.php?page=projects" class="<?= strpos($page, 'project') === 0 ? 'active' : '' ?>">Проекты</a>
        <a href="index.php?page=bugs" class="<?= strpos($page, 'bug') === 0 ? 'active' : '' ?>">Баги</a>
    </nav>
    <div class="user-menu">
        <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?> (<?= $_SESSION['user']['role'] ?>)</span>
        <a href="index.php?page=logout" class="btn-out">Выйти</a>
    </div>
    <?php endif; ?>
</header>

<div class="container">
    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if (isset($content)) echo $content; ?>
</div>
</body>
</html>