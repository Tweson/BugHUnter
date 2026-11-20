<?php
ob_start();

if ($page === 'bugs') {
    $bugs = $pdo->query("SELECT b.*, p.name as p_name, a.full_name as assignee FROM bugs b JOIN projects p ON b.project_id = p.id LEFT JOIN users a ON b.assignee_id = a.id ORDER BY b.id DESC")->fetchAll();
    echo '<h2>Все баги</h2><a href="index.php?page=bug_create" class="btn mb-3">+ Создать баг</a><table><tr><th>ID</th><th>Название</th><th>Проект</th><th>Статус</th><th>Приоритет</th><th>Исполнитель</th><th>Действия</th></tr>';
    foreach ($bugs as $b) {
        $canEdit = $_SESSION['user']['role'] === 'admin' || $_SESSION['user']['id'] == $b['author_id'] || $_SESSION['user']['id'] == $b['assignee_id'];
        echo "<tr><td>#{$b['id']}</td><td>{$b['title']}</td><td><a href='index.php?page=project_bugs&id={$b['project_id']}'>{$b['p_name']}</a></td>
        <td><span class='badge badge-status-".str_replace(' ', '', $b['status'])."'>{$b['status']}</span></td>
        <td><span class='badge badge-priority-{$b['priority']}'>{$b['priority']}</span></td>
        <td>".($b['assignee'] ? $b['assignee'] : '<i>Не назначен</i>')."</td>
        <td>".($canEdit ? "<a href='index.php?page=bug_edit&id={$b['id']}' class='btn btn-sm'>✎ Ред.</a>" : "<span class='text-muted'>Нет прав</span>")."</td></tr>";
    }
    echo '</table>';
}

elseif ($page === 'bug_create') {
    $preselect = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die("CSRF Error");
        $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? '');
        $pid = (int)($_POST['project_id'] ?? 0); $prio = $_POST['priority'] ?? 'Medium';
        $stat = $_POST['status'] ?? 'Open'; $assignee = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;

        if (!$title || !$pid) { $msg = "Заполните все поля."; $msgType = "error"; }
        else {
            $stmt = $pdo->prepare("INSERT INTO bugs (title, description, priority, status, project_id, author_id, assignee_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $prio, $stat, $pid, $_SESSION['user']['id'], $assignee]);
            redirect("index.php?page=project_bugs&id=$pid", 'Баг добавлен.');
        }
    }
    $projs = $pdo->query("SELECT id, name FROM projects")->fetchAll();
    $users = $pdo->query("SELECT id, full_name, role FROM users WHERE login != 'admin'")->fetchAll();
    
    echo '<h2>Создать баг</h2><div class="form-card"><form method="POST"><input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
    <div class="form-group"><label>Проект</label><select name="project_id" required><option value="">-- Выбрать --</option>';
    foreach ($projs as $pr) echo "<option value='{$pr['id']}' ".($pr['id']==$preselect?'selected':'').">{$pr['name']}</option>";
    echo '</select></div>
    <div class="form-group"><label>Название</label><input type="text" name="title" required></div>
    <div class="form-group"><label>Описание</label><textarea name="description"></textarea></div>
    <div class="form-group"><label>Приоритет</label><select name="priority"><option value="Critical">Critical</option><option value="High">High</option><option value="Medium" selected>Medium</option><option value="Low">Low</option></select></div>
    <div class="form-group"><label>Статус</label><select name="status"><option value="Open" selected>Open</option><option value="In Progress">In Progress</option><option value="Closed">Closed</option></select></div>
    <div class="form-group"><label>Исполнитель</label><select name="assignee_id"><option value="">-- Не назначен --</option>';
    foreach ($users as $u) echo "<option value='{$u['id']}'>{$u['full_name']} ({$u['role']})</option>";
    echo '</select></div><button type="submit" class="btn">Сохранить</button></form></div>';
}

elseif ($page === 'bug_edit') {
    $bid = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM bugs WHERE id = ?"); $stmt->execute([$bid]); $b = $stmt->fetch();
    if (!$b) redirect('index.php?page=bugs', 'Баг не найден', 'error');

    $canEdit = $_SESSION['user']['role'] === 'admin' || $_SESSION['user']['id'] == $b['author_id'] || $_SESSION['user']['id'] == $b['assignee_id'];
    if (!$canEdit) redirect('index.php?page=bugs', 'У вас нет прав на редактирование этого бага.', 'error');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) die("CSRF Error");
        $stat = $_POST['status'] ?? $b['status']; $prio = $_POST['priority'] ?? $b['priority'];
        $assignee = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;
        $upd = $pdo->prepare("UPDATE bugs SET status = ?, priority = ?, assignee_id = ? WHERE id = ?");
        $upd->execute([$stat, $prio, $assignee, $bid]);
        redirect("index.php?page=project_bugs&id={$b['project_id']}", 'Баг обновлен.');
    }

    $users = $pdo->query("SELECT id, full_name, role FROM users WHERE login != 'admin'")->fetchAll();
    echo '<h2>Редактировать баг: '.htmlspecialchars($b['title']).'</h2><div class="form-card"><form method="POST"><input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">
    <div class="form-group"><label>Статус</label><select name="status">
        <option value="Open" '.($b['status']=='Open'?'selected':'').'>Open</option>
        <option value="In Progress" '.($b['status']=='In Progress'?'selected':'').'>In Progress</option>
        <option value="Closed" '.($b['status']=='Closed'?'selected':'').'>Closed</option>
    </select></div>
    <div class="form-group"><label>Приоритет</label><select name="priority">
        <option value="Critical" '.($b['priority']=='Critical'?'selected':'').'>Critical</option>
        <option value="High" '.($b['priority']=='High'?'selected':'').'>High</option>
        <option value="Medium" '.($b['priority']=='Medium'?'selected':'').'>Medium</option>
        <option value="Low" '.($b['priority']=='Low'?'selected':'').'>Low</option>
    </select></div>
    <div class="form-group"><label>Исполнитель</label><select name="assignee_id"><option value="">-- Не назначен --</option>';
    foreach ($users as $u) echo "<option value='{$u['id']}' ".($u['id']==$b['assignee_id']?'selected':'').">{$u['full_name']} ({$u['role']})</option>";
    echo '</select></div><button type="submit" class="btn">Обновить</button></form></div>';
}

$content = ob_get_clean();
?>