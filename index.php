<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

$maxFile = (string) ini_get('upload_max_filesize');
$maxPost = (string) ini_get('post_max_size');
$msg = isset($_GET['msg']) && is_string($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : '';
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['name', 'size', 'date'], true) ? $_GET['sort'] : 'name';
$files = listUploadedFiles($sort);
$totalBytes = array_sum(array_column($files, 'size'));
$log = readLog(10);
?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Файловый менеджер</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header class="page-header">
        <div><div class="eyebrow">ПРАКТИЧЕСКАЯ РАБОТА | PHP 8</div>
        <h1>Файловый менеджер</h1>
        <p class="subtitle">Загружайте, просматривайте, скачивайте и удаляйте файлы</p></div>
        <a class="settings-link" href="info.php">Настройки PHP</a>
    </header>

    <?php if ($msg !== ''): ?><div class="alert success" role="status"><?= escape($msg) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert error" role="alert"><?= escape($error) ?></div><?php endif; ?>

    <section class="card">
        <div class="card-heading"><h2>Загрузка файлов</h2><span class="step-badge">01</span></div>
        <div class="limits">Ограничения сервера: один файл — до <b><?= escape($maxFile) ?></b>, весь запрос — до <b><?= escape($maxPost) ?></b>. Ограничение приложения — <b>5 МБ на файл</b>.</div>
        <div class="upload-columns">
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <label for="one-file">Один файл</label>
                <input id="one-file" type="file" name="userfile" required>
                <button type="submit">Загрузить файл</button>
            </form>
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <label for="many-files">Несколько файлов <span class="muted">(доп. задание)</span></label>
                <input id="many-files" type="file" name="files[]" multiple required>
                <button type="submit" class="button-secondary">Загрузить несколько</button>
            </form>
        </div>
        <p class="hint">Разрешены: jpg, jpeg, png, gif, pdf, txt, docx, xlsx, zip. PHP, EXE и другие форматы запрещены. Для нескольких файлов учитывается также общий размер POST-запроса.</p>
    </section>

    <section class="card">
        <div class="card-heading"><h2>Загруженные файлы</h2><span class="step-badge">02</span></div>
        <div class="sort-row"><span>Сортировать:</span>
            <a class="sort-chip <?= $sort === 'name' ? 'active' : '' ?>" href="?sort=name">По имени</a>
            <a class="sort-chip <?= $sort === 'size' ? 'active' : '' ?>" href="?sort=size">По размеру</a>
            <a class="sort-chip <?= $sort === 'date' ? 'active' : '' ?>" href="?sort=date">По дате</a>
        </div>
        <div class="table-wrap"><table>
            <thead><tr><th>№</th><th>Имя файла</th><th>Размер</th><th>Дата загрузки</th><th>Действие</th></tr></thead>
            <tbody>
            <?php if (!$files): ?>
                <tr><td colspan="5" class="empty">Файлов пока нет. Загрузите первый файл через форму выше.</td></tr>
            <?php else: ?>
                <?php foreach ($files as $i => $file): ?>
                <?php $name = $file['name']; $link = 'uploads/' . rawurlencode($name); ?>
                <tr>
                    <td class="muted"><?= $i + 1 ?></td>
                    <td><div class="file-cell">
                        <?php if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif'], true)): ?>
                            <img class="thumbnail" src="<?= escape($link) ?>" width="50" height="50" loading="lazy" alt="Миниатюра">
                        <?php else: ?><span class="file-icon" aria-hidden="true">📄</span><?php endif; ?>
                        <a href="<?= escape($link) ?>" download><?= escape($name) ?></a>
                    </div></td>
                    <td><?= escape(formatSize($file['size'])) ?></td>
                    <td><?= date('d.m.Y H:i', $file['mtime']) ?></td>
                    <td><a class="btn-delete" href="delete.php?file=<?= rawurlencode($name) ?>" onclick="return confirm('Удалить этот файл?')">Удалить</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table></div>
        <p class="total">Всего файлов: <b><?= count($files) ?></b> · Общий размер: <b><?= escape(formatSize((int) $totalBytes)) ?></b></p>
    </section>

    <section class="card">
        <div class="card-heading"><h2>Журнал действий</h2><span class="step-badge">03</span></div>
        <p class="hint">Последние 10 успешных загрузок и удалений из <code>log.txt</code>.</p>
        <?php if (!$log): ?><p class="empty-log">Записей пока нет.</p>
        <?php else: ?><ul class="log-list"><?php foreach ($log as $entry): ?><li><?= escape($entry) ?></li><?php endforeach; ?></ul><?php endif; ?>
    </section>

    <footer>Практическая работа «Работа с файлами в PHP</footer>
</div>
</body>
</html>
