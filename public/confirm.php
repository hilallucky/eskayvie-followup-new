<?php
require __DIR__ . '/../src/bootstrap.php';
$ok = Users::confirm($_GET['token'] ?? '');
component('layout-start', ['title' => 'Confirm account']);
component('alert', ['type' => $ok ? 'ok' : 'error', 'message' => $ok ? 'Your account is confirmed. You can log in now.' : 'This link is invalid or already used.']);
component('button', ['label' => 'Go to login', 'href' => 'login.php']);
component('layout-end');
