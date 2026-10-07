<?php
require __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json');
function out(int $status, array $body): never { http_response_code($status); echo json_encode($body); exit; }
$u = Auth::user() ?? out(401, ['ok' => false, 'error' => 'Please log in again.']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) out(419, ['ok' => false, 'error' => 'Session expired, reload the page.']);
$in = json_decode(file_get_contents('php://input'), true) ?: [];
try {
    $m = Members::find((string)($in['member_code'] ?? ''));
    if (!$m || $m['sponsor_code'] !== $u['member_code']) out(404, ['ok' => false, 'error' => 'Downline not found.']); // ownership check
    $field = (string)($in['field'] ?? ''); $value = $in['value'] ?? '';
    if (in_array($field, FollowUps::FIELDS, true)) FollowUps::save($u['username'], $m['member_code'], $field, $value);
    else Members::update($m['member_code'], $field, $value, $u['username']);
    out(200, ['ok' => true]);
} catch (InvalidArgumentException $x) { out(422, ['ok' => false, 'error' => $x->getMessage()]);
} catch (Throwable $x) { error_log((string)$x); out(500, ['ok' => false, 'error' => 'Server error.']); }
