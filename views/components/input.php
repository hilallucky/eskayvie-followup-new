<?php
$type = $type ?? 'text'; 
$namex = $namex ?? null; 
$value = $value ?? ''; 
$placeholder = $placeholder ?? ''; 
$attrs = $attrs ?? '';
$required = $required ?? true;
$label = $label ?? null; 
$compact = $compact ?? false;
$labelClassName = $labelClassName ?? 'block';
$labelTextClassName = $labelTextClassName ?? 'mb-1 block text-sm font-medium text-gray-700';
$size = $compact ? 'px-2 py-1 text-xs' : 'px-3 py-2 text-sm';
$className = $className ?? "w-full rounded border border-gray-400 $size focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/40";
?>
<?php if ($type === 'hidden'): ?>
<input type="hidden" name="<?= e($namex ?? '') ?>" value="<?= e($value) ?>" <?= $attrs ?>>
<?php else: ?>
<label class="<?= e($labelClassName) ?>">
  <?php if ($label): ?><span class="<?= e($labelTextClassName) ?>"><?= e($label) ?></span><?php endif; ?>
  <input type="<?= e($type) ?>" name="<?= e($namex ?? '') ?>" value="<?= e($value) ?>" placeholder="<?= e($placeholder) ?>"
         <?= $required ? 'required' : '' ?> <?= $attrs ?>
         class="<?= e($className) ?>">
</label>
<?php endif; ?>
