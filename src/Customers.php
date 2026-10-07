<?php
final class Customers {
    public static function forUser(string $username): array {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, phone_number, birth_date, order_date, notes
             FROM customers WHERE username = ? ORDER BY name, id'
        );
        $stmt->execute([$username]);
        $customers = [];
        foreach ($stmt->fetchAll() as $customer) {
            $customer['product_codes'] = [];
            $customers[(int)$customer['id']] = $customer;
        }
        if (!$customers) return [];

        $ids = array_keys($customers);
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $products = Database::pdo()->prepare("SELECT customer_id, product_code FROM customer_products WHERE customer_id IN ($marks)");
        $products->execute($ids);
        foreach ($products->fetchAll() as $product) {
            $customers[(int)$product['customer_id']]['product_codes'][] = $product['product_code'];
        }
        return array_values($customers);
    }

    public static function create(string $username, array $input): void {
        $values = self::validateFields($input, true);
        $productCodes = self::productCodes($input['product_codes'] ?? []);
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO customers (username, name, phone_number, birth_date, order_date, notes)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$username, ...array_values($values)]);
            self::replaceProducts($pdo, (int)$pdo->lastInsertId(), $productCodes);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function update(string $username, int $id, string $field, mixed $value): void {
        $check = Database::pdo()->prepare('SELECT id FROM customers WHERE id = ? AND username = ?');
        $check->execute([$id, $username]);
        if (!$check->fetchColumn()) throw new InvalidArgumentException('Customer not found.');

        if ($field === 'product_codes') {
            $productCodes = self::productCodes($value);
            $pdo = Database::pdo();
            $pdo->beginTransaction();
            try {
                self::replaceProducts($pdo, $id, $productCodes);
                $pdo->commit();
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $error;
            }
            return;
        }

        $fieldValues = self::validateFields([$field => $value], false);
        $allowed = ['name', 'phone_number', 'birth_date', 'order_date', 'notes'];
        if (!in_array($field, $allowed, true)) throw new InvalidArgumentException('Field not editable.');
        Database::pdo()->prepare("UPDATE customers SET $field = ? WHERE id = ? AND username = ?")
            ->execute([$fieldValues[$field], $id, $username]);
    }

    public static function birthdayColor(?string $birthDate, DateTimeImmutable $today): string {
        if (!$birthDate) return '';
        $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        if (!$birth || $birth->format('Y-m-d') !== $birthDate) return '';

        $month = (int)$birth->format('n');
        $day = (int)$birth->format('j');
        $nearest = PHP_INT_MAX;
        foreach ([(int)$today->format('Y') - 1, (int)$today->format('Y'), (int)$today->format('Y') + 1] as $year) {
            $birthdayDay = $month === 2 && $day === 29 && !checkdate(2, 29, $year) ? 28 : $day;
            $birthday = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $birthdayDay));
            $nearest = min($nearest, abs((int)$today->diff($birthday)->format('%r%a')));
        }
        if ($nearest === 0) return 'bg-green-100';
        return $nearest <= 7 ? 'bg-red-100' : '';
    }

    private static function validateFields(array $input, bool $creating): array {
        $fields = [];
        if ($creating || array_key_exists('name', $input)) {
            $fields['name'] = self::text($input['name'] ?? '', 'Name', 120, true);
        }
        if ($creating || array_key_exists('phone_number', $input)) {
            $fields['phone_number'] = self::text($input['phone_number'] ?? '', 'Phone Number', 40);
        }
        if ($creating || array_key_exists('birth_date', $input)) {
            $fields['birth_date'] = self::date($input['birth_date'] ?? '', 'Birth Date');
        }
        if ($creating || array_key_exists('order_date', $input)) {
            $fields['order_date'] = self::date($input['order_date'] ?? '', 'Order Date');
        }
        if ($creating || array_key_exists('notes', $input)) {
            $fields['notes'] = self::text($input['notes'] ?? '', 'Catatan', 2000);
        }
        return $fields;
    }

    private static function text(mixed $value, string $label, int $max, bool $required = false): ?string {
        if (!is_scalar($value)) throw new InvalidArgumentException('Invalid ' . $label . '.');
        $text = trim((string)$value);
        if ($required && $text === '') throw new InvalidArgumentException($label . ' is required.');
        if (mb_strlen($text) > $max) throw new InvalidArgumentException($label . ' must be ' . $max . ' characters or fewer.');
        return $text === '' ? null : $text;
    }

    private static function date(mixed $value, string $label): ?string {
        if (!is_scalar($value)) throw new InvalidArgumentException('Invalid ' . $label . '.');
        $value = trim((string)$value);
        if ($value === '') return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Invalid ' . $label . '.');
        return $value;
    }

    private static function productCodes(mixed $codes): array {
        if (!is_array($codes)) throw new InvalidArgumentException('Choose products from the list.');
        $available = Products::products();
        $selected = [];
        foreach ($codes as $code) {
            if (!is_string($code) || !isset($available[$code])) throw new InvalidArgumentException('Choose products from the list.');
            $selected[$code] = $code;
        }
        return array_values($selected);
    }

    private static function replaceProducts(PDO $pdo, int $customerId, array $productCodes): void {
        $pdo->prepare('DELETE FROM customer_products WHERE customer_id = ?')->execute([$customerId]);
        $insert = $pdo->prepare('INSERT INTO customer_products (customer_id, product_code) VALUES (?, ?)');
        foreach ($productCodes as $code) $insert->execute([$customerId, $code]);
    }
}