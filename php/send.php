<?php

declare(strict_types=1);

// Обработчик формы: проверяет поля и отправляет письмо через mail().
// Значения приходят из переменных окружения сайта, не из кода.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    return trim((string) $value);
};

// Ловушка для ботов: поле скрыто от людей.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'Имя: от 1 до 100 символов.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Укажите корректный email.';
}
if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'Сообщение: от 1 до 5000 символов.';
}

if ($errors !== []) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

$to = $env('MAIL_TO');
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Получатель не настроен.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$from = $env('MAIL_FROM');
if ($from === '') {
    // Домен берём из конфигурации сервера, а не из заголовка Host от клиента.
    $host = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_SERVER['SERVER_NAME'] ?? ''));
    $from = $host !== '' ? 'no-reply@' . $host : $to;
}
if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
    $from = $to;
}
$prefix = $env('MAIL_SUBJECT_PREFIX');

// Тема без пользовательских данных: подстановка ломает заголовки письма.
// mail() не кодирует заголовки сам: кириллица без RFC 2047 приходит пустой.
$subject = mb_encode_mimeheader(trim('Заявка с сайта ' . $prefix), 'UTF-8', 'B');
$body = "Имя: {$name}\nEmail: {$email}\n\n{$message}\n";

$headers = implode("\r\n", [
    'From: ' . $from,
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=utf-8',
    'Content-Transfer-Encoding: 8bit',
    'MIME-Version: 1.0',
]);

$sent = mail($to, $subject, $body, $headers);

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Письмо не отправилось. Попробуйте позже.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
