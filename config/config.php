<?php

declare(strict_types=1);

/**
 * Централизованная конфигурация AA7 CRM.
 * Значения читаются из окружения с безопасными значениями по умолчанию
 * для локальной среды. Секреты в репозитории не хранятся.
 */

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

return [
    'app' => [
        'name'     => 'AA7 CRM',
        'env'      => env('APP_ENV', 'production'),
        'debug'    => env('APP_DEBUG', '0') === '1',
        'url'      => env('APP_URL', ''),
        'timezone' => 'Europe/Moscow',
        'key'      => env('APP_KEY', 'aa7-local-development-key-change-me'),
    ],

    'db' => [
        'host'    => env('DB_HOST', '127.0.0.1'),
        'port'    => (int) env('DB_PORT', '3306'),
        'name'    => env('DB_NAME', 'u3545730_aa7_crm'),
        'user'    => env('DB_USER', 'u3545730_aa7_crm'),
        'pass'    => env('DB_PASS', 'aa7_crm123'),
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name'     => 'aa7_session',
        'lifetime' => 60 * 60 * 8,
        'idle'     => 60 * 30,
    ],

    'security' => [
        // Порог блокировки при переборе пароля
        'login_max_attempts' => 5,
        'login_lockout'      => 60 * 10,
        // Ограничение частоты для API поиска лидов
        'search_rate_limit'  => 30,
        'search_rate_window' => 60,
    ],

    'currency' => [
        // Официальный дневной курс ЦБ РФ, без ключа
        'rates_url' => 'https://www.cbr-xml-daily.ru/daily_json.js',
        'base'      => 'RUB',
        'ttl'       => 60 * 60 * 3,
    ],

    'leads' => [
        // Ключи опциональны: при отсутствии используется парсинг публичной выдачи
        '2gis' => [
            'key'      => env('GIS_2GIS_KEY', ''),
            'base_url' => 'https://catalog.api.2gis.com/3.0',
        ],
        'yandex' => [
            'key'      => 'adc4370f-367d-4875-be4b-0275ff811c26',
            'base_url' => 'https://search-maps.yandex.ru/v1',
        ],
    ],
];
