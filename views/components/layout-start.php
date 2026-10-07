<?php $user = $user ?? null; $wide = $wide ?? false; $w = $wide ? 'max-w-7xl' : 'max-w-md'; ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf" content="<?= e(csrf()) ?>">
<title><?= e($title) ?> · eskayvie</title>
<!-- Dev: Tailwind CDN. For production, compile with the Tailwind CLI using the same color tokens. -->
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{purple:'#611DB5',deep:'#441170',violet:'#9F2FFF',green:'#00CD00',forest:'#339900',lime:'#55FF00'}}}}}</script>
<style>
@media (min-width: 768px) {
  [data-sidebar][data-collapsed="true"] { display: none; }
}
@media (max-width: 767px) {
  [data-sidebar] { transform: translateX(-100%); }
  [data-sidebar][data-open="true"] { transform: translateX(0); }
}
</style>
</head>
<body class="min-h-screen bg-white text-gray-800 antialiased">
<header class="border-b-4 border-brand-green bg-brand-purple text-white">
  <div class="mx-auto flex items-center justify-between px-4 py-3 <?= $w ?>">
    <div class="flex items-center gap-3">
      <?php if ($user): ?>
      <button id="sidebar-toggle" type="button" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false" class="flex h-10 w-10 items-center justify-center rounded hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white" title="Toggle navigation">
        <span class="flex w-5 flex-col gap-1" aria-hidden="true"><span class="h-0.5 w-full bg-current"></span><span class="h-0.5 w-full bg-current"></span><span class="h-0.5 w-full bg-current"></span></span>
      </button>
      <?php endif; ?>
      <a href="index.php" class="text-2xl font-semibold tracking-tight">eskayvie</a>
    </div>
    <?php if ($user): ?>
    <form method="post" action="logout.php" class="flex items-center gap-3 text-sm">
      <span><?= e($user['username']) ?></span>
      <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
      <button class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">Logout</button>
    </form>
    <?php endif; ?>
  </div>
</header>
<?php if ($user): ?>
<div class="mx-auto flex max-w-7xl">
  <aside id="app-sidebar" data-sidebar data-open="false" data-collapsed="false" class="fixed inset-y-0 left-0 z-40 w-64 border-r border-gray-200 bg-white pt-20 shadow-lg transition-transform duration-200 md:sticky md:top-0 md:z-auto md:h-[calc(100vh-4rem)] md:w-56 md:shrink-0 md:pt-6 md:shadow-none">
    <nav aria-label="Main navigation" class="space-y-1 px-3">
      <details open class="group">
        <summary class="cursor-pointer rounded px-3 py-2 text-sm font-semibold text-brand-purple hover:bg-purple-50">Form</summary>
        <div class="ml-3 border-l border-gray-200 pl-2">
          <a href="index.php" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Follow Up Checklist</a>
          <a href="monthly-target.php" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Monthly Target</a>
          <a href="activity-record-plan.php" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Activity Record &amp; Plan</a>
          <a href="customer-list.php" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Customer List</a>
          <!-- <a href="index.php#add-downline" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Add New Downline</a>
          <a href="index.php#downline-list" class="block rounded px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Downline List</a> -->
        </div>
      </details>
      <div class="my-3 border-t border-gray-200"></div>
      <form method="post" action="logout.php" class="px-3">
        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
        <button type="submit" class="text-sm font-medium text-gray-600 hover:text-red-700">Logout</button>
      </form>
    </nav>
  </aside>
  <button id="sidebar-backdrop" type="button" aria-label="Close menu" class="fixed inset-0 z-30 hidden bg-black/40 md:hidden"></button>
  <main class="min-w-0 flex-1 px-4 py-8">
<?php else: ?>
<main class="mx-auto px-4 py-8 <?= $w ?>">
<?php endif; ?>
<h1 class="mb-6 text-2xl font-bold text-brand-forest"><?= e($title) ?></h1>
