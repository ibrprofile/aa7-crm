<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Client
{
    private const FIELDS = [
        'type', 'company_id', 'name', 'legal_name', 'position', 'email', 'phone',
        'telegram', 'whatsapp', 'website', 'inn', 'country', 'city', 'address',
        'industry', 'source', 'status', 'notes', 'owner_id',
    ];

    public static function find(int $id): ?array
    {
        return Database::instance()->first('SELECT * FROM clients WHERE id = ?', [$id]);
    }

    public static function paginate(array $filters = [], int $limit = 50): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term, $term);
        }

        $sql = 'SELECT c.*, comp.name AS company_name,
                    (SELECT COUNT(*) FROM orders o WHERE o.client_id = c.id) AS orders_count,
                    (SELECT COALESCE(SUM(o.amount_rub),0) FROM orders o WHERE o.client_id = c.id) AS revenue
                FROM clients c
                LEFT JOIN clients comp ON comp.id = c.company_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.updated_at DESC LIMIT ' . max(1, min(200, $limit));

        return Database::instance()->all($sql, $params);
    }

    public static function companies(): array
    {
        return Database::instance()->all(
            'SELECT id, name FROM clients WHERE type = "company" ORDER BY name'
        );
    }

    public static function options(): array
    {
        return Database::instance()->all(
            'SELECT id, name, type FROM clients ORDER BY type = "company" DESC, name'
        );
    }

    public static function contactsOf(int $companyId): array
    {
        return Database::instance()->all(
            'SELECT * FROM clients WHERE company_id = ? ORDER BY name',
            [$companyId]
        );
    }

    public static function create(array $data): int
    {
        $payload = self::filter($data);
        $columns = implode(', ', array_keys($payload));
        $placeholders = implode(', ', array_map(fn($k) => ':' . $k, array_keys($payload)));
        return Database::instance()->insert(
            "INSERT INTO clients ($columns) VALUES ($placeholders)",
            $payload
        );
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::filter($data);
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($payload)));
        $payload['id'] = $id;
        Database::instance()->run("UPDATE clients SET $set WHERE id = :id", $payload);
    }

    public static function summary(int $id): array
    {
        $db = Database::instance();
        return [
            'orders'   => (int) $db->value('SELECT COUNT(*) FROM orders WHERE client_id = ?', [$id]),
            'revenue'  => (float) $db->value('SELECT COALESCE(SUM(amount_rub),0) FROM orders WHERE client_id = ?', [$id]),
            'paid'     => (float) $db->value('SELECT COALESCE(SUM(paid_rub),0) FROM orders WHERE client_id = ?', [$id]),
            'active'   => (int) $db->value("SELECT COUNT(*) FROM orders WHERE client_id = ? AND status NOT IN ('done','cancelled')", [$id]),
        ];
    }

    private static function filter(array $data): array
    {
        $out = [];
        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                $out[$field] = ($value === '' ) ? null : $value;
            }
        }
        return $out;
    }
}
