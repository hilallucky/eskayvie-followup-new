<?php
final class DailyTasks {
    public const FIELDS = [
        'learn', 'video_onboarding', 'consume_product', 'repeat_order',
        'attend_zoom', 'contact_upline', 'sponsoring',
    ];

    public static function forMonth(string $username, int $month, int $year): array {
        $stmt = Database::pdo()->prepare(
            'SELECT e.* FROM daily_task_months m
             JOIN daily_task_entries e ON e.month_record_id = m.id
             WHERE m.username = ? AND m.task_month = ? AND m.task_year = ?'
        );
        $stmt->execute([$username, $month, $year]);
        $entries = [];
        foreach ($stmt->fetchAll() as $entry) $entries[(int)$entry['day_of_month']] = $entry;
        return $entries;
    }

    public static function save(string $username, int $month, int $year, int $day, string $field, mixed $value): void {
        if ($month < 1 || $month > 12 || $year < 1900 || $year > 9999) throw new InvalidArgumentException('Choose a valid month and year.');
        $dayCount = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
        if ($day < 1 || $day > $dayCount) throw new InvalidArgumentException('Choose a valid day.');
        if (!in_array($field, self::FIELDS, true)) throw new InvalidArgumentException('Task not editable.');
        if (!in_array($value, [0, 1, '0', '1'], true)) throw new InvalidArgumentException('Task value must be checked or unchecked.');

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO daily_task_months (username, task_month, task_year)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $stmt->execute([$username, $month, $year]);
            $monthId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO daily_task_entries (month_record_id, day_of_month, $field)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE $field = VALUES($field)"
            );
            $stmt->execute([$monthId, $day, (int)$value]);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}