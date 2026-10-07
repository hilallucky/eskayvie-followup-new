<?php
final class Database {
    private static ?PDO $pdo = null;
    public static function pdo(): PDO {
        if (!self::$pdo) {
            $d = cfg('db');
            logger::write('DB', "Connecting to MySQL (host={$d['host']} db={$d['name']} user={$d['user']})");
            try {
                self::$pdo = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset={$d['charset']}", $d['user'], $d['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // never log the password
                Logger::write('DB', "Connection failed (host={$d['host']} db={$d['name']} user={$d['user']}): " . $e->getMessage());
                echo "<script>console.error('MySQL Connection Error: ' + $error_message);</script>";

                throw $e;
            }
        }
        return self::$pdo;
    }
}
