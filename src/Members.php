<?php
final class Members {
    private static ?array $rankOptions = null;
    public const STATUSES = ['Active' => 'Active', 'Inactive' => 'Inactive'];
    public const EDITABLE = ['name', 'rank_code', 'status'];

    public static function ranks(): array {
        return self::$rankOptions ??= Database::pdo()
            ->query('SELECT rank_code, rank_name FROM ranks ORDER BY no ASC')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function newCode(): string {
        $s = Database::pdo()->prepare('SELECT 1 FROM members WHERE member_code = ?');
        do { $code = 'ESK' . random_int(100000, 999999); $s->execute([$code]); } while ($s->fetch());
        return $code;
    }

    public static function create(string $name, string $join, string $rank, ?string $upline, ?string $sponsor, string $by): string {
        if (!isset(self::ranks()[$rank])) throw new InvalidArgumentException('Invalid rank.');
        $code = self::newCode();
        $sql = 'INSERT INTO members (member_code,name,join_date,rank_code,upline_code,sponsor_code,created_by) VALUES (?,?,?,?,?,?,?)';
        $params = [$code, $name, $join, $rank, $upline, $sponsor, $by];
        try {
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            return $code;
        } catch (Throwable $e) {
            Logger::write('DB', 'Members::create failed. SQL=' . $sql . ' params=' . json_encode($params, JSON_THROW_ON_ERROR) . ' error=' . $e->getMessage(), __FILE__, __LINE__);
            throw $e;
        }
    }

    public static function find(string $code): ?array {
        $s = Database::pdo()->prepare('SELECT * FROM members WHERE member_code = ? AND deleted_date IS NULL');
        $s->execute([$code]);
        return $s->fetch() ?: null;
    }

    public static function countDownline(string $sponsor): int {
        $s = Database::pdo()->prepare('SELECT COUNT(*) FROM members WHERE sponsor_code = ? AND deleted_date IS NULL');
        $s->execute([$sponsor]);
        return (int)$s->fetchColumn();
    }

    /** Direct downline joined with this user's follow-up data. $limit null = all rows (export). */
    public static function downline(string $sponsor, string $username, ?int $limit = null, int $offset = 0): array {
        $sql = 'SELECT m.member_code, m.name, m.join_date, m.rank_code, m.status,
                       f.consume_product, f.product_name, f.consume_date, f.prospect_list,
                       f.welcome_to_eskayvie, f.product, f.marketing_plan, f.hbo, f.remarks
                FROM members m
                LEFT JOIN followups f ON f.member_code = m.member_code AND f.username = :u AND f.deleted_date IS NULL
                WHERE m.sponsor_code = :s AND m.deleted_date IS NULL
                ORDER BY m.join_date DESC, m.id DESC' . ($limit !== null ? ' LIMIT :l OFFSET :o' : '');
        $st = Database::pdo()->prepare($sql);
        $st->bindValue(':u', $username);
        $st->bindValue(':s', $sponsor);
        if ($limit !== null) { $st->bindValue(':l', $limit, PDO::PARAM_INT); $st->bindValue(':o', $offset, PDO::PARAM_INT); }
        $st->execute();
        return $st->fetchAll();
    }

    public static function update(string $code, string $field, mixed $value, string $by): void {
        if (!in_array($field, self::EDITABLE, true)) throw new InvalidArgumentException('Field not editable.');
        $v = trim((string)$value);
        if ($field === 'name' && ($v === '' || mb_strlen($v) > 120)) throw new InvalidArgumentException('Name is required (max 120 characters).');
        if ($field === 'rank_code' && !isset(self::ranks()[$v])) throw new InvalidArgumentException('Invalid rank.');
        if ($field === 'status' && !isset(self::STATUSES[$v])) throw new InvalidArgumentException('Invalid status.');
        Database::pdo()->prepare("UPDATE members SET $field = ?, updated_by = ? WHERE member_code = ?")->execute([$v, $by, $code]);
    }
}
