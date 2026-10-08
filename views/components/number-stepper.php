<?php
$id = $id ?? '';
$namex = $namex ?? '';
$value = $value ?? '';
$step = $step ?? '1';
$min = $min ?? 0;
$max = $max ?? null;
$ariaLabel = $ariaLabel ?? $namex;
$attrs = $attrs ?? '';
?>
<div class="flex w-full">
  <input id="<?= e($id) ?>" type="number" min="<?= e($min) ?>" <?= $max !== null ? 'max="' . e($max) . '"' : '' ?> step="<?= e($step) ?>" name="<?= e($namex) ?>" value="<?= e($value) ?>" aria-label="<?= e($ariaLabel) ?>" <?= $attrs ?> class="number-stepper min-w-0 w-full rounded-l border border-gray-400 px-1 py-1 text-right text-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40">
  <span class="flex w-5 shrink-0 flex-col">
    <button type="button" aria-label="Increase <?= e($ariaLabel) ?>" data-number-step="<?= e($step) ?>" data-number-target="<?= e($id) ?>" class="flex flex-1 items-center justify-center rounded-tr border border-gray-300 text-[8px] leading-none text-gray-400 hover:bg-gray-50 hover:text-gray-600 focus:outline-none focus:ring-1 focus:ring-brand-green/40">&#9650;</button>
    <button type="button" aria-label="Decrease <?= e($ariaLabel) ?>" data-number-step="-<?= e($step) ?>" data-number-target="<?= e($id) ?>" class="flex flex-1 items-center justify-center rounded-br border border-t-0 border-gray-300 text-[8px] leading-none text-gray-400 hover:bg-gray-50 hover:text-gray-600 focus:outline-none focus:ring-1 focus:ring-brand-green/40">&#9660;</button>
  </span>
</div>