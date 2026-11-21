<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
$keys = [
    'file_uploads',
    'upload_max_filesize',
    'post_max_size',
    'max_file_uploads',
    'memory_limit',
    'max_input_time',
    'max_execution_time',
];
$iniFile = php_ini_loaded_file();
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Настройки PHP — Файловый менеджер</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
    <div class="back-link"><a href="index.php">← К файловому менеджеру</a></div>
    <header><h1>Настройки загрузки PHP</h1><p class="subtitle">Текущие значения, применённые сервером</p></header>
    <section class="card">
        <div class="table-wrap"><table>
            <thead><tr><th>Директива</th><th>Значение</th></tr></thead>
            <tbody>
                <?php foreach ($keys as $key): ?>
                <tr><td><code><?= escape($key) ?></code></td><td><?= escape((string) ini_get($key)) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
        <p class="hint">Файл настроек: <code><?= escape($iniFile ?: '(php.ini не найден)') ?></code></p>
        <p class="hint">Изменения php.ini вступают в силу после перезапуска сервера (Ctrl+C, затем повторный запуск).</p>
    </section>
</main>
</body>
</html>
