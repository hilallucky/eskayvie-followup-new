<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$now = new DateTimeImmutable();
$currentMonth = (int)$now->format('n');
$currentYear = (int)$now->format('Y');
$month = (int)($_GET['month'] ?? $currentMonth);
$year = (int)($_GET['year'] ?? $currentYear);
if ($month < 1 || $month > 12) $month = $currentMonth;
if ($year < 1900 || $year > 9999) $year = $currentYear;
$months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$flash = $_SESSION['monthly_target_flash'] ?? null;
unset($_SESSION['monthly_target_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = (int)($_POST['month'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    try {
        if (!csrf_check($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Session expired. Reload the page and try again.');
        MonthlyTargets::save($u['username'], $month, $year, is_array($_POST['entries'] ?? null) ? $_POST['entries'] : []);
        $_SESSION['monthly_target_flash'] = ['success', 'Monthly targets saved.'];
    } catch (InvalidArgumentException $error) {
        $_SESSION['monthly_target_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Monthly target save failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['monthly_target_flash'] = ['error', 'Could not save monthly targets. Please try again.'];
    }
    redirect('monthly-target.php?month=' . $month . '&year=' . $year);
}

$dayCount = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
$today = new DateTimeImmutable('today');
$entries = MonthlyTargets::forMonth($u['username'], $month, $year);
$ranks = Members::ranks();
$statuses = MonthlyTargets::statuses();
$formatNumber = static fn(mixed $value): string => $value === null || $value === '' ? '' : number_format((int)$value, 0, '.', ',');
component('layout-start', ['title' => 'Monthly Target', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
?>
<style>
@media (max-width: 767px) {
  .monthly-target-table thead { display: none; }
  .monthly-target-table tbody { display: grid; gap: 0.75rem; }
  .monthly-target-table tbody tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.75rem;
  }
  .monthly-target-table tbody th {
    grid-column: 1 / -1;
    border-bottom: 1px solid #d1d5db;
    padding: 0 0 0.5rem;
    text-align: left;
  }
  .monthly-target-table tbody td { min-width: 0; padding: 0; }
  .monthly-target-table tbody td.mobile-full { grid-column: 1 / -1; }
}
</style>
<form method="get" class="mb-5 flex flex-wrap items-end gap-3" data-month-selector>
  <label class="grid gap-1 text-sm font-medium">Bulan
    <select name="month" class="rounded border border-gray-300 bg-white px-3 py-2" data-month-field>
      <?php foreach ($months as $number => $label): ?>
      <option value="<?= $number ?>" <?= $month === $number ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="grid gap-1 text-sm font-medium">Tahun
    <input type="number" name="year" min="1900" max="9999" value="<?= $year ?>" class="w-28 rounded border border-gray-300 px-3 py-2 text-right" data-month-field>
  </label>
  <button type="submit" class="rounded bg-brand-purple px-4 py-2 text-sm font-semibold text-white hover:bg-brand-deep">Tampilkan</button>
</form>

<form method="post">
  <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
  <input type="hidden" name="month" value="<?= $month ?>">
  <input type="hidden" name="year" value="<?= $year ?>">
  <datalist id="rank-options">
    <?php foreach ($ranks as $code => $name): ?><option value="<?= e($name) ?>" data-value="<?= e($code) ?>"></option><?php endforeach; ?>
  </datalist>
  <datalist id="status-options">
    <?php foreach ($statuses as $id => $name): ?><option value="<?= e($name) ?>" data-value="<?= (int)$id ?>"></option><?php endforeach; ?>
  </datalist>
  <div class="overflow-x-auto rounded border border-gray-400">
    <table class="monthly-target-table w-full min-w-0 border-collapse text-sm md:min-w-[1050px]">
      <thead class="bg-brand-purple text-white">
        <tr class="text-center">
          <th rowspan="2" class="w-16 border border-white/30 px-2">Tanggal</th>
          <th colspan="4" class="border border-white/30 py-1">Target</th>
          <th rowspan="2" class="w-64 border border-white/30 px-2">Usaha yang dilakukan</th>
          <th rowspan="2" class="w-44 border border-white/30 px-2">Status</th>
        </tr>
        <tr class="text-center">
          <th class="w-40 border border-white/30 px-2 py-1">Pendapatan</th>
          <th class="w-32 border border-white/30 px-2">Rekrut</th>
          <th class="w-32 border border-white/30 px-2">RO</th>
          <th class="w-48 border border-white/30 px-2">Peringkat</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($day = 1; $day <= $dayCount; $day++):
          $entry = $entries[$day] ?? [];
          $dayDate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
          $rowColor = $dayDate < $today ? 'bg-red-100' : ($day % 2 ? 'bg-white' : 'bg-gray-50');
        ?>
        <tr class="border-b border-gray-200 align-top hover:bg-purple-50/40 <?= $rowColor === 'bg-red-100' ? $rowColor : '' ?>">
          <th scope="row" class="px-2 py-2 text-right font-normal"><?= $day ?></th>
          <?php foreach ([['target_income', 'Target Pendapatan'], ['target_recruit', 'Target Rekrut'], ['target_ro', 'Target RO']] as [$field, $label]): $inputId = $field . '-' . $day; ?>
          <td class="min-w-[120px] px-2 py-2">
            <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden"><?= e($label) ?></span>
            <div class="flex w-full">
              <input id="<?= e($inputId) ?>" aria-label="<?= e($label) ?> tanggal <?= $day ?>" type="text" inputmode="numeric" data-number-format name="entries[<?= $day ?>][<?= e($field) ?>]" value="<?= e($formatNumber($entry[$field] ?? '')) ?>" class="min-w-0 w-full rounded-l border border-gray-400 px-2 py-1 text-right text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40">
              <span class="flex w-5 shrink-0 flex-col">
                <button type="button" aria-label="Increase <?= e($label) ?> tanggal <?= $day ?>" data-number-step="1" data-number-target="<?= e($inputId) ?>" class="flex flex-1 items-center justify-center rounded-tr border border-gray-300 text-[8px] leading-none text-gray-400 hover:bg-gray-50 hover:text-gray-600 focus:outline-none focus:ring-1 focus:ring-brand-green/40">&#9650;</button>
                <button type="button" aria-label="Decrease <?= e($label) ?> tanggal <?= $day ?>" data-number-step="-1" data-number-target="<?= e($inputId) ?>" class="flex flex-1 items-center justify-center rounded-br border border-t-0 border-gray-300 text-[8px] leading-none text-gray-400 hover:bg-gray-50 hover:text-gray-600 focus:outline-none focus:ring-1 focus:ring-brand-green/40">&#9660;</button>
              </span>
            </div>
          </td>
          <?php endforeach; ?>
          <td class="px-2 py-2">
            <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden">Target Peringkat</span>
            <input aria-label="Target Peringkat tanggal <?= $day ?>" type="text" list="rank-options" role="combobox" aria-autocomplete="list" data-autocomplete-input data-autocomplete-target="rank-value-<?= $day ?>" value="<?= e($ranks[$entry['rank_code'] ?? ''] ?? '') ?>" placeholder="Ketik peringkat" class="w-full rounded border border-gray-400 bg-white px-1 py-1 text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40">
            <input type="hidden" id="rank-value-<?= $day ?>" name="entries[<?= $day ?>][rank_code]" data-autocomplete-value value="<?= e($entry['rank_code'] ?? '') ?>">
          </td>
          <td class="mobile-full min-w-[160px] px-2 py-2"><span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden">Usaha yang dilakukan</span><textarea aria-label="Usaha yang dilakukan tanggal <?= $day ?>" name="entries[<?= $day ?>][effort]" maxlength="1000" rows="1" data-auto-grow class="w-full resize-none overflow-hidden rounded border border-gray-400 px-2 py-1 text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40"><?= e($entry['effort'] ?? '') ?></textarea></td>
          <td class="mobile-full px-2 py-2">
            <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden">Status</span>
            <input aria-label="Status tanggal <?= $day ?>" type="text" list="status-options" role="combobox" aria-autocomplete="list" data-autocomplete-input data-autocomplete-target="status-value-<?= $day ?>" value="<?= e($statuses[$entry['status_id'] ?? ''] ?? '') ?>" placeholder="Ketik status" class="w-full rounded border border-gray-400 bg-white px-1 py-1 text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40">
            <input type="hidden" id="status-value-<?= $day ?>" name="entries[<?= $day ?>][status_id]" data-autocomplete-value value="<?= e($entry['status_id'] ?? '') ?>">
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-4 flex justify-end">
    <button type="submit" class="w-full rounded bg-brand-purple px-5 py-2 font-semibold text-white hover:bg-brand-deep sm:w-auto">Simpan Target</button>
  </div>
</form>
<?php component('layout-end'); ?>