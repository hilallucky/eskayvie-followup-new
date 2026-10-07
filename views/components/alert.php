<?php $c = ($type ?? 'error') === 'error' ? 'border-red-400 bg-red-50 text-red-800' : 'border-brand-green bg-green-50 text-brand-forest'; ?>
<div class="mb-4 rounded border px-4 py-3 text-sm <?= $c ?>"><?= e($message) ?></div>
