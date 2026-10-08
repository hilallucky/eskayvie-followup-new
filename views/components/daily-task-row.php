<?php /* props: $day, $month, $year, $entry, $tasks, $rowColor */ ?>
<tr class="border-b border-gray-200 align-top hover:bg-purple-50/40 <?= $rowColor ?>" data-daily-task-row data-month="<?= (int)$month ?>" data-year="<?= (int)$year ?>" data-day="<?= (int)$day ?>">
  <th scope="row" class="px-2 py-2 text-center font-normal md:text-right"><?= sprintf('%02d', $day) ?></th>
  <?php foreach ($tasks as $field => $label): ?>
  <td class="px-2 py-2 text-center">
    <span class="mb-1 block text-xs font-semibold text-gray-600 md:hidden"><?= e($label) ?></span>
    <?php component('toggle', ['field' => $field, 'on' => $entry[$field] ?? 0, 'label' => $label . ' on ' . sprintf('%02d', $day)]); ?>
  </td>
  <?php endforeach; ?>
</tr>