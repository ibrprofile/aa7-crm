<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Человекочитаемые метки и цветовые классы для статусов и приоритетов.
 */
final class Labels
{
    public const ORDER_STATUS = [
        'new'         => ['Новый', 'blue'],
        'negotiation' => ['Переговоры', 'amber'],
        'in_progress' => ['В работе', 'blue'],
        'review'      => ['На проверке', 'amber'],
        'done'        => ['Завершён', 'green'],
        'cancelled'   => ['Отменён', 'red'],
    ];

    public const PRIORITY = [
        'low'    => ['Низкий', 'muted'],
        'normal' => ['Обычный', 'muted'],
        'high'   => ['Высокий', 'amber'],
        'urgent' => ['Срочный', 'red'],
    ];

    public const PAYMENT_STATUS = [
        'unpaid'  => ['Не оплачен', 'red'],
        'partial' => ['Частично', 'amber'],
        'paid'    => ['Оплачен', 'green'],
    ];

    public const CLIENT_STATUS = [
        'lead'     => ['Лид', 'blue'],
        'active'   => ['Активный', 'green'],
        'vip'      => ['VIP', 'amber'],
        'archived' => ['Архив', 'muted'],
    ];

    public const LEAD_STATUS = [
        'new'       => ['Новый', 'blue'],
        'contacted' => ['На связи', 'amber'],
        'converted' => ['В клиентах', 'green'],
        'rejected'  => ['Отклонён', 'muted'],
    ];

    public static function get(array $map, ?string $key): array
    {
        return $map[$key] ?? ['—', 'muted'];
    }

    public static function badge(array $map, ?string $key): string
    {
        [$label, $color] = self::get($map, $key);
        return sprintf('<span class="badge badge--%s"><span class="dot"></span>%s</span>', $color, $label);
    }
}
