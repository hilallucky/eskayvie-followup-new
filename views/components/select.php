<?php
$namex = $namex ?? null; 
$label = $label ?? null; 
$selected = $selected ?? ''; 
$attrs = $attrs ?? ''; 
$compact = $compact ?? false;
$size = $compact ? 'px-1 py-1 text-xs' : 'px-3 py-2 text-sm';
?>
<label class="block">
  <?php if ($label): ?><span class="mb-1 block text-sm font-medium text-gray-700"><?= e($label) ?></span><?php endif; ?>
  <select name="<?= e($namex ?? '') ?>" <?= $attrs ?>
          class="w-full rounded border border-gray-400 bg-white <?= $size ?> focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40">
    <?php foreach ($options as $val => $text): ?>
      <option value="<?= e($val) ?>" <?= (string)$val === (string)$selected ? 'selected' : '' ?>><?= e($text) ?></option>
    <?php endforeach; ?>
  </select>
</label>
