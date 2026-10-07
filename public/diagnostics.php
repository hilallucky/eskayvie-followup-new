<?php
require __DIR__ . '/../src/bootstrap.php';
if (!cfg('debug')) { http_response_code(404); exit('Not found'); }   // only available while debug = true

$checks = [
    'PHP version'            => [PHP_VERSION, version_compare(PHP_VERSION, '8.1', '>=')],
    'pdo_mysql extension'    => [extension_loaded('pdo_mysql') ? 'loaded' : 'missing', extension_loaded('pdo_mysql')],
    'mbstring extension'     => [extension_loaded('mbstring') ? 'loaded' : 'missing', extension_loaded('mbstring')],
    'PhpSpreadsheet (XLSX)'  => [class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class) ? 'installed' : 'run composer install', class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)],
    'storage/logs writable'  => [is_writable(dirname(Logger::file())) ? 'yes' : 'no', is_writable(dirname(Logger::file()))],
];

$db = cfg('db'); $dbError = null; $dbInfo = []; $tables = [];
try {
    $pdo = Database::pdo();
    $dbInfo = ['Server version' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'Current database' => $pdo->query('SELECT DATABASE()')->fetchColumn()];
    foreach (['ranks', 'members', 'users', 'followups'] as $t) {
        try { $tables[$t] = (string)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() . ' rows'; }
        catch (PDOException $e) { $tables[$t] = null; Logger::write('DB', "Table check failed for $t: " . $e->getMessage()); }
    }
} catch (Throwable $t) { $dbError = $t->getMessage(); }

$ok = fn(bool $b) => $b ? '<span class="font-bold text-brand-forest">✓</span>' : '<span class="font-bold text-red-600">✗</span>';
component('layout-start', ['title' => 'Diagnostics', 'wide' => true]); ?>
<p class="mb-6 rounded border border-yellow-400 bg-yellow-50 px-4 py-2 text-sm text-yellow-900">Debug mode is on. Set <code>'debug' =&gt; false</code> in config/config.php before going live.</p>

<div class="grid gap-6 lg:grid-cols-2">
  <section>
    <h2 class="mb-2 font-semibold text-brand-purple">Environment</h2>
    <table class="w-full text-sm"><?php foreach ($checks as $k => [$v, $good]): ?>
      <tr class="border-b"><td class="py-1"><?= e($k) ?></td><td><?= e($v) ?></td><td class="text-right"><?= $ok($good) ?></td></tr>
    <?php endforeach; ?></table>
  </section>

  <section>
    <h2 class="mb-2 font-semibold text-brand-purple">Database connection</h2>
    <table class="w-full text-sm">
      <tr class="border-b"><td class="py-1">Host / database / user</td><td><?= e("{$db['host']} / {$db['name']} / {$db['user']}") ?></td><td></td></tr>
      <tr class="border-b"><td class="py-1">Status</td><td><?= $dbError ? 'Failed' : 'Connected' ?></td><td class="text-right"><?= $ok(!$dbError) ?></td></tr>
      <?php foreach ($dbInfo as $k => $v): ?><tr class="border-b"><td class="py-1"><?= e($k) ?></td><td><?= e($v) ?></td><td></td></tr><?php endforeach; ?>
      <?php foreach ($tables as $t => $c): ?><tr class="border-b"><td class="py-1">Table <code><?= e($t) ?></code></td><td><?= $c === null ? 'missing / unreadable (import sql/schema.sql)' : e($c) ?></td><td class="text-right"><?= $ok($c !== null) ?></td></tr><?php endforeach; ?>
    </table>
    <?php if ($dbError) component('alert', ['message' => $dbError]); ?>
  </section>
</div>

<section class="mt-8">
  <h2 class="mb-2 font-semibold text-brand-purple">Error log (newest first, last 100)</h2>
  <?php $lines = Logger::tail(100); if (!$lines): ?>
    <p class="text-sm text-gray-500">No entries yet.</p>
  <?php else: ?>
    <pre class="max-h-[32rem] overflow-auto rounded bg-gray-900 p-4 text-xs leading-relaxed text-gray-100"><?php foreach ($lines as $l): ?><span class="<?= str_contains($l, ' DB ') || str_contains($l, ' ERROR ') ? 'text-red-300' : (str_contains($l, ' WARNING ') ? 'text-yellow-300' : '') ?>"><?= e($l) ?></span>
<?php endforeach; ?></pre>
  <?php endif; ?>
  <p class="mt-2 text-xs text-gray-500">File: storage/logs/app.log</p>
</section>
<?php component('layout-end');
