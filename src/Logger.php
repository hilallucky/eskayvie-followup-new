<?php
final class Logger {
    public static function file(): string { return __DIR__ . '/../storage/logs/app.log'; }

    public static function write(string $level, string $msg, string $file = '', int $line = 0): void {
        $dir = dirname(self::file());
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $where = $file ? ' [' . basename($file) . ":$line]" : '';
        $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
        @file_put_contents(self::file(), date('Y-m-d H:i:s') . " $level $msg$where $uri\n", FILE_APPEND | LOCK_EX);
    }

    /** Newest first. */
    public static function tail(int $n = 100): array {
        if (!is_file(self::file())) return [];
        $lines = file(self::file(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_reverse(array_slice($lines, -$n));
    }
}
