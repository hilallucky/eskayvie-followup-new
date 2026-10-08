<?php
final class Prospects {
    public const RESPONSES = ['Baik' => 'Baik', 'Kurang' => 'Kurang', 'Tidak Baik' => 'Tidak Baik'];
    public const FOLLOW_UP_OPTIONS = ['1' => 'Yes', '0' => 'No'];

    public static function forUser(string $username): array {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, phone_number, domicile, occupation, prospect_date, response, reason,
                    follow_up, follow_up_date
             FROM prospects WHERE username = ? ORDER BY id'
        );
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }

    public static function create(string $username, array $input): void {
        $values = [
            'name' => self::validate('name', $input['name'] ?? ''),
            'phone_number' => self::validate('phone_number', $input['phone_number'] ?? ''),
            'domicile' => self::validate('domicile', $input['domicile'] ?? ''),
            'occupation' => self::validate('occupation', $input['occupation'] ?? ''),
            'prospect_date' => self::validate('prospect_date', $input['prospect_date'] ?? ''),
            'response' => self::validate('response', $input['response'] ?? ''),
            'reason' => self::validate('reason', $input['reason'] ?? ''),
            'follow_up' => self::validate('follow_up', $input['follow_up'] ?? '0'),
            'follow_up_date' => self::validate('follow_up_date', $input['follow_up_date'] ?? ''),
        ];
        Database::pdo()->prepare(
            'INSERT INTO prospects (username, name, phone_number, domicile, occupation, prospect_date, response, reason, follow_up, follow_up_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$username, ...array_values($values)]);
    }

    public static function update(string $username, int $id, string $field, mixed $value): void {
        $allowed = ['name', 'phone_number', 'domicile', 'occupation', 'prospect_date', 'response', 'reason', 'follow_up', 'follow_up_date'];
        if (!in_array($field, $allowed, true)) throw new InvalidArgumentException('Field not editable.');
        $check = Database::pdo()->prepare('SELECT id FROM prospects WHERE id = ? AND username = ?');
        $check->execute([$id, $username]);
        if (!$check->fetchColumn()) throw new InvalidArgumentException('Prospect not found.');
        $validated = self::validate($field, $value);
        Database::pdo()->prepare("UPDATE prospects SET $field = ? WHERE id = ? AND username = ?")
            ->execute([$validated, $id, $username]);
    }

    private static function validate(string $field, mixed $value): mixed {
        if (!is_scalar($value)) throw new InvalidArgumentException('Invalid value.');
        $value = trim((string)$value);
        if ($field === 'name') {
            if ($value === '') throw new InvalidArgumentException('Nama is required.');
            if (mb_strlen($value) > 120) throw new InvalidArgumentException('Nama must be 120 characters or fewer.');
            return $value;
        }
        if (in_array($field, ['phone_number', 'domicile', 'occupation'], true)) {
            $max = $field === 'phone_number' ? 40 : 120;
            if (mb_strlen($value) > $max) throw new InvalidArgumentException(ucfirst($field) . ' must be ' . $max . ' characters or fewer.');
            return $value === '' ? null : $value;
        }
        if (in_array($field, ['prospect_date', 'follow_up_date'], true)) {
            if ($value === '') return null;
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Invalid date.');
            return $value;
        }
        if ($field === 'response') {
            if ($value === '') return null;
            if (!isset(self::RESPONSES[$value])) throw new InvalidArgumentException('Choose a valid response.');
            return $value;
        }
        if ($field === 'reason') {
            if (mb_strlen($value) > 2000) throw new InvalidArgumentException('Alasan must be 2000 characters or fewer.');
            return $value === '' ? null : $value;
        }
        if ($field === 'follow_up') {
            if (!array_key_exists($value, self::FOLLOW_UP_OPTIONS)) throw new InvalidArgumentException('Choose Yes or No for Follow-up.');
            return (int)$value;
        }
        throw new InvalidArgumentException('Field not editable.');
    }
}