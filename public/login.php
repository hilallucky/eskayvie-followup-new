<?php
require __DIR__ . '/../src/bootstrap.php';
if (Auth::user()) redirect('index.php');
$error = null;
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expected = $_SESSION['captcha'] ?? null; unset($_SESSION['captcha']);
    if (!csrf_check($_POST['_csrf'] ?? null)) $error = 'Session expired, please try again.';
    elseif ($expected === null || (int)($_POST['captcha'] ?? -1) !== $expected) $error = 'Captcha answer is incorrect.';
    else { $r = Auth::attempt(trim($_POST['username'] ?? ''), $_POST['password'] ?? ''); if ($r === true) redirect('index.php'); $error = $r; }
    //  $r = Auth::attempt(trim($_POST['username'] ?? ''), $_POST['password'] ?? ''); if ($r === true) redirect('index.php'); $error = $r; 
}
[$a, $b] = [random_int(1, 9), random_int(1, 9)]; $_SESSION['captcha'] = $a + $b;
component('layout-start', ['title' => 'Login Form']);
if ($flash) component('alert', ['type' => $flash[0], 'message' => $flash[1]]);
if ($error) component('alert', ['message' => $error]); ?>
<form method="post" class="space-y-4">
  <input type="hidden" name="_csrf" value="<?= e(csrf()) ?>">
  <?php
  component('input', ['label' => 'Username', 'namex' => 'username', 'value' => $_POST['username'] ?? '', 'attrs' => 'autocomplete="username"']);
  component('input', ['label' => 'Password', 'namex' => 'password', 'type' => 'password', 'attrs' => 'autocomplete="current-password"']);
  component('input', ['label' => "Captcha: $a + $b = ?", 'namex' => 'captcha', 'attrs' => 'inputmode="numeric" autocomplete="off"']);
  ?>
  <div class="flex gap-2"><?php component('button', ['label' => 'Login', 'type' => 'submit']); component('button', ['label' => 'Signup', 'href' => 'signup.php', 'variant' => 'secondary']); ?></div>
</form>
<?php component('layout-end');
