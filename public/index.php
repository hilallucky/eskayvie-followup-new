<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$me = Members::find($u['member_code']) ?? redirect('logout.php');
$per = (int)cfg('per_page');
$total = Members::countDownline($me['member_code']);
$pages = max(1, (int)ceil($total / $per));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$rows = Members::downline($me['member_code'], $u['username'], $per, ($page - 1) * $per);
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

component('layout-start', ['title' => 'Follow Up Checklist', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]); ?>

<dl id="profile" class="mb-6 grid max-w-md grid-cols-[6rem_1fr] gap-y-1 text-sm">
  <dt class="text-gray-500">Name</dt><dd class="font-medium"><?= e($me['name']) ?> <span class="font-normal text-gray-500">(<?= e($u['username']) ?>)</span></dd>
  <dt class="text-gray-500">Rank</dt><dd class="font-medium"><?= e(Members::ranks()[$me['rank_code']] ?? $me['rank_code']) ?></dd>
  <dt class="text-gray-500">Join Date</dt><dd class="font-medium"><?= e(date('d M Y', strtotime($me['join_date']))) ?></dd>
</dl>

<details id="add-downline" class="mb-6 rounded border border-gray-300 p-4">
  <summary class="cursor-pointer font-semibold text-brand-purple">Add New Downline</summary>
  <form method="post" action="add-downline.php" class="mt-4 grid max-w-3xl gap-4 sm:grid-cols-4 sm:items-end">
    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
    <?php
    component('input', ['label' => 'Name', 'namex' => 'name', 'placeholder' => 'Input Downline Name']);
    component('select', ['label' => 'Rank', 'namex' => 'rank_code', 'options' => Members::ranks()]);
    component('input', ['label' => 'Join Date', 'namex' => 'join_date', 'type' => 'date', 'value' => date('Y-m-d')]);
    component('button', ['label' => 'Add', 'type' => 'submit']);
    ?>
  </form>
</details>

<div class="mb-3 flex flex-wrap items-center justify-between gap-2">
  <div class="flex gap-2">
    <?php component('button', ['label' => 'Export CSV', 'href' => 'export.php?format=csv', 'variant' => 'secondary']);
          component('button', ['label' => 'Export XLSX', 'href' => 'export.php?format=xlsx', 'variant' => 'ghost']); ?>
  </div>
  <span class="text-sm text-gray-600">Downline List · <?= $total ?> records · <?= $per ?>/page</span>
</div>

<div id="downline-list" class="overflow-x-auto rounded border border-gray-300">
  <table class="w-full border-collapse text-sm">
    <thead class="bg-brand-purple text-white">
      <tr class="text-center">
        <th rowspan="3" class="w-10 border border-white/30 px-2">No</th>
        <th rowspan="3" class="border border-white/30 px-2">Name</th>
        <th colspan="6" class="border border-white/30 py-1">Activities</th>
        <th rowspan="3" class="border border-white/30 px-2">Remarks</th>
        <th rowspan="3" class="border border-white/30 px-2">Status</th>
      </tr>
      <tr class="text-center">
        <th colspan="2" class="border border-white/30 py-1">Personal</th>
        <th colspan="4" class="border border-white/30 py-1">Training</th>
      </tr>
      <tr class="bg-brand-deep text-center text-xs">
        <th class="border border-white/30 px-2 py-1">Consume Product</th><th class="border border-white/30 px-2">Prospect List</th>
        <th class="border border-white/30 px-2">Welcome To Eskayvie</th><th class="border border-white/30 px-2">Product</th>
        <th class="border border-white/30 px-2">Marketing Plan</th><th class="border border-white/30 px-2">HBO</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="11" class="px-4 py-8 text-center text-gray-500">No downline yet. Use “Add New Downline” above.</td></tr><?php endif; ?>
      <?php foreach ($rows as $n => $r) component('downline-row', ['i' => ($page - 1) * $per + $n + 1, 'r' => $r]); ?>
    </tbody>
  </table>
</div>
<?php component('pagination', ['page' => $page, 'pages' => $pages]); component('layout-end');
