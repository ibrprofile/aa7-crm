<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SavedLead
{
    public static function all(array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['source'])) {
            $where[]  = 'source = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }

        $sql = 'SELECT * FROM saved_leads';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 300';

        return Database::instance()->all($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::instance()->first('SELECT * FROM saved_leads WHERE id = ? LIMIT 1', [$id]);
    }

    public static function exists(string $source, string $externalId): bool
    {
        return (bool) Database::instance()->value(
            'SELECT id FROM saved_leads WHERE source = ? AND external_id = ? LIMIT 1',
            [$source, $externalId]
        );
    }

    public static function create(array $data): int
    {
        return Database::instance()->insert(
            'INSERT INTO saved_leads
                (source, external_id, name, category, city, phone, website, instagram, whatsapp, address, rating, reviews, payload, status, created_by)
             VALUES
                (:source, :external_id, :name, :category, :city, :phone, :website, :instagram, :whatsapp, :address, :rating, :reviews, :payload, :status, :created_by)',
            $data
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::instance()->run('UPDATE saved_leads SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::instance()->run('DELETE FROM saved_leads WHERE id = ?', [$id]);
    }

    public static function counts(): array
    {
        return Database::instance()->all(
            'SELECT source, COUNT(*) AS total FROM saved_leads GROUP BY source'
        );
    }
}
