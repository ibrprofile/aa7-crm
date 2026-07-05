<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Форматирование значений для отображения (деньги, даты, относительное время).
 */
final class Format
{
    public static function money(float|int|string $value, string $currency = 'RUB'): string
    {
        $symbols = ['RUB' => '₽', 'USD' => '$'];
        $symbol = $symbols[$currency] ?? '';
        $number = number_format((float) $value, ((float) $value == (int) $value) ? 0 : 2, ',', ' ');
        return $currency === 'USD' ? $symbol . $number : $number . ' ' . $symbol;
    }

    public static function compact(float|int $value): string
    {
        $abs = abs($value);
        if ($abs >= 1_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000, 1, ',', ''), '0'), ',') . 'M';
        }
        if ($abs >= 1_000) {
            return rtrim(rtrim(number_format($value / 1_000, 1, ',', ''), '0'), ',') . 'K';
        }
        return (string) (int) $value;
    }

    public static function date(?string $value, string $format = 'd.m.Y'): string
    {
        if (!$value) {
            return '—';
        }
        return date($format, strtotime($value));
    }

    public static function ago(?string $value): string
    {
        if (!$value) {
            return '—';
        }
        $diff = time() - strtotime($value);
        if ($diff < 60) return 'только что';
        if ($diff < 3600) return self::plural((int) ($diff / 60), 'минуту', 'минуты', 'минут') . ' назад';
        if ($diff < 86400) return self::plural((int) ($diff / 3600), 'час', 'часа', 'часов') . ' назад';
        if ($diff < 2592000) return self::plural((int) ($diff / 86400), 'день', 'дня', 'дней') . ' назад';
        return date('d.m.Y', strtotime($value));
    }

    public static function plural(int $n, string $one, string $few, string $many): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) return "$n $one";
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) return "$n $few";
        return "$n $many";
    }

    public static function fileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' МБ';
        if ($bytes >= 1_024)    return round($bytes / 1_024) . ' КБ';
        return $bytes . ' Б';
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);
        return mb_strtoupper($first . $second);
    }
}
