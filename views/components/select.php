<?php
$namex = $namex ?? null; 
$label = $label ?? null; 
$selected = $selected ?? ''; 
$placeholder = $placeholder ?? null;
$attrs = $attrs ?? ''; 
$compact = $compact ?? false;
$autocomplete = $autocomplete ?? false;
$required = $required ?? ($placeholder === null);
$ariaLabel = $ariaLabel ?? $label ?? $namex ?? '';
$labelClassName = $labelClassName ?? 'block';
$labelTextClassName = $labelTextClassName ?? 'mb-1 block text-sm font-medium text-gray-700';
$size = $compact ? 'px-1 py-1 text-xs' : 'px-3 py-2 text-sm';
$className = $className ?? "w-full rounded border border-gray-400 bg-white $size focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40";
?>
<label class="<?= e($labelClassName) ?>">
  <?php if ($label): ?><span class="<?= e($labelTextClassName) ?>"><?= e($label) ?></span><?php endif; ?>
  <?php if ($autocomplete): $autocompleteId = $autocompleteId ?? 'select-' . bin2hex(random_bytes(8)); $listId = $autocompleteId . '-options'; $valueId = $autocompleteId . '-value'; ?>
    <datalist id="<?= e($listId) ?>">
      <?php if ($placeholder !== null): ?><option value="<?= e($placeholder) ?>" data-value=""></option><?php endif; ?>
      <?php foreach ($options as $val => $text): ?><option value="<?= e($text) ?>" data-value="<?= e($val) ?>"></option><?php endforeach; ?>
    </datalist>
    <input id="<?= e($autocompleteId) ?>" type="text" list="<?= e($listId) ?>" role="combobox" aria-autocomplete="list" aria-label="<?= e($ariaLabel) ?>" data-autocomplete-input data-autocomplete-target="<?= e($valueId) ?>" data-autocomplete-allow-empty="<?= $required ? 'false' : 'true' ?>"
           value="<?= e($options[$selected] ?? '') ?>" placeholder="<?= e($placeholder ?? '') ?>" <?= $required ? 'required' : '' ?> class="<?= e($className) ?>">
    <input type="hidden" id="<?= e($valueId) ?>" name="<?= e($namex ?? '') ?>" value="<?= e($selected) ?>" data-autocomplete-visible="<?= e($autocompleteId) ?>" <?= $attrs ?>>
  <?php else: ?>
  <select name="<?= e($namex ?? '') ?>" <?= $attrs ?>
          class="<?= e($className) ?>">
    <?php if ($placeholder !== null): ?><option value="" <?= (string)$selected === '' ? 'selected' : '' ?>><?= e($placeholder) ?></option><?php endif; ?>
    <?php foreach ($options as $val => $text): ?>
      <option value="<?= e($val) ?>" <?= (string)$val === (string)$selected ? 'selected' : '' ?>><?= e($text) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
</label>
