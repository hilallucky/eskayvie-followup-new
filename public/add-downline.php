<?php
require __DIR__ . '/../src/bootstrap.php';
$u = Auth::require();

// echo('Add downline POST: ' . json_encode($_POST));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['_csrf'] ?? null)) redirect('index.php');
$name = trim($_POST['name'] ?? ''); 
$rank = $_POST['rank_code'] ?? ''; 
$join = $_POST['join_date'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $join);

if ($name === '' || mb_strlen($name) > 120 || !isset(Members::ranks()[$rank]) || !$d || $d->format('Y-m-d') !== $join) {
    $_SESSION['flash'] = ['error', 'Please check the downline name, rank and join date.'];
} else {
    try {
        $code = Members::create($name, $join, $rank, $u['member_code'], $u['member_code'], $u['username']);
        // echo('INFO Downline created. member_code=' . $code . ' sponsor=' . $u['member_code'] . ' username=' . $u['username']);
        // Logger::write('INFO', 'Downline created. member_code=' . $code . ' sponsor=' . $u['member_code'] . ' username=' . $u['username'], __FILE__, __LINE__);
        $_SESSION['flash'] = ['ok', "Downline added ($code)."];
    } catch (Throwable $e) {
        // echo('DB else Add downline failed. name=' . $name . ' rank=' . $rank . ' join=' . $join . ' sponsor=' . $u['member_code'] . ' user=' . $u['username'] . ' error=' . $e->getMessage());
        // Logger::write('DB', 'Add downline failed. name=' . $name . ' rank=' . $rank . ' join=' . $join . ' sponsor=' . $u['member_code'] . ' user=' . $u['username'] . ' error=' . $e->getMessage(), __FILE__, __LINE__);
        $_SESSION['flash'] = ['error', 'Could not add downline. Please check the log and database.'];
    }
}
redirect('index.php');
