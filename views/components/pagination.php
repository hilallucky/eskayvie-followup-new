<?php /* props: $page, $pages */ if ($pages > 1): ?>
<nav class="mt-4 flex flex-wrap justify-end gap-1 text-sm">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?page=<?= $p ?>" class="rounded px-3 py-1 <?= $p === $page ? 'bg-brand-purple text-white' : 'border border-gray-300 hover:bg-gray-100' ?>"><?= $p ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
