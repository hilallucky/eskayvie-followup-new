<?php
final class MonthlyTargets {
    public static function statuses(): array {
        return Database::pdo()->query('SELECT id, status_name FROM statuses ORDER BY sort_order, status_name')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function forMonth(string $username, int $month, int $year): array {
        $stmt = Database::pdo()->prepare(
            'SELECT e.day_of_month, e.target_income, e.target_recruit, e.target_ro,
                    e.rank_code, e.effort, e.status_id
             FROM monthly_targets t
             JOIN monthly_target_entries e ON e.monthly_target_id = t.id
             WHERE t.username = ? AND t.target_month = ? AND t.target_year = ?'
        );
        $stmt->execute([$username, $month, $year]);
        $entries = [];
        foreach ($stmt->fetchAll() as $entry) $entries[(int)$entry['day_of_month']] = $entry;
        return $entries;
    }

    public static function save(string $username, int $month, int $year, array $entries): void {
        if ($month < 1 || $month > 12 || $year < 1900 || $year > 9999) {
            throw new InvalidArgumentException('Choose a valid month and year.');
        }
        $days = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        $ranks = Members::ranks();
        $statuses = self::statuses();
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO monthly_targets (username, target_month, target_year)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $stmt->execute([$username, $month, $year]);
            $targetId = (int)$pdo->lastInsertId();

            $upsert = $pdo->prepare(
                'INSERT INTO monthly_target_entries
                    (monthly_target_id, day_of_month, target_income, target_recruit, target_ro, rank_code, effort, status_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE target_income = VALUES(target_income), target_recruit = VALUES(target_recruit),
                    target_ro = VALUES(target_ro), rank_code = VALUES(rank_code), effort = VALUES(effort), status_id = VALUES(status_id)'
            );
            $delete = $pdo->prepare('DELETE FROM monthly_target_entries WHERE monthly_target_id = ? AND day_of_month = ?');

            for ($day = 1; $day <= $days; $day++) {
                $row = $entries[$day] ?? [];
                $income = self::number($row['target_income'] ?? '', 'Target Pendapatan');
                $recruit = self::number($row['target_recruit'] ?? '', 'Target Rekrut');
                $ro = self::number($row['target_ro'] ?? '', 'Target RO');
                $rank = trim((string)($row['rank_code'] ?? ''));
                $effort = trim((string)($row['effort'] ?? ''));
                $status = trim((string)($row['status_id'] ?? ''));

                if ($rank !== '' && !isset($ranks[$rank])) throw new InvalidArgumentException('Choose a valid target rank.');
                if ($status !== '' && !isset($statuses[$status])) throw new InvalidArgumentException('Choose a valid status.');
                if (mb_strlen($effort) > 1000) throw new InvalidArgumentException('Usaha yang dilakukan must be 1000 characters or fewer.');

                if ($income === null && $recruit === null && $ro === null && $rank === '' && $effort === '' && $status === '') {
                    $delete->execute([$targetId, $day]);
                    continue;
                }
                $upsert->execute([
                    $targetId, $day, $income, $recruit, $ro,
                    $rank === '' ? null : $rank,
                    $effort === '' ? null : $effort,
                    $status === '' ? null : (int)$status,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private static function number(mixed $value, string $label): ?int {
        $value = trim((string)$value);
        if ($value === '') return null;
        if (!preg_match('/^(?:\d+|\d{1,3}(?:,\d{3})+)$/D', $value)) {
            throw new InvalidArgumentException($label . ' must be a non-negative whole number.');
        }
        $digits = ltrim(str_replace(',', '', $value), '0') ?: '0';
        $maximum = (string)PHP_INT_MAX;
        if (strlen($digits) > strlen($maximum) || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new InvalidArgumentException($label . ' is too large.');
        }
        return (int)$digits;
    }
}