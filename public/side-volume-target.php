<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$currentYear = (int)(new DateTimeImmutable())->format('Y');
$year = (int)($_GET['year'] ?? $currentYear);
if ($year < 1900 || $year > 9999) $year = $currentYear;
$flash = $_SESSION['side_volume_flash'] ?? null;
unset($_SESSION['side_volume_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = (int)($_POST['year'] ?? 0);
    try {
        if (!csrf_check($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Session expired. Reload the page and try again.');
        SideVolumeTargets::save($u['username'], $year, is_array($_POST['entries'] ?? null) ? $_POST['entries'] : []);
        $_SESSION['side_volume_flash'] = ['success', 'Side volume targets saved.'];
    } catch (InvalidArgumentException $error) {
        $_SESSION['side_volume_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Side volume save failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['side_volume_flash'] = ['error', 'Could not save side volume targets. Please try again.'];
    }
    redirect('side-volume-target.php?year=' . $year);
}

$products = Products::products();
$values = SideVolumeTargets::forYear($u['username'], $year);
$months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$monthShort = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
$today = new DateTimeImmutable('today');
$formatValue = static fn(mixed $value): string => $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float)$value, 2, '.', ','), '0'), '.');
$productTotals = [];
$annualTotals = ['target' => 0.0, 'actual' => 0.0];
foreach ($products as $code => $_name) {
  $productTotals[$code] = ['target' => 0.0, 'actual' => 0.0];
  foreach (range(1, 12) as $month) {
    foreach (['target', 'actual'] as $type) {
      $amount = (float)($values[$code][$month][$type] ?? 0);
      $productTotals[$code][$type] += $amount;
      $annualTotals[$type] += $amount;
    }
  }
}
component('layout-start', ['title' => 'Monthly Side Volume Target', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
?>
<style>
.number-stepper { appearance: textfield; -moz-appearance: textfield; }
.number-stepper::-webkit-inner-spin-button,
.number-stepper::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>
<form method="get" class="mb-5 flex flex-wrap items-end gap-3" data-year-auto-load>
  <label class="grid gap-1 text-sm font-medium">Tahun
    <?php component('number-stepper', ['id' => 'side-volume-year', 'namex' => 'year', 'value' => $year, 'step' => '1', 'min' => 1900, 'max' => 9999, 'ariaLabel' => 'Tahun', 'attrs' => 'data-auto-load-year required']); ?>
  </label>
  <button type="submit" class="rounded bg-brand-purple px-4 py-2 text-sm font-semibold text-white hover:bg-brand-deep">Tampilkan</button>
</form>

<form method="post">
  <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
  <input type="hidden" name="year" value="<?= $year ?>">
  <div class="overflow-x-auto rounded border border-gray-300">
    <table class="w-full min-w-[1600px] border-collapse text-sm">
      <thead class="bg-brand-purple text-white">
        <tr class="text-center">
          <th colspan="2" class="w-52 border border-white/30 px-3 py-2">Produk</th>
          <?php foreach ($months as $number => $monthName): ?>
          <th class="border border-white/30 px-2 py-2 <?= $number === (int)$today->format('n') && $year === (int)$today->format('Y') ? 'bg-brand-deep' : '' ?>"><span class="block text-xs text-white/80"><?= e($months[$number]) ?> <?= $year ?></span></th>
          <?php endforeach; ?>
          <th class="w-32 border border-white/30 bg-brand-deep px-2 py-2">Total <?= $year ?></th>
        </tr>
        <!-- <tr class="bg-brand-deep text-center text-xs">
          <?php foreach ($months as $number => $_monthName): ?><th class="border border-white/30 px-2 py-1 <?= (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $number)))->modify('last day of this month') < $today ? 'bg-red-700' : '' ?>">Target / Actual</th><?php endforeach; ?>
        </tr> -->
      </thead>
      <tbody>
        <!-- <tr class="border-b border-gray-300 bg-purple-100 font-semibold">
          <th rowspan="2" class="border-r border-gray-300 px-3 py-2 text-left">Total</th>
          <th class="border-r border-gray-300 px-2 py-2 text-left text-xs">Target</th>
          <?php foreach ($months as $_month => $_monthName): ?><td class="border-r border-gray-200 px-1 py-2"></td><?php endforeach; ?>
          <td class="border-r border-gray-200 bg-purple-200 px-2 py-2 text-right font-bold"><?= e($formatValue($annualTotals['target'])) ?></td>
        </tr>
        <tr class="border-b-2 border-purple-300 bg-purple-50 font-semibold">
          <th class="border-r border-gray-300 px-2 py-2 text-left text-xs">Actual</th>
          <?php foreach ($months as $_month => $_monthName): ?><td class="border-r border-gray-200 px-1 py-2"></td><?php endforeach; ?>
          <td class="border-r border-gray-200 bg-purple-100 px-2 py-2 text-right font-bold"><?= e($formatValue($annualTotals['actual'])) ?></td>
        </tr> -->
        <?php foreach ($products as $code => $name): ?>
        <tr class="border-b border-gray-200 align-top hover:bg-purple-50/40">
          <th rowspan="2" class="border-r border-gray-300 px-3 py-2 text-left font-medium"><span class="mr-2 text-gray-500"><?= e(array_search($code, array_keys($products), true) + 1) ?></span><?= e($name) ?></th>
          <th class="border-r border-gray-300 px-2 py-2 text-left text-xs font-normal">Target</th>
          <?php foreach ($months as $month => $_monthName): $past = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('last day of this month') < $today; $inputId = "side-volume-$code-$month-target"; ?>
          <td class="border-r border-gray-200 p-1 <?= $past ? 'bg-red-100' : '' ?>">
            <?php component('number-stepper', ['id' => $inputId, 'namex' => "entries[$code][$month][target]", 'value' => $values[$code][$month]['target'] ?? '', 'step' => '0.01', 'ariaLabel' => $name . ' target ' . $_monthName]); ?>
          </td>
          <?php endforeach; ?>
          <td class="border-r border-gray-200 bg-purple-50 px-2 py-2 text-right font-semibold"><?= e($formatValue($productTotals[$code]['target'])) ?></td>
        </tr>
        <tr class="border-b border-gray-200 align-top hover:bg-purple-50/40">
          <th class="border-r border-gray-300 px-2 py-2 text-left text-xs font-normal">Actual</th>
          <?php foreach ($months as $month => $_monthName): $past = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->modify('last day of this month') < $today; $inputId = "side-volume-$code-$month-actual"; ?>
          <td class="border-r border-gray-200 p-1 <?= $past ? 'bg-red-100' : '' ?>">
            <?php component('number-stepper', ['id' => $inputId, 'namex' => "entries[$code][$month][actual]", 'value' => $values[$code][$month]['actual'] ?? '', 'step' => '0.01', 'ariaLabel' => $name . ' actual ' . $_monthName]); ?>
          </td>
          <?php endforeach; ?>
          <td class="border-r border-gray-200 bg-purple-50 px-2 py-2 text-right font-semibold"><?= e($formatValue($productTotals[$code]['actual'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="15" class="px-4 py-8 text-center text-gray-500">No active products are available.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-4 flex justify-end">
    <button type="submit" class="w-full rounded bg-brand-purple px-5 py-2 font-semibold text-white hover:bg-brand-deep sm:w-auto">Simpan Target</button>
  </div>
</form>
<?php component('layout-end'); ?>