<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$flash = $_SESSION['prospect_flash'] ?? null;
unset($_SESSION['prospect_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_check($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Session expired. Reload the page and try again.');
        Prospects::create($u['username'], $_POST);
        $_SESSION['prospect_flash'] = ['success', 'Prospect added.'];
    } catch (InvalidArgumentException $error) {
        $_SESSION['prospect_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Prospect create failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['prospect_flash'] = ['error', 'Could not add the prospect. Please try again.'];
    }
    redirect('prospect-list.php');
}

$prospects = Prospects::forUser($u['username']);
component('layout-start', ['title' => 'Prospect List', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
?>
<details class="mb-6 rounded border border-gray-300 p-4">
  <summary class="cursor-pointer font-semibold text-brand-purple">Add Prospect</summary>
  <form method="post" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
    <?php
    component('input', ['label' => 'Nama', 'namex' => 'name']);
    component('input', ['label' => 'Handphone', 'namex' => 'phone_number', 'required' => false]);
    component('input', ['label' => 'Domisili', 'namex' => 'domicile', 'required' => false]);
    component('input', ['label' => 'Pekerjaan', 'namex' => 'occupation', 'required' => false]);
    component('input', ['label' => 'Tanggal', 'namex' => 'prospect_date', 'type' => 'date', 'required' => false]);
    component('select', ['label' => 'Response', 'namex' => 'response', 'options' => Prospects::RESPONSES, 'placeholder' => 'Pilih response', 'autocomplete' => true]);
    component('select', ['label' => 'Follow-up', 'namex' => 'follow_up', 'options' => Prospects::FOLLOW_UP_OPTIONS, 'selected' => '0', 'autocomplete' => true]);
    component('input', ['label' => 'Follow-up Date', 'namex' => 'follow_up_date', 'type' => 'date', 'required' => false]);
    ?>
    <label class="grid gap-1 text-sm font-medium text-gray-700 sm:col-span-2 xl:col-span-4">Alasan
      <?php component('textarea', ['namex' => 'reason', 'maxlength' => 2000, 'rows' => 2, 'ariaLabel' => 'Alasan', 'attrs' => 'data-auto-grow']); ?>
    </label>
    <div class="sm:col-span-2 xl:col-span-4">
      <?php component('button', ['label' => 'Add Prospect', 'type' => 'submit']); ?>
    </div>
  </form>
</details>

<div class="mb-3 text-sm text-gray-600">Prospect List · <?= count($prospects) ?> records</div>
<div class="overflow-x-auto rounded border border-gray-300">
  <table class="w-full min-w-[1400px] border-collapse text-sm">
    <thead class="bg-brand-purple text-white">
      <tr class="text-center">
        <th rowspan="2" class="w-12 border border-white/30 px-2 py-2">No</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Nama</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Handphone</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Domisili</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Pekerjaan</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Tanggal</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Response</th>
        <th rowspan="2" class="border border-white/30 px-2 py-2">Alasan</th>
        <th colspan="2" class="border border-white/30 px-2 py-1">Follow-up</th>
      </tr>
      <tr class="bg-brand-deep text-center text-xs">
        <th class="border border-white/30 px-2 py-1">Status</th>
        <th class="border border-white/30 px-2 py-1">Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$prospects): ?><tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">No prospects yet. Use “Add Prospect” above.</td></tr><?php endif; ?>
      <?php foreach ($prospects as $index => $prospect) component('prospect-row', ['i' => $index + 1, 'prospect' => $prospect]); ?>
    </tbody>
  </table>
</div>
<?php component('layout-end'); ?>