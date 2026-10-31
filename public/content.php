<?php
require __DIR__ . '/../src/bootstrap.php';
$user = Auth::user();
$canManage = Auth::canManageContent();
$isContentAdmin = Auth::isContentAdministrator();
$qrBaseUrl = rtrim((string)cfg('base_url'), '/');
$qrTargetForItem = static function (array $item) use ($qrBaseUrl): string {
    return $qrBaseUrl . '/content.php?' . http_build_query(
        $item['kind'] === 'folder'
            ? ['folder' => $item['id']]
            : ['folder' => (string)($item['parent_id'] ?? ''), 'item' => $item['id']]
    );
};
if (isset($_GET['qr'])) {
    if (!$canManage) {
        http_response_code(403);
        exit('You do not have permission to generate Bank Content QR codes.');
    }

    $qrItem = ContentLibrary::find((string)$_GET['qr']);
    if (!$qrItem || !in_array($qrItem['kind'], ['folder', 'file'], true) || ($qrItem['kind'] === 'file' && !$qrItem['stored_name'])) {
        http_response_code(404);
        exit('The item does not have a QR code destination.');
    }

    $qrCode = new Endroid\QrCode\QrCode(
        data: $qrTargetForItem($qrItem),
        encoding: new Endroid\QrCode\Encoding\Encoding('UTF-8'),
        errorCorrectionLevel: Endroid\QrCode\ErrorCorrectionLevel::Medium,
        size: 160,
        margin: 4
    );
    $qrImage = (new Endroid\QrCode\Writer\SvgWriter())->write($qrCode);

    session_write_close();
    header('Content-Type: ' . $qrImage->getMimeType());
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo $qrImage->getString();
    exit;
}

