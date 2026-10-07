<?php
final class Products {
    private static ?array $productOptions = null;
    public const STATUSES = ['Active' => 'Active', 'Inactive' => 'Inactive'];
    public const EDITABLE = ['name', 'rank_code', 'status'];

    public static function products(): array {
        return self::$productOptions ??= Database::pdo()
            ->query('SELECT product_code, product_name FROM products WHERE status = "Active" ORDER BY product_name ASC')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
