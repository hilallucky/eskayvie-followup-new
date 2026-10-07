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
$flash = $_SESSION['activity_plan_flash'] ?? null;
unset($_SESSION['activity_plan_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = (int)($_POST['month'] ?? 0);
    $year = (int)($_POST['year'] ?? 0);
    try {
        if (!csrf_check($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Session expired. Reload the page and try again.');
        ActivityPlans::save($u['username'], $month, $year, is_array($_POST['entries'] ?? null) ? $_POST['entries'] : []);
        $_SESSION['activity_plan_flash'] = ['success', 'Activity plan saved.'];
    } catch (InvalidArgumentException $error) {
        $_SESSION['activity_plan_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Activity plan save failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['activity_plan_flash'] = ['error', 'Could not save the activity plan. Please try again.'];
    }
    redirect('activity-record-plan.php?month=' . $month . '&year=' . $year);
}

$dayCount = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
$today = new DateTimeImmutable('today');
$entries = ActivityPlans::forMonth($u['username'], $month, $year);
$statuses = ['' => 'Pilih status'] + ActivityPlans::statuses();
component('layout-start', ['title' => 'Activity Record & Plan', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
?>
<style>
@media (max-width: 767px) {
  .activity-plan-table thead { display: none; }
  .activity-plan-table tbody { display: grid; gap: 0.75rem; }
  .activity-plan-table tbody tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.75rem;
  }
  .activity-plan-table tbody th { grid-column: 1 / -1; border-bottom: 1px solid #d1d5db; padding: 0 0 0.5rem; text-align: left; }
  .activity-plan-table tbody td { min-width: 0; padding: 0; }
  .activity-plan-table tbody td.mobile-full { grid-column: 1 / -1; }
}
</style>
<form method="get" class="mb-5 flex flex-wrap items-end gap-3" data-month-selector>
  <label class="grid gap-1 text-sm font-medium">Bulan
    <select name="month" class="rounded border border-gray-300 bg-white px-3 py-2" data-month-field>
      <?php foreach ($months as $number => $label): ?><option value="<?= $number ?>" <?= $month === $number ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
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
  <div class="overflow-x-auto rounded border border-gray-300">
    <table class="activity-plan-table w-full border-collapse text-sm md:min-w-[680px]">
      <thead class="bg-brand-purple text-white">
        <tr class="text-center">
          <th class="w-20 border border-white/30 px-2 py-2">Tanggal</th>
          <th class="border border-white/30 px-3 py-2">Rencana Aktivitas</th>
          <th class="w-48 border border-white/30 px-3 py-2">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($day = 1; $day <= $dayCount; $day++):
          $dayDate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
          $rowColor = $dayDate < $today ? 'bg-red-100' : ($day % 2 ? 'bg-white' : 'bg-gray-50');
          $entry = $entries[$day] ?? [];
        ?>
        <tr class="border-b border-gray-200 align-top hover:bg-purple-50/40 <?= $rowColor === 'bg-red-100' ? $rowColor : '' ?>">
          <th scope="row" class="px-3 py-2 text-right font-normal"><?= $day ?></th>
          <td class="mobile-full px-2 py-2">
            <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden">Rencana Aktivitas</span>
            <?php component('textarea', ['namex' => "entries[$day][activity]", 'value' => $entry['activity'] ?? '', 'maxlength' => 2000, 'rows' => 1, 'ariaLabel' => "Rencana Aktivitas tanggal $day", 'attrs' => 'data-auto-grow']); ?>
          </td>
          <td class="px-2 py-2">
            <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden">Status</span>
            <?php component('select', ['namex' => "entries[$day][status_id]", 'options' => $statuses, 'selected' => $entry['status_id'] ?? '', 'compact' => true]); ?>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-4 flex justify-end">
    <button type="submit" class="w-full rounded bg-brand-purple px-5 py-2 font-semibold text-white hover:bg-brand-deep sm:w-auto">Simpan Aktivitas</button>
  </div>
</form>
<?php component('layout-end'); ?>