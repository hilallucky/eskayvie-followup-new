<?php
final class ActivityPlans {
    public static function statuses(): array {
        return Database::pdo()->query('SELECT id, status_name FROM statuses ORDER BY sort_order, status_name')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function forMonth(string $username, int $month, int $year): array {
        $stmt = Database::pdo()->prepare(
            'SELECT e.day_of_month, e.activity, e.status_id
             FROM activity_plans p
             JOIN activity_plan_entries e ON e.activity_plan_id = p.id
             WHERE p.username = ? AND p.plan_month = ? AND p.plan_year = ?'
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
        $statuses = self::statuses();
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO activity_plans (username, plan_month, plan_year)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $stmt->execute([$username, $month, $year]);
            $planId = (int)$pdo->lastInsertId();

            $upsert = $pdo->prepare(
                'INSERT INTO activity_plan_entries (activity_plan_id, day_of_month, activity, status_id)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE activity = VALUES(activity), status_id = VALUES(status_id)'
            );
            $delete = $pdo->prepare('DELETE FROM activity_plan_entries WHERE activity_plan_id = ? AND day_of_month = ?');

            for ($day = 1; $day <= $days; $day++) {
                $row = $entries[$day] ?? [];
                if (!is_array($row)) throw new InvalidArgumentException('Invalid activity entry.');
                $activityValue = $row['activity'] ?? '';
                $statusValue = $row['status_id'] ?? '';
                if (!is_scalar($activityValue) || !is_scalar($statusValue)) throw new InvalidArgumentException('Invalid activity entry.');
                $activity = trim((string)$activityValue);
                $status = trim((string)$statusValue);

                if (mb_strlen($activity) > 2000) throw new InvalidArgumentException('Rencana Aktivitas must be 2000 characters or fewer.');
                if ($status !== '' && !isset($statuses[$status])) throw new InvalidArgumentException('Choose a valid status.');

                if ($activity === '' && $status === '') {
                    $delete->execute([$planId, $day]);
                    continue;
                }
                $upsert->execute([$planId, $day, $activity === '' ? null : $activity, $status === '' ? null : (int)$status]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}