<?php
if (!is_dir('data')) mkdir('data', 0777, true);
if (!is_dir('sessions')) mkdir('sessions', 0777, true);

$pdo = new PDO("sqlite:data/bughunter.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents('schema.sql'));
echo "База данных успешно создана! <a href='index.php'>Перейти на сайт</a>";
?>