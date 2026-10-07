<?php
final class FollowUps {
    public const FIELDS = ['consume_product', 'prospect_list', 'welcome_to_eskayvie', 'product', 'marketing_plan', 'hbo',
                           'product_name', 'consume_date', 'remarks'];
    private const BOOL = ['consume_product', 'prospect_list', 'welcome_to_eskayvie', 'product', 'marketing_plan', 'hbo'];
    private const TEXT = ['product_name' => 100, 'remarks' => 255];

    /** Insert-or-update one field of a follow-up row (row is created on first edit). */
    public static function save(string $username, string $memberCode, string $field, mixed $value): void {
        if (!in_array($field, self::FIELDS, true)) throw new InvalidArgumentException('Field not editable.');
        $v = trim((string)$value);
        if (in_array($field, self::BOOL, true)) {
            $v = $v === '1' ? 1 : 0;
        } elseif ($field === 'consume_date') {
            if ($v === '') { $v = null; }
            else {
                $d = DateTime::createFromFormat('Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v) throw new InvalidArgumentException('Invalid date.');
            }
        } else {
            if (mb_strlen($v) > self::TEXT[$field]) throw new InvalidArgumentException('Text too long.');
            $v = $v === '' ? null : $v;
        }
        // $field is whitelisted above, so interpolating the column name is safe.
        Database::pdo()->prepare("INSERT INTO followups (username, member_code, $field, created_by) VALUES (?,?,?,?)
                                  ON DUPLICATE KEY UPDATE $field = VALUES($field), updated_by = ?")
            ->execute([$username, $memberCode, $v, $username, $username]);
    }
}
