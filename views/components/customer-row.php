<?php /* props: $i, $customer, $products, $today */ ?>
<tr class="border-b border-gray-200 align-top hover:bg-purple-50/40 <?= Customers::birthdayColor($customer['birth_date'], $today) ?>" data-customer-row data-customer-id="<?= (int)$customer['id'] ?>">
  <td class="px-2 py-2 text-right"><?= (int)$i ?></td>
  <td class="min-w-[170px] px-2 py-2"><?php component('input', ['namex' => 'name', 'value' => $customer['name'], 'compact' => true, 'attrs' => 'data-customer-field="name"']); ?></td>
  <td class="min-w-[140px] px-2 py-2"><?php component('input', ['namex' => 'phone_number', 'value' => $customer['phone_number'] ?? '', 'required' => false, 'compact' => true, 'attrs' => 'data-customer-field="phone_number"']); ?></td>
  <td class="min-w-[130px] px-2 py-2"><?php component('input', ['namex' => 'birth_date', 'type' => 'date', 'value' => $customer['birth_date'] ?? '', 'required' => false, 'compact' => true, 'attrs' => 'data-customer-field="birth_date"']); ?></td>
  <td class="min-w-[180px] px-2 py-2">
    <div class="grid grid-cols-2 gap-1" data-customer-products>
      <?php foreach ($products as $code => $name): ?>
      <label class="flex min-w-0 items-center gap-1 text-xs">
        <input type="checkbox" value="<?= e($code) ?>" data-customer-field="product_codes" data-customer-product <?= in_array($code, $customer['product_codes'], true) ? 'checked' : '' ?> class="rounded border-gray-300 text-brand-purple focus:ring-brand-green">
        <span class="truncate" title="<?= e($name) ?>"><?= e($name) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </td>
  <td class="min-w-[130px] px-2 py-2"><?php component('input', ['namex' => 'order_date', 'type' => 'date', 'value' => $customer['order_date'] ?? '', 'required' => false, 'compact' => true, 'attrs' => 'data-customer-field="order_date"']); ?></td>
  <td class="min-w-[200px] px-2 py-2"><?php component('textarea', ['namex' => 'notes', 'value' => $customer['notes'] ?? '', 'maxlength' => 2000, 'rows' => 1, 'ariaLabel' => 'Catatan untuk ' . $customer['name'], 'attrs' => 'data-customer-field="notes" data-auto-grow']); ?></td>
</tr>