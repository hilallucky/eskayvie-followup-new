<?php
final class SideVolumeTargets {
    public static function forYear(string $username, int $year): array {
        $stmt = Database::pdo()->prepare(
            'SELECT m.target_month, e.product_code, e.target_value, e.actual_value
             FROM side_volume_months m
             JOIN side_volume_entries e ON e.side_volume_month_id = m.id
             WHERE m.username = ? AND m.target_year = ?'
        );
        $stmt->execute([$username, $year]);
        $values = [];
        foreach ($stmt->fetchAll() as $entry) {
            $values[$entry['product_code']][(int)$entry['target_month']] = [
                'target' => $entry['target_value'],
                'actual' => $entry['actual_value'],
            ];
        }
        return $values;
    }

    public static function save(string $username, int $year, array $entries): void {
        if ($year < 1900 || $year > 9999) throw new InvalidArgumentException('Choose a valid year.');
        $products = Products::products();
        foreach ($entries as $productCode => $months) {
            if (!is_string($productCode) || !isset($products[$productCode]) || !is_array($months)) {
                throw new InvalidArgumentException('Invalid product data.');
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $findOrCreateMonth = $pdo->prepare(
                'INSERT INTO side_volume_months (username, target_year, target_month)
                 VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $upsert = $pdo->prepare(
                'INSERT INTO side_volume_entries (side_volume_month_id, product_code, target_value, actual_value)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE target_value = VALUES(target_value), actual_value = VALUES(actual_value)'
            );
            $delete = $pdo->prepare('DELETE FROM side_volume_entries WHERE side_volume_month_id = ? AND product_code = ?');

            for ($month = 1; $month <= 12; $month++) {
                $findOrCreateMonth->execute([$username, $year, $month]);
                $monthId = (int)$pdo->lastInsertId();
                foreach ($products as $productCode => $_productName) {
                    $row = $entries[$productCode][$month] ?? [];
                    if (!is_array($row)) throw new InvalidArgumentException('Invalid side volume entry.');
                    $target = self::number($row['target'] ?? '', 'Target');
                    $actual = self::number($row['actual'] ?? '', 'Actual');
                    if ($target === null && $actual === null) {
                        $delete->execute([$monthId, $productCode]);
                    } else {
                        $upsert->execute([$monthId, $productCode, $target, $actual]);
                    }
                }
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private static function number(mixed $value, string $label): ?string {
        if (!is_scalar($value)) throw new InvalidArgumentException('Invalid ' . $label . ' value.');
        $value = trim((string)$value);
        if ($value === '') return null;
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException($label . ' must be a non-negative number with up to two decimal places.');
        }
        return $value;
    }
}