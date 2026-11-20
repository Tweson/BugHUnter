<?php
ob_start();

if ($page === 'projects') {
    $projects = $pdo->query("SELECT p.*, u.full_name as owner FROM projects p JOIN users u ON p.owner_id = u.id ORDER BY p.id DESC")->fetchAll();
    echo '<h2>Проекты</h2><a href="index.php?page=project_create" class="btn mb-3">+ Новый проект</a><table><tr><th>ID</th><th>Название</th><th>Описание</th><th>Автор</th><th>Действия</th></tr>';
    foreach ($projects as $p) {
        echo "<tr><td>{$p['id']}</td><td><a href='index.php?page=project_view&id={$p['id']}'>{$p['name']}</a></td><td>{$p['description']}</td><td>{$p['owner']}</td><td>
        <a href='index.php?page=project_bugs&id={$p['id']}' class='btn btn-sm btn-secondary'>Баги проекта</a>";
        if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['id'] == $p['owner_id']) {
            echo "<a href='index.php?page=project_edit&id={$p['id']}' class='btn btn-sm'>✎</a>
            <a href='index.php?page=project_delete&id={$p['id']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Удалить?')\">✖</a>";
        }
        echo "</td></tr>";
    }
    echo '</table>';
} 

elseif ($page === 'project_view') {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as owner FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.id = ?");
    $stmt->execute([(int)$_GET['id']]); $p = $stmt->fetch();
    if (!$p) redirect('index.php?page=projects', 'Проект не найден', 'error');
    echo "<h2>Проект: {$p['name']}</h2>
    <div class='info-box'>
        <p><strong>Автор:</strong> {$p['owner']}</p>
        <p class='mb-0'><strong>Описание:</strong> {$p['description']}</p>
    </div>
    <a href='index.php?page=project_bugs&id={$p['id']}' class='btn btn-success'>Посмотреть баги проекта</a>
    <a href='index.php?page=projects' class='btn'>← Назад</a>";
}

elseif ($page === 'project_create') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die("CSRF Error");
        $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
        if (!$name) { $msg = "Название обязательно."; $msgType = "error"; }
        else {
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, owner_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $desc, $_SESSION['user']['id']]);
            redirect('index.php?page=projects', 'Проект создан.');
        }
    }
    echo '<h2>Создать проект</h2><div class="form-card"><form method="POST"><input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
    <div class="form-group"><label>Название</label><input type="text" name="name" required></div>
    <div class="form-group"><label>Описание</label><textarea name="description"></textarea></div>
    <button type="submit" class="btn">Сохранить</button></form></div>';
}

elseif ($page === 'project_edit') {
    $pid = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?"); $stmt->execute([$pid]); $p = $stmt->fetch();
    if (!$p) redirect('index.php?page=projects', 'Проект не найден', 'error');
    if ($_SESSION['user']['role'] !== 'admin' && $p['owner_id'] != $_SESSION['user']['id']) {
        redirect('index.php?page=projects', 'Нет прав на редактирование', 'error');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die("CSRF Error");
        $name = trim($_POST['name'] ?? '');
        if (!$name) { $msg = "Название обязательно."; $msgType = "error"; }
        else {
            $upd = $pdo->prepare("UPDATE projects SET name = ?, description = ? WHERE id = ?");
            $upd->execute([$name, trim($_POST['description']), $pid]);
            redirect('index.php?page=projects', 'Проект обновлен.');
        }
    }
    echo '<h2>Редактировать проект</h2><div class="form-card"><form method="POST"><input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
    <div class="form-group"><label>Название</label><input type="text" name="name" value="'.htmlspecialchars($p['name']).'" required></div>
    <div class="form-group"><label>Описание</label><textarea name="description">'.htmlspecialchars($p['description']).'</textarea></div>
    <button type="submit" class="btn">Обновить</button></form></div>';
}

elseif ($page === 'project_delete') {
    $pid = (int)$_GET['id'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM bugs WHERE project_id = ?"); $check->execute([$pid]);
    if ($check->fetchColumn() > 0) redirect('index.php?page=projects', 'Нельзя удалить проект с багами!', 'error');
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?"); $stmt->execute([$pid]);
    redirect('index.php?page=projects', 'Проект удален.');
}

elseif ($page === 'project_bugs') {
    $pid = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?"); $stmt->execute([$pid]); $project = $stmt->fetch();
    if (!$project) redirect('index.php?page=projects', 'Проект не найден', 'error');
    
    $stmt = $pdo->prepare("SELECT b.*, a.full_name as assignee FROM bugs b LEFT JOIN users a ON b.assignee_id = a.id WHERE b.project_id = ? ORDER BY b.id DESC");
    $stmt->execute([$pid]);
    
    echo "<h2>Баги проекта: ".htmlspecialchars($project['name'])."</h2>
    <a href='index.php?page=bug_create&project_id={$pid}' class='btn mb-3'>+ Добавить баг</a>
    <table><tr><th>ID</th><th>Название</th><th>Статус</th><th>Приоритет</th><th>Исполнитель</th><th>Действия</th></tr>";
    foreach ($stmt->fetchAll() as $b) {
        $canEdit = $_SESSION['user']['role'] === 'admin' || $_SESSION['user']['id'] == $b['author_id'] || $_SESSION['user']['id'] == $b['assignee_id'];
        echo "<tr><td>#{$b['id']}</td><td>{$b['title']}</td>
        <td><span class='badge badge-status-".str_replace(' ', '', $b['status'])."'>{$b['status']}</span></td>
        <td><span class='badge badge-priority-{$b['priority']}'>{$b['priority']}</span></td>
        <td>".($b['assignee'] ? $b['assignee'] : '<i>Не назначен</i>')."</td>
        <td>".($canEdit ? "<a href='index.php?page=bug_edit&id={$b['id']}' class='btn btn-sm'>✎ Ред.</a>" : "<span class='text-muted'>Нет прав</span>")."</td></tr>";
    }
    echo '</table>';
}

$content = ob_get_clean();
?>