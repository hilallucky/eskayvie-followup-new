<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$fmt = ($_GET['format'] ?? 'csv') === 'xlsx' ? 'xlsx' : 'csv';
$yn = fn($v) => (int)$v === 1 ? 'Yes' : 'No';
$safe = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;   // formula-injection guard
$ranks = Members::ranks();
$head = ['No', 'Member Code', 'Name', 'Rank', 'Join Date', 'Consume Product', 'Product Name', 'Consume Date', 'Prospect List',
         'Welcome To Eskayvie', 'Product', 'Marketing Plan', 'HBO', 'Remarks', 'Status'];
$data = [];
foreach (Members::downline($u['member_code'], $u['username']) as $i => $r) {
    $data[] = array_map($safe, [$i + 1, $r['member_code'], $r['name'], $ranks[$r['rank_code']] ?? $r['rank_code'], $r['join_date'],
        $yn($r['consume_product']), $r['product_name'] ?? '', $r['consume_date'] ?? '', $yn($r['prospect_list']),
        $yn($r['welcome_to_eskayvie']), $yn($r['product']), $yn($r['marketing_plan']), $yn($r['hbo']), $r['remarks'] ?? '', $r['status']]);
}
$file = 'downline-followup-' . date('Ymd');
if ($fmt === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$file.csv\"");
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");            // BOM so Excel reads UTF-8
    fputcsv($o, $head, ',', '"', '');
    foreach ($data as $row) fputcsv($o, $row, ',', '"', '');                 // explicit escape arg (PHP 8.4)
    exit;
}
if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) { http_response_code(500); exit('XLSX needs PhpSpreadsheet: run "composer install".'); }
$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sh = $ss->getActiveSheet(); $sh->setTitle('Follow Up');
$sh->fromArray($head, null, 'A1'); $sh->fromArray($data, null, 'A2');
$last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($head));
$sh->getStyle("A1:{$last}1")->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '611DB5']]]);
foreach ($head as $c => $h) {   // character-count widths (autosize is unreliable)
    $w = mb_strlen($h);
    foreach ($data as $row) $w = max($w, mb_strlen((string)$row[$c]));
    $sh->getColumnDimensionByColumn($c + 1)->setWidth(min(50, $w + 2));
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$file.xlsx\"");
(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
