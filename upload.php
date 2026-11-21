<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    back(null, 'Для загрузки используйте форму на главной странице.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postLimit = iniToBytes((string) ini_get('post_max_size'));
// При превышении post_max_size PHP очищает $_FILES и $_POST до запуска скрипта.
if ($postLimit > 0 && $contentLength > $postLimit) {
    back(null, 'Размер запроса превышает post_max_size (' . ini_get('post_max_size') . ').');
}
if (empty($_FILES)) {
    back(null, 'Файлы не переданы. Проверьте выбор файла и enctype="multipart/form-data".');
}
if (!ensureUploadDir() || !is_writable(UPLOAD_DIR)) {
    back(null, 'Папка uploads недоступна для записи.');
}

/** @return array{ok:bool,text:string} */
function saveUploadedFile(array $file): array
{
    $original = is_string($file['name'] ?? null) ? $file['name'] : '';
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    $errorText = match ($errorCode) {
        UPLOAD_ERR_INI_SIZE => 'Превышен upload_max_filesize (' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_FORM_SIZE => 'Превышен лимит MAX_FILE_SIZE из формы.',
        UPLOAD_ERR_PARTIAL => 'Файл был загружен не полностью.',
        UPLOAD_ERR_NO_FILE => 'Файл не выбран.',
        UPLOAD_ERR_NO_TMP_DIR => 'На сервере отсутствует временная папка.',
        UPLOAD_ERR_CANT_WRITE => 'Серверу не удалось записать файл на диск.',
        UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением PHP.',
        UPLOAD_ERR_OK => '',
        default => 'Неизвестная ошибка загрузки (' . $errorCode . ').',
    };
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'text' => $errorText];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size > SCRIPT_MAX_BYTES) {
        return ['ok' => false, 'text' => 'Размер файла больше 5 МБ (лимит приложения).'];
    }
    $name = cleanFileName($original);
    if (!isAllowedName($name)) {
        return ['ok' => false, 'text' => 'Недопустимое расширение или имя: ' . $original . '.'];
    }

    $tmpName = $file['tmp_name'] ?? null;
    if (!is_string($tmpName) || !is_uploaded_file($tmpName)) {
        return ['ok' => false, 'text' => 'Не найден корректный временный загруженный файл.'];
    }

    // При совпадении имени добавляем метку времени, при необходимости — порядковый номер.
    $targetName = $name;
    if (file_exists(UPLOAD_DIR . $targetName)) {
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $timestamp = date('Ymd_His');
        $counter = 0;
        do {
            $suffix = $counter === 0 ? '' : '_' . $counter;
            $targetName = $timestamp . '_' . $stem . $suffix . '.' . $ext;
            ++$counter;
        } while (file_exists(UPLOAD_DIR . $targetName));
    }

    if (!move_uploaded_file($tmpName, UPLOAD_DIR . $targetName)) {
        return ['ok' => false, 'text' => 'Не удалось сохранить файл в uploads.'];
    }
    appendLog('Загружен', $targetName);
    return ['ok' => true, 'text' => $targetName];
}

/** @var list<array<string,mixed>> $uploads */
$uploads = [];
if (isset($_FILES['userfile']) && is_array($_FILES['userfile'])) {
    $uploads[] = $_FILES['userfile'];
} elseif (isset($_FILES['files']) && is_array($_FILES['files'])) {
    $multi = $_FILES['files'];
    if (!isset($multi['name']) || !is_array($multi['name'])) {
        back(null, 'Неверный формат данных множественной загрузки.');
    }
    foreach ($multi['name'] as $key => $originalName) {
        $uploads[] = [
            'name' => $originalName,
            'type' => $multi['type'][$key] ?? '',
            'tmp_name' => $multi['tmp_name'][$key] ?? '',
            'error' => $multi['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $multi['size'][$key] ?? 0,
        ];
    }
} else {
    back(null, 'Файл не выбран.');
}

$successCount = 0;
$errors = [];
foreach ($uploads as $file) {
    $result = saveUploadedFile($file);
    if ($result['ok']) {
        ++$successCount;
    } else {
        $errors[] = $result['text'];
    }
}
$message = $successCount > 0
    ? ('Успешно загружено файлов: ' . $successCount . '.')
    : null;
$shortErrors = array_slice($errors, 0, 3);
$errorMessage = $shortErrors ? implode(' ', $shortErrors) . (count($errors) > 3 ? ' И ещё ошибок: ' . (count($errors) - 3) . '.' : '') : null;
back($message, $errorMessage);
