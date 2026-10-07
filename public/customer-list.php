<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();
$flash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_check($_POST['_csrf'] ?? null)) throw new InvalidArgumentException('Session expired. Reload the page and try again.');
        Customers::create($u['username'], $_POST);
        $_SESSION['customer_flash'] = ['success', 'Customer added.'];
    } catch (InvalidArgumentException $error) {
        $_SESSION['customer_flash'] = ['error', $error->getMessage()];
    } catch (Throwable $error) {
        Logger::write('ERROR', 'Customer create failed: ' . $error->getMessage(), __FILE__, __LINE__);
        $_SESSION['customer_flash'] = ['error', 'Could not add the customer. Please try again.'];
    }
    redirect('customer-list.php');
}

$products = Products::products();
$customers = Customers::forUser($u['username']);
$today = new DateTimeImmutable('today');
component('layout-start', ['title' => 'Customer List', 'user' => $u, 'wide' => true]);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
?>
<details class="mb-6 rounded border border-gray-300 p-4">
  <summary class="cursor-pointer font-semibold text-brand-purple">Add Customer</summary>
  <form method="post" class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
    <?php
    component('input', ['label' => 'Name', 'namex' => 'name']);
    component('input', ['label' => 'Phone Number', 'namex' => 'phone_number', 'required' => false]);
    component('input', ['label' => 'Birth Date', 'namex' => 'birth_date', 'type' => 'date', 'required' => false]);
    component('input', ['label' => 'Order Date', 'namex' => 'order_date', 'type' => 'date', 'required' => false]);
    ?>
    <fieldset class="sm:col-span-2 xl:col-span-4">
      <legend class="mb-2 text-sm font-medium text-gray-700">Product</legend>
      <div class="flex flex-wrap gap-x-5 gap-y-2">
        <?php foreach ($products as $code => $name): ?>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="product_codes[]" value="<?= e($code) ?>" class="rounded border-gray-300 text-brand-purple focus:ring-brand-green"> <?= e($name) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <label class="grid gap-1 text-sm font-medium text-gray-700 sm:col-span-2 xl:col-span-4">Catatan
      <?php component('textarea', ['namex' => 'notes', 'maxlength' => 2000, 'rows' => 2, 'ariaLabel' => 'Catatan', 'attrs' => 'data-auto-grow']); ?>
    </label>
    <div class="sm:col-span-2 xl:col-span-4">
      <button type="submit" class="rounded bg-brand-purple px-4 py-2 text-sm font-semibold text-white hover:bg-brand-deep">Add Customer</button>
    </div>
  </form>
</details>

<div class="mb-3 text-sm text-gray-600">Customer List · <?= count($customers) ?> records</div>
<div class="overflow-x-auto rounded border border-gray-300">
  <table class="w-full min-w-[1050px] border-collapse text-sm">
    <thead class="bg-brand-purple text-white">
      <tr class="text-center">
        <th class="w-12 border border-white/30 px-2 py-2">No.</th>
        <th class="border border-white/30 px-2 py-2">Nama</th>
        <th class="border border-white/30 px-2 py-2">No. Handphone</th>
        <th class="border border-white/30 px-2 py-2">Ulang Tahun</th>
        <th class="border border-white/30 px-2 py-2">Produk</th>
        <th class="border border-white/30 px-2 py-2">Order Date</th>
        <th class="border border-white/30 px-2 py-2">Catatan</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$customers): ?><tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No customers yet. Use “Add Customer” above.</td></tr><?php endif; ?>
      <?php foreach ($customers as $index => $customer) component('customer-row', ['i' => $index + 1, 'customer' => $customer, 'products' => $products, 'today' => $today]); ?>
    </tbody>
  </table>
</div>
<?php component('layout-end'); ?>