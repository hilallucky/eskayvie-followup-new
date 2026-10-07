<?php
final class Auth {
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function require(): array { return self::user() ?? redirect('login.php'); }

    /** @return true|string true on success, otherwise an error message */
    public static function attempt(string $username, string $password): true|string {
        // echo('Auth::attempt called with username: ' . e($username) . ' and password: ' . e($password)); // Debugging line
        $s = Database::pdo()->prepare('SELECT * FROM users WHERE (username = ? or email = ?) AND deleted_date IS NULL');
        $s->execute([$username, $username]);
        $u = $s->fetch();
        // var_dump($u); // Debugging line to check the fetched user data
        if (!$u || !password_verify($password, $u['password_hash'])) return 'Invalid username or password.';
        if (!$u['email_confirmed_at']) return 'Please confirm your email first (check your inbox).';
        if ($u['status'] !== 'Active') return 'This account is inactive.';
        session_regenerate_id(true);
        $_SESSION['user'] = ['username' => $u['username'], 'member_code' => $u['member_code']];
        return true;
    }
    public static function logout(): void { $_SESSION = []; session_destroy(); }
}