$approvedOnly = !$canManage;
$folders = ContentLibrary::folders($approvedOnly);
$view = (string)($_GET['view'] ?? '');
if (!in_array($view, ['', 'recent', 'starred', 'shared'], true)) $view = '';
$folderParam = (string)($_GET['folder'] ?? '');
$folderId = $folderParam === '' ? null : $folderParam;
if ($view === '' && $folderId === null && !array_key_exists('folder', $_GET)) {
    foreach ($folders as $rootFolder) {
        if ($rootFolder['name'] === 'Accounts') {
            $folderId = $rootFolder['id'];
            break;
        }
    }
}
$currentFolder = $folderId === null ? null : ContentLibrary::find($folderId, $approvedOnly);
if ($folderId !== null && (!$currentFolder || $currentFolder['kind'] !== 'folder')) {
    http_response_code(404);
    $folderId = null;
    $currentFolder = null;
}
$query = trim((string)($_GET['q'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'name');
if (!in_array($sort, ['name', 'modified', 'size'], true)) $sort = 'name';
$items = $view === '' ? ContentLibrary::children($folderId, $approvedOnly) : ContentLibrary::viewItems($view, $approvedOnly);
if ($view !== '') {
    $items = array_values(array_filter($items, static function (array $item) use ($view): bool {
        return match ($view) {
            'starred' => (bool)$item['starred'],
            'shared' => (bool)$item['shared'],
            default => true,
        };
    }));
}
if ($query !== '') {
    $items = array_values(array_filter($items, static fn(array $item): bool => stripos($item['name'], $query) !== false));
}
usort($items, static function (array $a, array $b) use ($sort): int {
    $kindOrder = ($a['kind'] === 'folder' ? 0 : 1) <=> ($b['kind'] === 'folder' ? 0 : 1);
    if ($kindOrder !== 0) return $kindOrder;
    if ($sort === 'modified') return strcmp($b['modified_at'], $a['modified_at']) ?: strnatcasecmp($a['name'], $b['name']);
    if ($sort === 'size') return (int)$b['size'] <=> (int)$a['size'] ?: strnatcasecmp($a['name'], $b['name']);
    return strnatcasecmp($a['name'], $b['name']);
});
$selected = isset($_GET['item']) ? ContentLibrary::find((string)$_GET['item'], $approvedOnly) : null;
$selectedFile = $selected && $selected['kind'] === 'file' ? ContentLibrary::file($selected['id'], $approvedOnly) : null;
if (isset($_GET['item']) && !$selectedFile) {
    http_response_code(404);
    $selected = null;
}
$flash = $_SESSION['content_flash'] ?? null;
unset($_SESSION['content_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) redirect('login.php');
    if (!$canManage) {
        http_response_code(403);
        exit('You do not have permission to manage Bank Content.');
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] && $_FILES === []) {
        http_response_code(413);
        exit('The upload exceeds the server request-size limit. Upload fewer or smaller files, or increase PHP post_max_size.');
    }
    if (!csrf_check($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        exit('Session expired. Reload the page and try again.');
    }

    $action = (string)($_POST['action'] ?? '');
    if (in_array($action, ['create-folder', 'upload', 'upload-many', 'create-link'], true) && !$isContentAdmin) {
        http_response_code(403);
        exit('Only Administrators can create folders, upload files, or add links.');
    }
    $returnFolder = (string)($_POST['return_folder'] ?? '');
    $destinationId = $returnFolder === '' ? null : $returnFolder;
    try {
        if ($action === 'create-folder') {
            ContentLibrary::createFolder((string)($_POST['name'] ?? ''), $destinationId, $user['username']);
            $_SESSION['content_flash'] = ['success', 'Folder created.'];
        } elseif ($action === 'upload') {
            ContentLibrary::upload($_FILES['upload'] ?? [], $destinationId, $user['username']);
            $_SESSION['content_flash'] = ['success', 'File uploaded.'];
        } elseif ($action === 'upload-many') {
            $result = ContentLibrary::uploadMany(
                $_FILES['upload'] ?? [],
                is_array($_POST['relative_paths'] ?? null) ? $_POST['relative_paths'] : [],
                $destinationId,
                $user['username']
            );
            $uploadedCount = count($result['uploaded']);
            $errors = $result['errors'];
            if ($errors === []) {
                $_SESSION['content_flash'] = ['success', $uploadedCount . ' file' . ($uploadedCount === 1 ? '' : 's') . ' uploaded.'];
            } else {
                $message = $uploadedCount . ' uploaded; ' . count($errors) . ' failed.';
                foreach (array_slice($errors, 0, 3) as $error) $message .= ' ' . $error['name'] . ': ' . $error['message'];
                if (count($errors) > 3) $message .= ' And ' . (count($errors) - 3) . ' more failed.';
                $_SESSION['content_flash'] = ['error', $message];
            }
        } elseif ($action === 'create-link') {
            ContentLibrary::createLink(
                (string)($_POST['name'] ?? ''),
                (string)($_POST['url'] ?? ''),
                $destinationId,
                $user['username']
            );
            $_SESSION['content_flash'] = ['success', 'Link added.'];
        } elseif ($action === 'replace-file') {
            ContentLibrary::replaceFile((string)($_POST['item_id'] ?? ''), $_FILES['upload'] ?? [], $user['username']);
            $_SESSION['content_flash'] = ['success', 'File contents uploaded.'];
        } elseif ($action === 'update') {
            ContentLibrary::update((string)($_POST['item_id'] ?? ''), (string)($_POST['name'] ?? ''), (string)($_POST['status'] ?? ''));
            $_SESSION['content_flash'] = ['success', 'Item updated.'];
        } elseif ($action === 'delete') {
            ContentLibrary::delete((string)($_POST['item_id'] ?? ''));
            $_SESSION['content_flash'] = ['success', 'Item deleted.'];
        } else {
            throw new InvalidArgumentException('Choose a valid content action.');
        }
    } catch (InvalidArgumentException $error) {
        $_SESSION['content_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Bank Content action failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['content_flash'] = ['error', 'The content action failed. Please try again.'];
    }
    $url = 'content.php?folder=' . rawurlencode($returnFolder);
    if ($view !== '') $url .= '&view=' . rawurlencode($view);
    redirect($url);
}

$activeFolders = [];
$ancestor = $currentFolder;
while ($ancestor) {
    $activeFolders[$ancestor['id']] = true;
    $ancestor = $ancestor['parent_id'] ? ContentLibrary::find($ancestor['parent_id'], $approvedOnly) : null;
}
$flatFolders = [];
$flattenFolders = function (array $nodes, int $depth = 0) use (&$flattenFolders, &$flatFolders): void {
    foreach ($nodes as $node) {
        $flatFolders[] = ['id' => $node['id'], 'name' => str_repeat('— ', $depth) . $node['name']];
        $flattenFolders($node['children'], $depth + 1);
    }
};
$flattenFolders($folders);
$renderFolderTree = function (array $nodes) use (&$renderFolderTree, $activeFolders, $folderId): void {
    foreach ($nodes as $node) {
        $hasChildren = $node['children'] !== [];
        $isCurrent = $folderId === $node['id'];
        ?>
          <?php if ($hasChildren): ?>
            <details class="group" <?= isset($activeFolders[$node['id']]) ? 'open' : '' ?>>
              <summary class="flex cursor-pointer list-none items-center gap-1 rounded-md py-1.5 pr-1">
                <svg aria-hidden="true" viewBox="0 0 20 20" class="content-menu-chevron h-3.5 w-3.5 shrink-0 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m7 5 5 5-5 5"/></svg>
                <a href="content.php?folder=<?= rawurlencode($node['id']) ?>" class="<?= $isCurrent ? 'bg-purple-50 font-medium text-brand-purple' : 'text-slate-700 hover:bg-slate-50' ?> flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5">
                  <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 5.5h5l1.5 2H17v8H3z"/><path d="M3 5V4h5l1.5 1.5"/></svg>
                  <span class="truncate"><?= e($node['name']) ?></span>
                </a>
              </summary>
              <div class="ml-5 border-l border-slate-200 pl-2">
                <?php $renderFolderTree($node['children']); ?>
              </div>
            </details>
          <?php else: ?>
            <div class="flex items-center gap-1 py-1.5">
              <span class="w-3.5 shrink-0" aria-hidden="true"></span>
              <a href="content.php?folder=<?= rawurlencode($node['id']) ?>" class="<?= $isCurrent ? 'bg-purple-50 font-medium text-brand-purple' : 'text-slate-700 hover:bg-slate-50' ?> flex min-w-0 flex-1 items-center gap-2 rounded-md px-2 py-1.5">
                <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 5.5h5l1.5 2H17v8H3z"/><path d="M3 5V4h5l1.5 1.5"/></svg>
                <span class="truncate"><?= e($node['name']) ?></span>
              </a>
            </div>
          <?php endif; ?>
        <?php
    }
};
$formatSize = static function (?int $bytes): string {
    if ($bytes === null || $bytes < 1) return '—';
    if ($bytes < 1024) return $bytes . ' B';
    $size = $bytes / 1024;
    foreach (['KB', 'MB', 'GB'] as $unit) {
        if ($size < 1024 || $unit === 'GB') return number_format($size, $size < 10 ? 1 : 0) . ' ' . $unit;
        $size /= 1024;
    }
    return number_format($size, 0) . ' GB';
};
$viewLabel = match ($view) {
    'recent' => 'Recent',
    'starred' => 'Starred',
    'shared' => 'Shared with me',
    default => $currentFolder['name'] ?? 'All contents',
};
$breadcrumbFolder = $selectedFile
    ? (($selected['parent_id'] ?? null) ? ContentLibrary::find($selected['parent_id'], $approvedOnly) : null)
    : $currentFolder;
$breadcrumbFolders = [];
while ($breadcrumbFolder) {
    $breadcrumbFolders[] = $breadcrumbFolder;
    $breadcrumbFolder = $breadcrumbFolder['parent_id'] ? ContentLibrary::find($breadcrumbFolder['parent_id'], $approvedOnly) : null;
}
$breadcrumbFolders = array_reverse($breadcrumbFolders);
$returnFolderId = $selectedFile ? $selected['parent_id'] : $folderId;
$returnUrl = 'content.php?folder=' . rawurlencode((string)($returnFolderId ?? ''));
component('layout-start', ['title' => 'Bank Content', 'portal' => true]); ?>

<style>
.content-menu-chevron { transition: transform 150ms ease; }
details[open] > summary .content-menu-chevron { transform: rotate(90deg); }
[data-library-sidebar][data-hidden="true"] { display: none; }
@media (max-width: 767px) {
  [data-library-sidebar] { position: fixed; top: 64px; bottom: 0; left: 0; z-index: 40; width: min(85vw, 280px); overflow-y: auto; transform: translateX(-105%); transition: transform 180ms ease; box-shadow: 0 12px 28px rgb(15 23 42 / 0.16); }
  [data-library-sidebar][data-open="true"] { transform: translateX(0); }
}
</style>
<header class="relative z-50 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 md:px-8">
  <button id="library-sidebar-toggle" type="button" aria-label="Hide sidebar" aria-controls="library-sidebar" aria-expanded="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-purple-300" title="Hide sidebar">
    <svg aria-hidden="true" viewBox="0 0 20 20" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2.5" y="3" width="15" height="14" rx="1.5"/><path d="M7 3v14m4-9 2 2-2 2"/></svg>
  </button>
  <!-- <a href="content.php" class="text-[28px] font-semibold leading-none tracking-tight text-brand-purple">eskayvie</a> -->
  <img alt="Eskayvie" class="h-8 w-32 object-contain object-left sm:h-9 sm:w-[154px]" src="assets/images/eskayvie-logo.png">
  <span class="hidden h-6 border-l border-slate-200 sm:block"></span>
  <span class="hidden text-sm font-medium text-slate-600 sm:block">V-System</span>
</header>
<div class="flex min-h-[calc(100vh-4rem)]">
  <aside id="library-sidebar" data-library-sidebar data-hidden="false" data-open="false" class="w-[232px] shrink-0 border-r border-slate-200 bg-white py-3">
    <nav aria-label="Main menu" class="space-y-1 px-3 text-sm">
      <details class="group">
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-3 py-2.5 text-slate-700 hover:bg-slate-50">
          <span class="flex items-center gap-2.5"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 2.5 16 5v4.5c0 3.5-2.5 6.2-6 8-3.5-1.8-6-4.5-6-8V5z"/><path d="M7.5 9.5 9.2 11l3.3-3.5"/></svg>Administrator</span>
          <svg aria-hidden="true" viewBox="0 0 20 20" class="content-menu-chevron h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m7 5 5 5-5 5"/></svg>
        </summary>
        <div class="ml-3 mt-1 space-y-0.5 border-l border-slate-200 pl-2">
          <?php if ($user): ?>
            <span class="block px-3 py-2 text-xs text-slate-500">Signed in as <?= e($user['username']) ?></span>
            <?php if ($isContentAdmin): ?><a href="content.php?folder=#content-manager" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Manage Bank Content</a><?php endif; ?>
            <form method="post" action="logout.php" class="px-3 py-1"><input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><button type="submit" class="text-slate-600 hover:text-red-700">Sign out</button></form>
          <?php else: ?>
            <a href="login.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Sign in</a>
            <a href="signup.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Create account</a>
          <?php endif; ?>
        </div>
      </details>
      <details class="group">
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md px-3 py-2.5 text-slate-700 hover:bg-slate-50">
          <span class="flex items-center gap-2.5"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 3.5h7l5 5v8A1.5 1.5 0 0 1 14.5 18h-10A1.5 1.5 0 0 1 3 16.5v-11A2 2 0 0 1 4 3.5Z"/><path d="M11 3.5v5h5M6 12h7m-7 3h7"/></svg>Playbooks</span>
          <svg aria-hidden="true" viewBox="0 0 20 20" class="content-menu-chevron h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m7 5 5 5-5 5"/></svg>
        </summary>
        <div class="ml-3 mt-1 space-y-0.5 border-l border-slate-200 pl-2">
          <a href="index.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Follow Up Checklist</a>
          <a href="monthly-target.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Monthly Target</a>
          <a href="activity-record-plan.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Activity Record &amp; Plan</a>
          <a href="customer-list.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Customer List</a>
          <a href="prospect-list.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Prospect List</a>
          <a href="daily-tasks.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">7 Daily Tasks</a>
          <a href="side-volume-target.php" class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-50">Monthly Side Volume Target</a>
        </div>
      </details>
      <details class="group" open>
        <summary class="flex cursor-pointer list-none items-center justify-between rounded-md bg-purple-50 px-3 py-2.5 font-medium text-brand-purple">
          <span class="flex items-center gap-2.5"><svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>Bank Content</span>
          <svg aria-hidden="true" viewBox="0 0 20 20" class="content-menu-chevron h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m7 5 5 5-5 5"/></svg>
        </summary>
        <div class="ml-3 mt-1 space-y-0.5 border-l border-slate-200 pl-2">
          <a href="content.php?folder=" class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-700 hover:bg-slate-50"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 5.5h5l1.5 2H17v8H3z"/><path d="M3 5V4h5l1.5 1.5"/></svg>Contents</a>
          <!-- <div class="flex items-center gap-2 px-3 pb-1 pt-2 text-slate-700"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-3 w-3 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m6 8 4 4 4-4"/><path d="M4 3h8l4 4v5"/></svg><span>Folders</span></div> -->
          <?php $renderFolderTree($folders); ?>
          <div class="my-2 border-t border-slate-100"></div>
          <a href="content.php?view=recent" class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-700 hover:bg-slate-50"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2"/></svg>Recent</a>
          <a href="content.php?view=starred" class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-700 hover:bg-slate-50"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m10 2 2.4 5 5.5.8-4 3.9.9 5.5-4.8-2.6-4.8 2.6.9-5.5-4-3.9 5.5-.8z"/></svg>Starred</a>
          <a href="content.php?view=shared" class="flex items-center gap-2 rounded-md px-3 py-2 text-slate-700 hover:bg-slate-50"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="6" r="3"/><path d="M2 17v-1a5 5 0 0 1 10 0v1zm11-8a3 3 0 1 0-1-5.8M15 12a4 4 0 0 1 3 4v1h-3"/></svg>Shared with me</a>
        </div>
      </details>
    </nav>
  </aside>
  <button id="library-sidebar-backdrop" type="button" aria-label="Close sidebar" class="fixed inset-0 z-30 hidden bg-black/30 md:hidden"></button>

  <section class="min-w-0 flex-1">
    <div class="p-4 md:px-9 md:pt-5">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
        <div class="flex items-center gap-2 text-sm font-medium text-slate-700"><span>Bank Content</span><span class="text-xs font-normal text-slate-500">Public library</span></div>
        <form method="get" class="flex flex-wrap items-center gap-2">
          <?php if ($folderId !== null): ?><input type="hidden" name="folder" value="<?= e($folderId) ?>"><?php endif; ?>
          <?php if ($view !== ''): ?><input type="hidden" name="view" value="<?= e($view) ?>"><?php endif; ?>
          <label class="sr-only" for="file-search">Search files and folders</label>
          <input id="file-search" name="q" value="<?= e($query) ?>" placeholder="Search files" class="w-36 rounded-md border border-slate-200 px-3 py-2 text-xs outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100">
          <label for="file-sort" class="text-xs text-slate-500">Sort by</label>
          <select id="file-sort" name="sort" onchange="this.form.submit()" class="rounded-md border-0 bg-white py-2 pl-1 pr-7 text-xs text-slate-700 focus:ring-2 focus:ring-purple-200">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
            <option value="modified" <?= $sort === 'modified' ? 'selected' : '' ?>>Last modified</option>
            <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>File size</option>
          </select>
          <button type="submit" aria-label="Search files" class="rounded-md p-2 text-slate-500 hover:bg-slate-100"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="8.5" cy="8.5" r="5.5"/><path d="m13 13 4 4"/></svg></button>
        </form>
      </div>

      <form method="get" class="mb-4 md:hidden">
        <label for="mobile-folder" class="mb-1 block text-xs font-medium text-slate-500">Folder</label>
        <select id="mobile-folder" name="folder" onchange="this.form.submit()" class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
          <option value="" <?= $folderId === null ? 'selected' : '' ?>>All contents</option>
          <?php foreach ($flatFolders as $option): ?><option value="<?= e($option['id']) ?>" <?= $folderId === $option['id'] ? 'selected' : '' ?>><?= e($option['name']) ?></option><?php endforeach; ?>
        </select>
      </form>

      <div class="mb-4 flex items-center gap-2 text-xs text-slate-600">
        <a href="content.php?folder=" class="hover:text-brand-purple">Contents</a>
        <?php foreach ($breadcrumbFolders as $index => $breadcrumb): ?>
          <span aria-hidden="true">›</span>
          <?php if (!$selectedFile && $index === array_key_last($breadcrumbFolders)): ?>
            <span class="font-medium text-slate-700"><?= e($breadcrumb['name']) ?></span>
          <?php else: ?>
            <a href="content.php?folder=<?= rawurlencode($breadcrumb['id']) ?>" class="hover:text-brand-purple"><?= e($breadcrumb['name']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($selectedFile): ?><span aria-hidden="true">›</span><span class="font-medium text-slate-700"><?= e($selected['name']) ?></span>
        <?php elseif ($view !== ''): ?><span aria-hidden="true">›</span><span class="font-medium text-slate-700"><?= e($viewLabel) ?></span><?php endif; ?>
      </div>

      <?php if ($flash): ?>
        <div role="status" class="mb-4 rounded-md border px-4 py-3 text-sm <?= $flash[0] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800' ?>"><?= e($flash[1]) ?></div>
      <?php endif; ?>

      <?php if ($selected): ?>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div><a href="<?= e($returnUrl) ?>" class="text-xs text-brand-purple hover:underline">← Back to <?= e($viewLabel) ?></a><h2 class="mt-2 text-lg font-semibold text-slate-900"><?= e($selected['name']) ?></h2><p class="mt-1 text-xs text-slate-500"><?= e($selected['mime_type'] ?? 'Document') ?> · <?= e($formatSize((int)$selected['size'])) ?></p></div>
            <a href="content-file.php?id=<?= rawurlencode($selected['id']) ?>&amp;download=1" class="rounded-md bg-brand-purple px-4 py-2 text-sm font-medium text-white hover:bg-brand-deep">Download</a>
          </div>
          <div class="bg-slate-50 p-3"><iframe title="Preview of <?= e($selected['name']) ?>" src="content-file.php?id=<?= rawurlencode($selected['id']) ?>" class="h-[70vh] min-h-96 w-full rounded-md border border-slate-200 bg-white"></iframe></div>
        </div>
      <?php else: ?>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
          <div><h1 class="text-sm font-semibold text-slate-900"><?= e($viewLabel) ?></h1><span class="text-xs text-slate-500"><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></span></div>
          <?php if ($isContentAdmin): ?>
            <details class="relative">
              <summary class="cursor-pointer rounded-md bg-brand-purple px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-deep">+ New folder</summary>
              <form method="post" class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-lg">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="create-folder"><input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
                <label for="new-folder-name" class="mb-1 block text-xs font-medium">Folder name</label><input id="new-folder-name" name="name" required maxlength="180" class="mb-3 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <button class="rounded-md bg-brand-purple px-3 py-2 text-xs font-medium text-white">Create folder</button>
              </form>
            </details>
          <?php endif; ?>
        </div>
        <?php if ($isContentAdmin): ?>
          <div id="content-manager" class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
            <div id="content-drop-zone" class="flex min-w-0 flex-1 flex-wrap items-center gap-3 rounded-md border border-dashed border-transparent px-2 py-1 transition-colors">
            <form id="content-upload-form" method="post" enctype="multipart/form-data" class="flex min-w-0 flex-wrap items-center gap-3">
              <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
              <input type="hidden" name="action" value="upload-many">
              <input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
              <input id="content-upload" name="upload[]" type="file" accept=".csv,.doc,.docx,.gif,.jpeg,.jpg,.pdf,.png,.ppt,.pptx,.txt,.webp,.xls,.xlsx" multiple class="sr-only">
              <input id="content-folder-upload" type="file" webkitdirectory directory multiple class="sr-only">
              <label for="content-upload" class="flex cursor-pointer items-center gap-2 rounded-md px-1 py-2 text-sm text-slate-700 hover:text-brand-purple">
                <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13V3m0 0L6.5 6.5M10 3l3.5 3.5M4 12v4a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-4"/></svg>
                Choose files
              </label>
              <label for="content-folder-upload" class="cursor-pointer rounded-md px-1 py-2 text-xs text-slate-500 hover:text-brand-purple">Choose folder</label>
              <span class="hidden h-6 border-l border-slate-200 sm:block"></span>
              <span id="content-upload-selection" class="text-xs text-slate-500">or drag files here</span>
              <button id="content-upload-submit" type="submit" disabled class="rounded-md bg-brand-purple px-3 py-2 text-xs font-medium text-white hover:bg-brand-deep disabled:cursor-not-allowed disabled:opacity-40">Upload selected</button>
              <div id="content-relative-paths"></div>
            </form>
            <details class="relative">
              <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md border-l border-slate-200 px-3 py-2 text-sm text-slate-700 hover:text-brand-purple">
                <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 10h6M10 7v6"/><path d="M7.5 3.5h5l4 4v8A1.5 1.5 0 0 1 15 17h-10A1.5 1.5 0 0 1 3.5 15.5v-10A2 2 0 0 1 5.5 3.5z"/></svg>
                Add link
              </summary>
              <form method="post" class="absolute right-0 z-20 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-4 shadow-lg">
                <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="create-link"><input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
                <label for="new-link-name" class="mb-1 block text-xs font-medium text-slate-700">Link name</label>
                <input id="new-link-name" name="name" required maxlength="180" class="mb-3 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <label for="new-link-url" class="mb-1 block text-xs font-medium text-slate-700">URL</label>
                <input id="new-link-url" name="url" type="url" required maxlength="2048" placeholder="https://" class="mb-3 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                <button class="rounded-md bg-brand-purple px-3 py-2 text-xs font-medium text-white">Add link</button>
              </form>
            </details>
            </div>
          </div>
        <?php endif; ?>
        <div class="relative rounded-lg border border-slate-200 bg-white">
          <div class="hidden rounded-t-lg <?= $canManage ? 'grid-cols-[minmax(13rem,1fr)_minmax(6rem,0.35fr)_minmax(7rem,0.5fr)_minmax(5rem,0.35fr)_minmax(5rem,0.35fr)_minmax(9rem,0.5fr)_minmax(5rem,0.5fr)]' : 'grid-cols-[minmax(13rem,1fr)_minmax(6rem,0.35fr)_minmax(7rem,0.5fr)_minmax(5rem,0.35fr)_minmax(5rem,0.5fr)]' ?> gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 text-[11px] font-medium text-slate-500 sm:grid">
            <span>Name</span><?php if ($canManage): ?><span>Owner</span><?php endif; ?><span>Last modified</span><span>File size</span><?php if ($canManage): ?><span>Status</span><span class="text-center">QR code</span><?php endif; ?><span class="text-right">Actions</span>
          </div>
          <?php if (!$items): ?>
            <div class="px-5 py-14 text-center"><p class="text-sm font-medium text-slate-700"><?= $query === '' ? 'No items in this folder yet' : 'No items match your search' ?></p><?php if ($canManage && $query === ''): ?><p class="mt-2 text-xs text-slate-500">Upload a file or create a folder to get started.</p><?php endif; ?></div>
          <?php else: foreach ($items as $item): ?>
            <?php
            $isFolder = $item['kind'] === 'folder';
            $isLink = $item['kind'] === 'link';
            $hasFileContents = $item['kind'] === 'file' && (bool)$item['stored_name'];
            $canOpen = $isFolder || $isLink || $hasFileContents;
            $canGenerateQr = $isFolder || ($item['kind'] === 'file' && $hasFileContents);
            $qrEndpoint = 'content.php?qr=' . rawurlencode($item['id']) . '&regen=' . rawurlencode((string)microtime(true));
            $extension = strtolower(pathinfo((string)$item['name'], PATHINFO_EXTENSION));
            [$iconColor, $iconType, $iconLabel] = match (true) {
                $isFolder => ['text-amber-700 bg-amber-50', 'folder', ''],
                $isLink => ['text-purple-700 bg-purple-50', 'link', ''],
                in_array($extension, ['pdf'], true) => ['text-red-700 bg-red-50', 'pdf', 'PDF'],
                in_array($extension, ['doc', 'docx'], true) => ['text-blue-700 bg-blue-50', 'word', 'W'],
                in_array($extension, ['xls', 'xlsx', 'csv'], true) => ['text-emerald-700 bg-emerald-50', 'spreadsheet', $extension === 'csv' ? 'CSV' : 'X'],
                in_array($extension, ['ppt', 'pptx'], true) => ['text-orange-700 bg-orange-50', 'presentation', 'P'],
                in_array($extension, ['gif', 'jpeg', 'jpg', 'png', 'webp'], true) => ['text-fuchsia-700 bg-fuchsia-50', 'image', ''],
                $extension === 'txt' => ['text-slate-700 bg-slate-100', 'text', 'TXT'],
                default => ['text-slate-600 bg-slate-100', 'file', strtoupper(substr($extension, 0, 3))],
            };
            $itemHref = $isFolder
                ? 'content.php?folder=' . rawurlencode($item['id'])
                : ($isLink ? (in_array(parse_url((string)$item['url'], PHP_URL_SCHEME), ['http', 'https'], true) ? (string)$item['url'] : '#') : ($hasFileContents ? 'content.php?folder=' . rawurlencode((string)($folderId ?? '')) . '&item=' . rawurlencode($item['id']) : '#'));
            ?>
            <div class="grid gap-2 border-b border-slate-100 px-4 py-3 last:border-0 sm:<?= $canManage ? 'grid-cols-[minmax(13rem,1fr)_minmax(6rem,0.35fr)_minmax(7rem,0.5fr)_minmax(5rem,0.35fr)_minmax(5rem,0.35fr)_minmax(9rem,0.5fr)_minmax(5rem,0.5fr)]' : 'grid-cols-[minmax(13rem,1fr)_minmax(6rem,0.35fr)_minmax(7rem,0.5fr)_minmax(5rem,0.35fr)_minmax(5rem,0.5fr)]' ?> sm:items-center sm:gap-3">
              <div class="flex min-w-0 items-center gap-2.5">
                <span class="<?= $iconColor ?> flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                  <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5">
                    <?php if ($iconType === 'folder'): ?>
                      <path d="M3 7h6l2 2h10v10H3z"/><path d="M3 7V5h6l2 2"/>
                    <?php elseif ($iconType === 'link'): ?>
                      <path d="M9.5 14.5 8 16a3.2 3.2 0 0 1-4.5-4.5l3-3A3.2 3.2 0 0 1 11 8"/><path d="m14.5 9.5 1.5-1.5A3.2 3.2 0 0 1 20.5 12l-3 3A3.2 3.2 0 0 1 13 16"/><path d="m8 16 8-8"/>
                    <?php else: ?>
                      <path d="M5 2.5h9l5 5v14H5z"/><path d="M14 2.5v5h5"/>
                      <?php if ($iconType === 'spreadsheet'): ?>
                        <path d="M8 11h8M8 14h8M8 17h8M11 10v8M14 10v8"/>
                      <?php elseif ($iconType === 'image'): ?>
                        <circle cx="15.5" cy="11" r="1.5"/><path d="m7 18 4.5-5 2.5 2 2-2 3 3"/>
                      <?php elseif ($iconType === 'presentation'): ?>
                        <rect x="8" y="10" width="8" height="5" rx=".5"/><path d="M12 15v3m-3 0h6"/>
                      <?php elseif ($iconType === 'text'): ?>
                        <path d="M8 11h8M8 14h8M8 17h6"/>
                      <?php else: ?>
                        <text x="12" y="16" text-anchor="middle" fill="currentColor" stroke="none" font-size="6" font-weight="700"><?= e($iconLabel ?: 'FILE') ?></text>
                      <?php endif; ?>
                    <?php endif; ?>
                  </svg>
                </span>
                <?php if ($canOpen): ?><a href="<?= e($itemHref) ?>" <?= $isLink ? 'target="_blank" rel="noopener noreferrer"' : '' ?> class="min-w-0 truncate text-xs font-medium text-slate-900 hover:text-brand-purple hover:underline"><?php else: ?><span title="The file contents have not been uploaded yet" class="min-w-0 truncate text-xs font-medium text-slate-900"><?php endif; ?>
                  <?= e($item['name']) ?><?php if ($item['starred']): ?> <span class="text-amber-500" aria-label="Starred">★</span><?php endif; ?>
                  <span class="mt-1 block text-[10px] font-normal text-slate-500 sm:hidden"><?= e($item['kind'] === 'folder' ? 'Folder' : ($item['mime_type'] ?? 'File')) ?><?= !$hasFileContents && !$isFolder && !$isLink ? ' · file contents not uploaded' : '' ?></span>
                <?php if ($canOpen): ?></a><?php else: ?></span><?php endif; ?>
              </div>
              <?php if ($canManage): ?>
                <span class="hidden truncate text-[10px] text-slate-600 sm:block"><?= e($item['owner']) ?></span>
              <?php endif; ?>
              <span class="hidden text-[10px] text-slate-600 sm:block"><?= e(date('M j, Y', strtotime($item['modified_at']))) ?></span>
              <span class="hidden text-[10px] text-slate-600 sm:block"><?= e($formatSize($item['size'] === null ? null : (int)$item['size'])) ?></span>
              <?php if ($canManage): ?>
                <?php $statusClass = match ($item['status']) { 'Approved' => 'bg-emerald-50 text-emerald-700', 'In review' => 'bg-amber-50 text-amber-700', default => 'bg-sky-50 text-sky-700' }; ?>
                <span class="hidden text-[10px] sm:block"><span class="<?= $statusClass ?> inline-flex rounded px-2 py-1 font-medium"><?= e($item['status']) ?></span></span>
                <div class="flex items-center justify-start gap-2 sm:justify-center">
                  <?php if ($canGenerateQr): ?>
                    <button type="button" data-qr-preview data-qr-image="qr-image-<?= e($item['id']) ?>" data-qr-title="<?= e($item['name']) ?>" data-qr-url="<?= e($qrTargetForItem($item)) ?>" aria-label="Preview QR code for <?= e($item['name']) ?>" class="rounded focus:outline-none focus:ring-2 focus:ring-purple-300">
                      <img id="qr-image-<?= e($item['id']) ?>" src="<?= e($qrEndpoint) ?>" alt="" width="80" height="80" class="h-16 w-16 rounded border border-slate-200 bg-white">
                    </button>
                    <button type="button" data-qr-regenerate data-qr-target="qr-image-<?= e($item['id']) ?>" class="rounded border border-slate-200 px-2 py-1 text-[10px] font-medium text-slate-600 hover:bg-slate-50">Regenerate</button>
                  <?php else: ?>
                    <span class="text-[10px] text-slate-400"><?= $isFolder ? '' : ($isLink ? '—' : 'Upload file first') ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <div class="flex items-center justify-end gap-1">
                <?php if (!$isFolder && !$isLink && $item['kind'] === 'file' && $item['stored_name']): ?>
                  <a aria-label="Download <?= e($item['name']) ?>" title="Download" href="content-file.php?id=<?= rawurlencode($item['id']) ?>&amp;download=1" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 hover:text-brand-purple"><svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 3v9m0 0 3-3m-3 3L7 9M4 13v3h12v-3"/></svg></a>
                <?php endif; ?>
                <?php if ($canManage): ?>
                  <details class="relative" data-manage-popup>
                    <summary class="cursor-pointer list-none rounded p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Manage <?= e($item['name']) ?>" title="Manage item">•••</summary>
                    <div class="absolute right-0 z-20 mt-1 w-64 rounded-lg border border-slate-200 bg-white p-3 text-left shadow-lg">
                      <form method="post" class="space-y-2">
                        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="item_id" value="<?= e($item['id']) ?>"><input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
                        <label class="block text-[11px] font-medium text-slate-600">Name<input name="name" required maxlength="180" value="<?= e($item['name']) ?>" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-xs"></label>
                        <label class="block text-[11px] font-medium text-slate-600">Status<select name="status" class="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-xs"><?php foreach (['Approved','In review','Ready'] as $status): ?><option <?= $item['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
                        <button class="rounded bg-brand-purple px-3 py-1.5 text-xs font-medium text-white">Save changes</button>
                      </form>
                      <?php if ($item['kind'] === 'file'): ?>
                        <form method="post" enctype="multipart/form-data" class="mt-3 border-t border-slate-100 pt-3">
                          <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="replace-file"><input type="hidden" name="item_id" value="<?= e($item['id']) ?>"><input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
                          <label class="mb-2 block text-[11px] font-medium text-slate-600"><?= $item['stored_name'] ? 'Replace file contents' : 'Attach file contents' ?><input name="upload" type="file" required class="mt-1 block w-full text-[11px]"></label>
                          <button class="rounded border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"><?= $item['stored_name'] ? 'Replace file' : 'Upload contents' ?></button>
                        </form>
                      <?php endif; ?>
                      <form method="post" class="mt-3 border-t border-slate-100 pt-3" onsubmit="return confirm('Delete this item? Deleting a folder also deletes its contents.');">
                        <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="<?= e($item['id']) ?>"><input type="hidden" name="return_folder" value="<?= e((string)($folderId ?? '')) ?>">
                        <button class="text-xs font-medium text-red-700 hover:underline">Delete</button>
                      </form>
                    </div>
                  </details>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($canManage): ?>
        <dialog data-qr-dialog class="w-[min(92vw,28rem)] rounded-xl border border-slate-200 bg-white p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/50">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div><h2 data-qr-dialog-title class="text-base font-semibold">QR code</h2><p class="mt-1 text-xs text-slate-500">Scan to open this Bank Content item.</p></div>
            <button type="button" data-qr-dialog-close aria-label="Close QR preview" class="rounded px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">×</button>
          </div>
          <div class="flex flex-col items-center gap-3 p-5">
            <img data-qr-dialog-image alt="" width="320" height="320" class="h-72 w-72 max-w-full rounded border border-slate-200 bg-white">
            <a data-qr-dialog-url class="max-w-full break-all text-center text-xs text-brand-purple underline" target="_blank" rel="noopener noreferrer"></a>
          </div>
        </dialog>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php component('layout-end'); ?>
