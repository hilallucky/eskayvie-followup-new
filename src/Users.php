<?php
final class Users {
    /** Creates the member + login account. Returns the new member_code (also the username). */
    public static function register(string $name, string $email, string $password, string $join, ?string $ref, string $token): string {
        $name = trim($name);
        $email = strtolower(trim($email));
        $joinDate = DateTime::createFromFormat('!Y-m-d', $join);
        if ($name === '' || mb_strlen($name) > 3) throw new InvalidArgumentException('Please enter a name up to 3 characters.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Please enter a valid email.');
        if (strlen($password) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
        if (!$joinDate || $joinDate->format('Y-m-d') !== $join) throw new InvalidArgumentException('Please enter a valid join date.');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new InvalidArgumentException('Invalid confirmation token.');

        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $sponsor = ($ref && Members::find($ref)) ? $ref : null;
            $code = Members::create($name, $join, 'MGR', $sponsor, $sponsor, 'self-signup');
            $db->prepare('INSERT INTO users (username,member_code,email,password_hash,confirm_token,created_by) VALUES (?,?,?,?,?,?)')
               ->execute([$code, $code, $email, password_hash($password, PASSWORD_DEFAULT), $token, 'self-signup']);
            $db->commit();
            return $code;
        } catch (Throwable $t) {
            if ($db->inTransaction()) $db->rollBack();
            throw $t;
        }
    }

    public static function confirm(string $token): bool {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) return false;
        $s = Database::pdo()->prepare('UPDATE users SET email_confirmed_at = NOW(), confirm_token = NULL WHERE confirm_token = ? AND email_confirmed_at IS NULL');
        $s->execute([$token]);
        return $s->rowCount() === 1;
    }
}
