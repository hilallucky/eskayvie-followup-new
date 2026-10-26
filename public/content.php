<?php
require __DIR__ . '/../src/bootstrap.php';
$folders = ContentLibrary::folders();
$folder = trim((string)($_GET['folder'] ?? ''), '/');
if ($folder !== '' && !in_array($folder, $folders, true)) {
    http_response_code(404);
    $folder = '';
}
$files = ContentLibrary::files($folder);
$selected = isset($_GET['file']) ? ContentLibrary::file((string)$_GET['file']) : null;
if (isset($_GET['file']) && $selected === null) http_response_code(404);
$folderLabel = $folder === '' ? 'All contents' : basename($folder);
component('layout-start', ['title' => 'Bank Content', 'wide' => true]); ?>

<div class="min-h-[70vh] overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
  <div class="flex min-h-[70vh]">
    <aside class="hidden w-60 shrink-0 border-r border-slate-200 bg-white p-4 sm:block">
      <a href="content.php" class="mb-5 flex items-center gap-2 rounded-lg bg-purple-50 px-3 py-2 font-semibold text-brand-purple">
        <span aria-hidden="true">▤</span> Bank Content
      </a>
      <nav aria-label="Content folders" class="space-y-1 text-sm">
        <a href="content.php" class="<?= $folder === '' ? 'bg-purple-50 text-brand-purple' : 'text-slate-700 hover:bg-slate-100' ?> block rounded-lg px-3 py-2">Contents</a>
        <p class="px-3 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Folders</p>
        <?php foreach ($folders as $item): ?>
          <?php $depth = substr_count($item, '/'); ?>
          <a href="content.php?folder=<?= rawurlencode($item) ?>" class="<?= $folder === $item ? 'bg-purple-50 text-brand-purple' : 'text-slate-700 hover:bg-slate-100' ?> block rounded-lg py-2 pr-2" style="padding-left: <?= 12 + $depth * 16 ?>px">
            <span aria-hidden="true">▱</span> <?= e(basename($item)) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <p class="mt-8 border-t border-slate-100 px-3 pt-4 text-xs leading-5 text-slate-500">Documents here are publicly accessible. Do not place private or confidential files in this library.</p>
    </aside>

    <section class="min-w-0 flex-1 p-4 sm:p-6">
      <?php if ($selected): ?>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <a href="content.php<?= $folder !== '' ? '?folder=' . rawurlencode($folder) : '' ?>" class="text-sm text-brand-purple hover:underline">← Back to <?= e($folderLabel) ?></a>
            <h2 class="mt-2 text-lg font-semibold text-slate-900"><?= e($selected['name']) ?></h2>
            <p class="mt-1 text-sm text-slate-500"><?= e($selected['type']) ?> · <?= e(number_format($selected['size'] / 1024, 0)) ?> KB</p>
          </div>
          <a href="content-file.php?file=<?= rawurlencode((string)$_GET['file']) ?>&amp;download=1" class="rounded-lg bg-brand-purple px-4 py-2 text-sm font-semibold text-white hover:bg-brand-deep">Download file</a>
        </div>
        <iframe title="Preview of <?= e($selected['name']) ?>" src="content-file.php?file=<?= rawurlencode((string)$_GET['file']) ?>" class="h-[65vh] min-h-96 w-full rounded-lg border border-slate-200 bg-white"></iframe>
      <?php else: ?>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
          <div>
            <p class="text-sm text-slate-500">Contents<?= $folder !== '' ? ' / ' . e($folder) : '' ?></p>
            <h2 class="mt-1 text-xl font-semibold text-slate-900"><?= e($folderLabel) ?></h2>
          </div>
          <p class="text-sm text-slate-500"><?= count($files) ?> <?= count($files) === 1 ? 'item' : 'items' ?></p>
        </div>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <div class="hidden grid-cols-[minmax(15rem,1fr)_minmax(7rem,0.4fr)_minmax(8rem,0.5fr)_minmax(5rem,0.35fr)_5rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-500 sm:grid">
            <span>Name</span><span>Type</span><span>Last modified</span><span>File size</span><span class="text-right">Action</span>
          </div>
          <?php if (!$files): ?>
            <div class="px-5 py-14 text-center">
              <p class="font-medium text-slate-700">No files in this folder yet</p>
              <p class="mt-2 text-sm text-slate-500">Add published documents to <code class="rounded bg-slate-100 px-1.5 py-0.5">storage/content/<?= e($folder === '' ? '' : $folder) ?></code> on the server.</p>
            </div>
          <?php else: foreach ($files as $file): ?>
            <div class="grid gap-2 border-b border-slate-100 px-4 py-4 last:border-0 sm:grid-cols-[minmax(15rem,1fr)_minmax(7rem,0.4fr)_minmax(8rem,0.5fr)_minmax(5rem,0.35fr)_5rem] sm:items-center sm:gap-3">
              <a href="content.php?file=<?= rawurlencode($file['path']) ?>&amp;folder=<?= rawurlencode($folder) ?>" class="min-w-0 text-sm font-medium text-brand-deep hover:underline">
                <?= e($file['name']) ?>
                <span class="mt-1 block truncate text-xs font-normal text-slate-500 sm:hidden"><?= e($file['type']) ?> · <?= e(number_format($file['size'] / 1024, 0)) ?> KB</span>
              </a>
              <span class="hidden text-xs text-slate-600 sm:block"><?= e($file['type']) ?></span>
              <span class="hidden text-xs text-slate-600 sm:block"><?= e(date('M j, Y', $file['modified'])) ?></span>
              <span class="hidden text-xs text-slate-600 sm:block"><?= e(number_format($file['size'] / 1024, 0)) ?> KB</span>
              <a aria-label="Download <?= e($file['name']) ?>" href="content-file.php?file=<?= rawurlencode($file['path']) ?>&amp;download=1" class="justify-self-start text-sm text-brand-purple hover:underline sm:justify-self-end">Download</a>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php component('layout-end'); ?>
