<?php
declare(strict_types=1);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) require $autoload;
spl_autoload_register(fn($c) => is_file($f = __DIR__ . "/$c.php") ? require $f : null);
require __DIR__ . '/helpers.php';

// Log PHP warnings/notices, then let PHP continue its normal handling.
set_error_handler(function (int $no, string $msg, string $file, int $line): bool {
    Logger::write(in_array($no, [E_WARNING, E_USER_WARNING, E_DEPRECATED], true) ? 'WARNING' : 'NOTICE', $msg, $file, $line);
    return false;
});
// Log uncaught exceptions (including database errors) and show a safe message.
set_exception_handler(function (Throwable $t): void {
    Logger::write($t instanceof PDOException ? 'DB' : 'ERROR', get_class($t) . ': ' . $t->getMessage(), $t->getFile(), $t->getLine());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo cfg('debug') ? get_class($t) . ': ' . $t->getMessage() . "\n\nSee diagnostics.php for the full log." : 'Something went wrong. Please try again later.';
});
