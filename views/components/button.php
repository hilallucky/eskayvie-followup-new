<?php
$variant = $variant ?? 'primary'; $type = $type ?? 'button'; $href = $href ?? null; $attrs = $attrs ?? ''; $className = $className ?? '';
$styles = [
  'primary'   => 'bg-brand-purple text-white hover:bg-brand-deep',
  'secondary' => 'bg-brand-green text-brand-deep hover:bg-brand-forest hover:text-white',
  'ghost'     => 'border border-brand-purple text-brand-purple hover:bg-brand-purple hover:text-white',
];
$cls = 'inline-block rounded px-4 py-2 text-sm font-semibold transition ' . $styles[$variant] . ' ' . $className;
?>
<?php if ($href): ?><a href="<?= e($href) ?>" class="<?= $cls ?>" <?= $attrs ?>><?= e($label) ?></a>
<?php else: ?><button type="<?= e($type) ?>" class="<?= $cls ?>" <?= $attrs ?>><?= e($label) ?></button><?php endif; ?>
