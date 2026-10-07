<?php /* props: $i (row number), $r (row array) */ ?>
<tr class="border-b border-gray-200 align-top hover:bg-purple-50/40" data-code="<?= e($r['member_code']) ?>">
  <td class="px-2 py-2 text-right"><?= e($i) ?></td>
  <td class="min-w-[220px] space-y-1 px-3 py-2">
    <?php component('input', ['namex' => 'name', 'value' => $r['name'], 'compact' => true, 'attrs' => 'data-field="name"']); ?>
    <div class="text-xs text-gray-500">ID: <?= e($r['member_code']) ?></div>
    <div class="text-xs text-gray-500">Join Date: <?= e(date('d M Y', strtotime($r['join_date']))) ?></div>
    <?php component('select', ['namex' => 'rank_code', 'options' => Members::ranks(), 'selected' => $r['rank_code'], 'compact' => true, 'attrs' => 'data-field="rank_code"']); ?>
  </td>
  <td class="min-w-[190px] space-y-1 px-3 py-2">
    <div class="text-center"><?php component('toggle', ['field' => 'consume_product', 'on' => $r['consume_product']]); ?></div>
    <!-- <?php component('input', ['namex' => 'product_name', 'value' => $r['product_name'] ?? '', 'placeholder' => 'Product', 'required' => false, 'compact' => true, 'attrs' => 'data-field="product_name"']); ?> -->
    <?php component('select', ['namex' => 'product_name', 'options' => Products::products(), 'selected' => $r['product_name'] ?? '', 'placeholder' => 'Pilih Product', 'required' => false, 'compact' => true, 'attrs' => 'data-field="product_name"']); ?>
    <?php component('input', ['namex' => 'consume_date', 'type' => 'date', 'value' => $r['consume_date'] ?? '', 'required' => false, 'compact' => true, 'attrs' => 'data-field="consume_date"']); ?>
  </td>
  <?php foreach (['prospect_list', 'welcome_to_eskayvie', 'product', 'marketing_plan', 'hbo'] as $f): ?>
    <td class="px-2 py-2 text-center"><?php component('toggle', ['field' => $f, 'on' => $r[$f]]); ?></td>
  <?php endforeach; ?>
  <td class="min-w-[160px] px-2 py-2"><?php component('input', ['namex' => 'remarks', 'value' => $r['remarks'] ?? '', 'required' => false, 'compact' => true, 'attrs' => 'data-field="remarks"']); ?></td>
  <td class="px-2 py-2"><?php component('select', ['namex' => 'status', 'options' => Members::STATUSES, 'selected' => $r['status'], 'compact' => true, 'attrs' => 'data-field="status"']); ?></td>
</tr>
