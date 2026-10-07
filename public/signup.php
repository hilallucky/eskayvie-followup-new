<?php
require __DIR__ . '/../src/bootstrap.php';
$errors = []; $done = null; $debugPost = null;
$ref = preg_match('/^ESK\d{6}$/', $_GET['ref'] ?? $_POST['ref'] ?? '') ? ($_GET['ref'] ?? $_POST['ref']) : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // echo('Signup POST: ' . json_encode($_POST));
    $name = trim($_POST['name'] ?? ''); 
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? ''; 
    $join = $_POST['join_date'] ?? '';
    if (cfg('debug')) {
        $debugPost = $_POST;
        foreach (['_csrf', 'password'] as $sensitive) {
            if (array_key_exists($sensitive, $debugPost)) $debugPost[$sensitive] = '[redacted]';
        }
    }

    if (!csrf_check($_POST['_csrf'] ?? null)) $errors[] = 'Session expired, please try again.';
    if (!$errors) {
        try {
            $token = bin2hex(random_bytes(32)); 
            $code = Users::register($name, $email, $pass, $join, $ref, $token);
            $link = rtrim(cfg('base_url'), '/') . '/confirm.php?token=' . $token;
            Mailer::welcome($email, $name, $code, $link);
            $done = ['code' => $code, 'link' => $link];
        } catch (PDOException $x) {
            // echo '<pre>PDO`Exception: ' . e($x->getMessage()) . '</pre>'; // for dev only
            if ($x->getCode() === '23000') $errors[] = 'This email is already registered.'; else throw $x;
        } catch (Throwable $t) {
            // echo '<pre>Throwable: ' . e($t->getMessage()) . '</pre>'; // for dev only
            throw $t;
        } catch (InvalidArgumentException $x) {
            $errors[] = $x->getMessage();
        }
    }
}
component('layout-start', ['title' => 'Signup']);

if ($done) {
    component('alert', ['type' => 'ok', 'message' => 'Saved! Your member code is ' . $done['code'] . '. A confirmation link was sent by email.']);
    // component('button', ['label' => 'Go to login', 'href' => 'login.php', 'variant' => 'secondary']);
    if (cfg('mail_debug')) echo '<p class="break-all text-xs">Dev link: <a class="text-brand-purple underline" href="' . e($done['link']) . '">' . e($done['link']) . '</a></p>';

} else {
    foreach ($errors as $m) component('alert', ['message' => $m]); ?>
<form method="post" class="space-y-4">
  <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="ref" value="<?= e($ref) ?>">
  <?php
  component('input', ['label' => 'Name', 'namex' => 'name', 'value' => $_POST['name'] ?? '']);
  component('input', ['label' => 'Email', 'namex' => 'email', 'type' => 'email', 'value' => $_POST['email'] ?? '']);
  component('input', ['label' => 'Password', 'namex' => 'password', 'type' => 'password', 'attrs' => 'minlength="8"']);
  component('input', ['label' => 'Join Date', 'namex' => 'join_date', 'type' => 'date', 'value' => $_POST['join_date'] ?? date('Y-m-d')]);
  ?>
  <div class="flex gap-2"><?php component('button', ['label' => 'Save', 'type' => 'submit']); component('button', ['label' => 'Back to login', 'href' => 'login.php', 'variant' => 'ghost']); ?></div>
  <p class="text-xs text-gray-500">After saved: an email is sent with your username and a link to confirm your account.</p>
</form>
<?php }
component('layout-end');
