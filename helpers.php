<?php
declare(strict_types=1);

const UPLOAD_DIR = __DIR__ . '/uploads/';
const SCRIPT_MAX_BYTES = 5 * 1024 * 1024;
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'docx', 'xlsx', 'zip'];

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function iniToBytes(string $value): int
{
    $value = trim($value);
    $number = (float) $value;
    $suffix = strtoupper(substr($value, -1));
    $factor = match ($suffix) {
        'G' => 1024 ** 3,
        'M' => 1024 ** 2,
        'K' => 1024,
        default => 1,
    };
    return (int) ($number * $factor);
}

function formatSize(int $bytes): string
{
    $size = (float) $bytes;
    $units = ['Б', 'КБ', 'МБ', 'ГБ'];
    $index = 0;
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        ++$index;
    }
    if ($index === 0) {
        return $bytes . ' Б';
    }
    return rtrim(rtrim(number_format($size, 1, '.', ''), '0'), '.') . ' ' . $units[$index];
}

function back(?string $message = null, ?string $error = null): void
{
    $params = [];
    if ($message !== null && $message !== '') {
        $params['msg'] = $message;
    }
    if ($error !== null && $error !== '') {
        $params['error'] = $error;
    }
    header('Location: index.php' . ($params ? '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) : ''), true, 303);
    exit;
}

function cleanFileName(string $original): string
{
    // На Windows путь может содержать обратные косые черты.
    $basename = basename(str_replace('\\', '/', $original));
    $sanitized = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $basename);
    if ($sanitized === null) {
        return '';
    }
    // Точки в начале запрещены: нельзя создать .htaccess и другие служебные файлы.
    $sanitized = trim($sanitized, " ._-\t\r\n");
    return substr($sanitized, 0, 180);
}

function isAllowedName(string $name): bool
{
    if ($name === '' || $name[0] === '.' || $name === '.' || $name === '..') {
        return false;
    }
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
        return false;
    }
    // Дополнительная защита при переносе проекта на Apache с MultiViews/особой конфигурацией.
    return preg_match('/\.(?:php\d*|phtml|phar|cgi|pl|py|sh|shtml|htaccess|user\.ini)\./i', $name) !== 1;
}

function ensureUploadDir(): bool
{
    return is_dir(UPLOAD_DIR) || @mkdir(UPLOAD_DIR, 0775, true);
}

function appendLog(string $action, string $filename): void
{
    $safeName = str_replace(["\r", "\n", "\t"], ' ', $filename);
    $line = date('[d.m.Y H:i:s] ') . $action . ': ' . $safeName . "\n";
    // Журнал лежит вне uploads и не попадает в таблицу файлов.
    @file_put_contents(__DIR__ . '/log.txt', $line, FILE_APPEND | LOCK_EX);
}

/** @return list<string> */
function readLog(int $count = 10): array
{
    $path = __DIR__ . '/log.txt';
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return is_array($lines) ? array_reverse(array_slice($lines, -$count)) : [];
}

/** @return list<array{name:string,size:int,mtime:int,extension:string}> */
function listUploadedFiles(string $sort): array
{
    if (!ensureUploadDir()) {
        return [];
    }
    $entries = scandir(UPLOAD_DIR);
    if ($entries === false) {
        return [];
    }
    $files = [];
    foreach ($entries as $name) {
        if ($name === '.' || $name === '..' || !isAllowedName($name)) {
            continue;
        }
        $path = UPLOAD_DIR . $name;
        if (!is_file($path)) {
            continue;
        }
        $files[] = [
            'name' => $name,
            'size' => (int) filesize($path),
            'mtime' => (int) filemtime($path),
            'extension' => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
        ];
    }
    usort($files, static function (array $a, array $b) use ($sort): int {
        $comparison = match ($sort) {
            'size' => $a['size'] <=> $b['size'],
            'date' => $b['mtime'] <=> $a['mtime'],
            default => strnatcasecmp($a['name'], $b['name']),
        };
        return $comparison ?: strcmp($a['name'], $b['name']);
    });
    return $files;
}
