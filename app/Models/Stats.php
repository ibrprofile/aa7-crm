<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Stats
{
    public static function overview(): array
    {
        $db = Database::instance();

        return [
            'revenue_total'   => (float) $db->value("SELECT COALESCE(SUM(amount_rub),0) FROM payments"),
            'revenue_month'   => (float) $db->value("SELECT COALESCE(SUM(amount_rub),0) FROM payments WHERE paid_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"),
            'pipeline'        => (float) $db->value("SELECT COALESCE(SUM(amount_rub - paid_rub),0) FROM orders WHERE status NOT IN ('done','canceled')"),
            'orders_active'   => (int) $db->value("SELECT COUNT(*) FROM orders WHERE status NOT IN ('done','canceled')"),
            'orders_total'    => (int) $db->value("SELECT COUNT(*) FROM orders"),
            'orders_done'     => (int) $db->value("SELECT COUNT(*) FROM orders WHERE status = 'done'"),
            'clients_total'   => (int) $db->value("SELECT COUNT(*) FROM clients"),
            'leads_total'     => (int) $db->value("SELECT COUNT(*) FROM saved_leads"),
            'overdue'         => (int) $db->value("SELECT COUNT(*) FROM orders WHERE due_at IS NOT NULL AND due_at < NOW() AND status NOT IN ('done','canceled')"),
            'receivable'      => (float) $db->value("SELECT COALESCE(SUM(amount_rub - paid_rub),0) FROM orders WHERE payment_status <> 'paid' AND status <> 'canceled'"),
        ];
    }

    public static function revenueByMonth(int $months = 12): array
    {
        return Database::instance()->all(
            "SELECT DATE_FORMAT(paid_at,'%Y-%m') AS bucket, SUM(amount_rub) AS total
             FROM payments
             WHERE paid_at >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL ? MONTH)
             GROUP BY bucket ORDER BY bucket",
            [$months - 1]
        );
    }

    public static function ordersByStatus(): array
    {
        return Database::instance()->all(
            "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount_rub),0) AS amount
             FROM orders GROUP BY status"
        );
    }

    public static function topClients(int $limit = 6): array
    {
        return Database::instance()->all(
            "SELECT c.id, c.name, c.type, COUNT(o.id) AS orders_count, COALESCE(SUM(o.amount_rub),0) AS revenue
             FROM clients c JOIN orders o ON o.client_id = c.id
             GROUP BY c.id ORDER BY revenue DESC LIMIT " . max(1, min(20, $limit))
        );
    }

    public static function revenueByCountry(): array
    {
        return Database::instance()->all(
            "SELECT COALESCE(NULLIF(c.country,''),'—') AS country, COUNT(o.id) AS orders_count, COALESCE(SUM(o.amount_rub),0) AS revenue
             FROM orders o JOIN clients c ON c.id = o.client_id
             GROUP BY country ORDER BY revenue DESC"
        );
    }

    public static function currencySplit(): array
    {
        return Database::instance()->all(
            "SELECT currency, COUNT(*) AS total, COALESCE(SUM(amount_rub),0) AS amount
             FROM orders GROUP BY currency"
        );
    }

    public static function teamPerformance(): array
    {
        return Database::instance()->all(
            "SELECT u.id, u.name, u.avatar_color,
                    COUNT(o.id) AS orders_count,
                    COALESCE(SUM(o.amount_rub),0) AS revenue,
                    SUM(o.status = 'done') AS done_count
             FROM users u LEFT JOIN orders o ON o.owner_id = u.id
             WHERE u.is_active = 1
             GROUP BY u.id ORDER BY revenue DESC"
        );
    }

    public static function conversion(): array
    {
        $leads = (int) Database::instance()->value("SELECT COUNT(*) FROM saved_leads");
        $converted = (int) Database::instance()->value("SELECT COUNT(*) FROM saved_leads WHERE status = 'converted'");
        return [
            'leads' => $leads,
            'converted' => $converted,
            'rate' => $leads > 0 ? round($converted / $leads * 100, 1) : 0.0,
        ];
    }
}
