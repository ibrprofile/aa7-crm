<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Payment
{
    public static function forOrder(int $orderId): array
    {
        return Database::instance()->all(
            'SELECT p.*, u.name AS user_name FROM payments p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.order_id = ? ORDER BY p.paid_at DESC, p.id DESC',
            [$orderId]
        );
    }

    public static function recent(int $limit = 12): array
    {
        return Database::instance()->all(
            'SELECT p.*, p.order_id, o.number AS order_number, o.title AS order_title, c.name AS client_name
             FROM payments p
             LEFT JOIN orders o ON o.id = p.order_id
             LEFT JOIN clients c ON c.id = o.client_id
             ORDER BY p.paid_at DESC, p.id DESC LIMIT ' . max(1, min(100, $limit))
        );
    }

    public static function create(array $data): int
    {
        return Database::instance()->insert(
            'INSERT INTO payments (order_id, amount_input, currency, fx_rate, amount_rub, method, note, created_by, paid_at)
             VALUES (:order_id, :amount_input, :currency, :fx_rate, :amount_rub, :method, :note, :created_by, :paid_at)',
            $data
        );
    }

    public static function delete(int $id): ?int
    {
        $orderId = Database::instance()->value('SELECT order_id FROM payments WHERE id = ?', [$id]);
        Database::instance()->run('DELETE FROM payments WHERE id = ?', [$id]);
        return $orderId ? (int) $orderId : null;
    }
}
