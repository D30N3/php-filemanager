<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

$raw = $_GET['file'] ?? '';
if (!is_string($raw) || $raw === '') {
    back(null, 'Не указано имя файла для удаления.');
}
$name = basename(str_replace('\\', '/', $raw));
$path = UPLOAD_DIR . $name;
if (!isAllowedName($name) || !is_file($path)) {
    back(null, 'Файл не найден или его удаление запрещено.');
}
if (!@unlink($path)) {
    back(null, 'Не удалось удалить файл. Проверьте права доступа.');
}
appendLog('Удалён', $name);
back('Файл «' . $name . '» удалён.');
