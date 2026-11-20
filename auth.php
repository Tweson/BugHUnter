<?php
ob_start();

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user'] = $user;
        redirect('index.php?page=projects');
    } else {
        $msg = "Неверный логин или пароль."; $msgType = "error";
    }
}

if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'developer';
    
    if (strlen($login) < 3 || strlen($pass) < 3 || strlen($full_name) < 2) {
        $msg = "Логин и пароль от 3 символов, имя от 2 символов."; $msgType = "error";
    } elseif (!in_array($role, ['developer', 'tester', 'manager'])) {
        $msg = "Неверная роль."; $msgType = "error";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (login, full_name, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$login, $full_name, password_hash($pass, PASSWORD_DEFAULT), $role]);
            redirect('index.php?page=login', 'Регистрация успешна. Войдите.');
        } catch (PDOException $e) { 
            $msg = ($e->getCode() == 23000) ? "Логин уже занят." : "Ошибка базы данных."; 
            $msgType = "error"; 
        }
    }
}

if ($page === 'login') {
    echo '<h2 class="text-center">Вход в систему</h2>
    <div class="form-card-center">
        <form method="POST">
            <div class="form-group"><label>Логин</label><input type="text" name="login" value="admin" required></div>
            <div class="form-group"><label>Пароль</label><input type="password" name="password" value="admin" required></div>
            <button type="submit" class="btn w-100">Войти</button>
        </form>
        <p class="text-center mt-3"><a href="index.php?page=register">Нет аккаунта? Регистрация</a></p>
    </div>';
} elseif ($page === 'register') {
    echo '<h2 class="text-center">Регистрация</h2>
    <div class="form-card-center">
        <form method="POST">
            <div class="form-group"><label>Имя</label><input type="text" name="full_name" required minlength="2"></div>
            <div class="form-group"><label>Логин</label><input type="text" name="login" required minlength="3"></div>
            <div class="form-group"><label>Пароль</label><input type="password" name="password" required minlength="3"></div>
            <div class="form-group">
                <label>Роль</label>
                <select name="role">
                    <option value="developer">Разработчик</option>
                    <option value="tester">Тестировщик</option>
                    <option value="manager">Менеджер</option>
                </select>
            </div>
            <button type="submit" class="btn w-100">Создать аккаунт</button>
        </form>
        <p class="text-center mt-3"><a href="index.php?page=login">Уже есть аккаунт? Войти</a></p>
    </div>';
}

$content = ob_get_clean();
?>