<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Order
{
    public static function find(int $id): ?array
    {
        return Database::instance()->first(
            'SELECT o.*, c.name AS client_name, c.type AS client_type, u.name AS owner_name, u.avatar_color AS owner_color
             FROM orders o
             LEFT JOIN clients c ON c.id = o.client_id
             LEFT JOIN users u ON u.id = o.owner_id
             WHERE o.id = ?',
            [$id]
        );
    }

    public static function paginate(array $filters = [], int $limit = 60): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['owner_id'])) {
            $where[] = 'o.owner_id = ?';
            $params[] = $filters['owner_id'];
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'o.client_id = ?';
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(o.title LIKE ? OR o.number LIKE ? OR c.name LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }

        $sql = 'SELECT o.*, c.name AS client_name, u.name AS owner_name, u.avatar_color AS owner_color
                FROM orders o
                LEFT JOIN clients c ON c.id = o.client_id
                LEFT JOIN users u ON u.id = o.owner_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC LIMIT ' . max(1, min(200, $limit));

        return Database::instance()->all($sql, $params);
    }

    public static function nextNumber(): string
    {
        $last = (int) Database::instance()->value(
            'SELECT COALESCE(MAX(id), 0) FROM orders'
        );
        return 'AA7-' . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function create(array $data): int
    {
        return Database::instance()->insert(
            'INSERT INTO orders
                (number, title, description, client_id, owner_id, status, priority,
                 amount_input, currency, fx_rate, amount_rub, payment_status, progress, tags, due_at)
             VALUES
                (:number, :title, :description, :client_id, :owner_id, :status, :priority,
                 :amount_input, :currency, :fx_rate, :amount_rub, :payment_status, :progress, :tags, :due_at)',
            $data
        );
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        Database::instance()->run(
            'UPDATE orders SET title = :title, description = :description, client_id = :client_id,
                owner_id = :owner_id, status = :status, priority = :priority,
                amount_input = :amount_input, currency = :currency, fx_rate = :fx_rate,
                amount_rub = :amount_rub, progress = :progress, tags = :tags, due_at = :due_at
             WHERE id = :id',
            $data
        );
    }

    public static function changeStatus(int $id, string $status): void
    {
        $progress = $status === 'done' ? 100 : null;
        if ($progress !== null) {
            Database::instance()->run('UPDATE orders SET status = ?, progress = 100 WHERE id = ?', [$status, $id]);
        } else {
            Database::instance()->run('UPDATE orders SET status = ? WHERE id = ?', [$status, $id]);
        }
    }

    public static function events(int $orderId): array
    {
        return Database::instance()->all(
            'SELECT e.*, u.name AS user_name, u.avatar_color
             FROM order_events e LEFT JOIN users u ON u.id = e.user_id
             WHERE e.order_id = ? ORDER BY e.id DESC',
            [$orderId]
        );
    }

    public static function addEvent(int $orderId, ?int $userId, string $type, string $message): void
    {
        Database::instance()->run(
            'INSERT INTO order_events (order_id, user_id, type, message) VALUES (?,?,?,?)',
            [$orderId, $userId, $type, $message]
        );
    }

    public static function recalcPayment(int $orderId): void
    {
        $db = Database::instance();
        $order = $db->first('SELECT amount_rub FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return;
        }
        $paid = (float) $db->value('SELECT COALESCE(SUM(amount_rub),0) FROM payments WHERE order_id = ?', [$orderId]);
        $amount = (float) $order['amount_rub'];
        $status = $paid <= 0 ? 'unpaid' : ($paid >= $amount ? 'paid' : 'partial');
        $db->run('UPDATE orders SET paid_rub = ?, payment_status = ? WHERE id = ?', [$paid, $status, $orderId]);
    }
}
