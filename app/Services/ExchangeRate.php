<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Курсы валют ЦБ РФ (cbr-xml-daily.ru). Значения кэшируются в БД на сутки,
 * чтобы не дёргать внешний источник на каждый ввод суммы и работать даже
 * при недоступности сети.
 */
final class ExchangeRate
{
    private const SOURCE = 'https://www.cbr-xml-daily.ru/daily_json.js';
    private const FALLBACK = ['USD' => 92.5, 'EUR' => 100.2, 'KZT' => 0.195, 'RUB' => 1.0];

    public static function rate(string $currency): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'RUB') {
            return 1.0;
        }

        $cached = self::fromCache($currency);
        if ($cached !== null) {
            return $cached;
        }

        $fetched = self::fetch();
        if ($fetched !== []) {
            foreach ($fetched as $code => $value) {
                self::store($code, $value);
            }
            if (isset($fetched[$currency])) {
                return $fetched[$currency];
            }
        }

        return self::FALLBACK[$currency] ?? 1.0;
    }

    public static function convertToRub(float $amount, string $currency): array
    {
        $rate = self::rate($currency);
        return [
            'rate' => $rate,
            'rub'  => round($amount * $rate, 2),
        ];
    }

    public static function all(): array
    {
        $rows = Database::instance()->all('SELECT currency, rate FROM fx_rates');
        $map = [];
        foreach ($rows as $row) {
            $map[$row['currency']] = (float) $row['rate'];
        }
        if (!isset($map['USD'])) {
            self::rate('USD');
            return self::all();
        }
        return $map;
    }

    private static function fromCache(string $currency): ?float
    {
        $row = Database::instance()->first(
            'SELECT rate FROM fx_rates WHERE currency = ? AND fetched_at > DATE_SUB(NOW(), INTERVAL 12 HOUR)',
            [$currency]
        );
        return $row ? (float) $row['rate'] : null;
    }

    private static function store(string $currency, float $rate): void
    {
        Database::instance()->run(
            'INSERT INTO fx_rates (currency, rate, fetched_at) VALUES (?,?,NOW())
             ON DUPLICATE KEY UPDATE rate = VALUES(rate), fetched_at = NOW()',
            [$currency, $rate]
        );
    }

    private static function fetch(): array
    {
        $raw = self::httpGet(self::SOURCE);
        if ($raw === null) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['Valute'])) {
            return [];
        }

        $out = ['RUB' => 1.0];
        foreach (['USD', 'EUR', 'KZT'] as $code) {
            if (isset($data['Valute'][$code]['Value'], $data['Valute'][$code]['Nominal'])) {
                $v = (float) $data['Valute'][$code]['Value'];
                $nominal = (float) $data['Valute'][$code]['Nominal'] ?: 1.0;
                $out[$code] = round($v / $nominal, 4);
            }
        }
        return $out;
    }

    private static function httpGet(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $res = @file_get_contents($url, false, $ctx);
            return $res === false ? null : $res;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'AA7CRM/1.0',
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($res === false || $code >= 400) ? null : (string) $res;
    }
}
