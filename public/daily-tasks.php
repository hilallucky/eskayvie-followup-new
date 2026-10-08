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
$tasks = [
    'learn' => 'Belajar',
    'video_onboarding' => 'Video onboarding',
    'consume_product' => 'Konsumsi produk',
    'repeat_order' => 'Repeat Order',
    'attend_zoom' => 'Attend meeting zoom',
    'contact_upline' => 'Kontak upline',
    'sponsoring' => 'Sponsoring',
];
$dayCount = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
$today = new DateTimeImmutable('today');
$entries = DailyTasks::forMonth($u['username'], $month, $year);
component('layout-start', ['title' => '7 Daily Tasks', 'user' => $u, 'wide' => true]);
?>
<form method="get" class="mb-5 flex flex-wrap items-end gap-3 rounded border border-purple-200 bg-purple-50 px-4 py-3" data-month-selector>
  <label class="grid gap-1 text-sm font-medium text-brand-purple">Bulan
    <select name="month" class="rounded border border-gray-300 bg-white px-3 py-2" data-month-field>
      <?php foreach ($months as $number => $label): ?><option value="<?= $number ?>" <?= $month === $number ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label class="grid gap-1 text-sm font-medium text-brand-purple">Tahun
    <input type="number" name="year" min="1900" max="9999" value="<?= $year ?>" class="w-28 rounded border border-gray-300 bg-white px-3 py-2 text-right" data-month-field>
  </label>
  <button type="submit" class="rounded bg-brand-purple px-4 py-2 text-sm font-semibold text-white hover:bg-brand-deep">Tampilkan</button>
</form>

<style>
@media (max-width: 767px) {
  .daily-tasks-table thead { display: none; }
  .daily-tasks-table tbody { display: grid; gap: 0.75rem; }
  .daily-tasks-table tbody tr {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    padding: 0.75rem;
  }
  .daily-tasks-table tbody th { grid-column: 1 / -1; border-bottom: 1px solid #d1d5db; padding: 0 0 0.5rem; text-align: left; }
  .daily-tasks-table tbody td { min-width: 0; padding: 0; }
}
</style>
<div class="overflow-x-auto rounded border border-gray-300">
  <table class="daily-tasks-table w-full border-collapse text-sm md:min-w-[900px]">
    <thead class="bg-brand-purple text-white">
      <tr class="text-center">
        <th class="w-20 border border-white/30 px-2 py-2">Tanggal</th>
        <?php foreach (array_values($tasks) as $index => $label): ?>
        <th class="border border-white/30 px-2 py-2"><span class="block text-base"><?= $index + 1 ?></span><span class="text-xs font-medium"><?= e($label) ?></span></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php for ($day = 1; $day <= $dayCount; $day++):
        $dayDate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        $rowColor = $dayDate < $today ? 'bg-red-100' : '';
        component('daily-task-row', ['day' => $day, 'month' => $month, 'year' => $year, 'entry' => $entries[$day] ?? [], 'tasks' => $tasks, 'rowColor' => $rowColor]);
      endfor; ?>
    </tbody>
  </table>
</div>
<p class="mt-3 text-xs text-gray-500">Red rows indicate dates that have passed.</p>
<?php component('layout-end'); ?>