<?php $on = !empty($on); $label = $label ?? $field; ?>
<button type="button" data-toggle="<?= e($field) ?>" data-value="<?= $on ? 1 : 0 ?>" aria-label="<?= e($label) ?>"
        class="h-7 w-7 rounded text-lg font-bold hover:bg-gray-100 <?= $on ? 'text-brand-forest' : 'text-gray-400' ?>"><?= $on ? '✓' : '✗' ?></button>
