<?php
function cfg(string $k): mixed { static $c; $c ??= require __DIR__ . '/../config/config.php'; return $c[$k]; }
function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
/** Render views/components/<name>.php with $props extracted as variables. */
function component(string $name, array $props = []): void { extract($props, EXTR_SKIP); include __DIR__ . "/../views/components/$name.php"; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_check(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }
function redirect(string $url): never { header("Location: $url"); exit; }
