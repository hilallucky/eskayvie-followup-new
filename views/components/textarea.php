<?php
$namex = $namex ?? '';
$value = $value ?? '';
$maxlength = $maxlength ?? 2000;
$rows = $rows ?? 2;
$ariaLabel = $ariaLabel ?? '';
$attrs = $attrs ?? '';
?>
<textarea name="<?= e($namex) ?>" maxlength="<?= (int)$maxlength ?>" rows="<?= (int)$rows ?>" aria-label="<?= e($ariaLabel) ?>" <?= $attrs ?>
          class="w-full resize-none overflow-hidden rounded border border-gray-400 px-2 py-1 text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40"><?= e($value) ?></textarea>