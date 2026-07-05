<?php

declare(strict_types=1);

/**
 * Инициализация схемы и наполнение демонстрационными данными.
 * Запуск: php database/seed.php
 */

use App\Core\Database;
use App\Core\Security;

require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Security.php';

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);
Database::boot($config['db']);
$db = Database::instance();

// Применяем схему
$schema = file_get_contents(__DIR__ . '/schema.sql');
foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
    if ($statement !== '' && !str_starts_with($statement, '--')) {
        $db->pdo()->exec($statement);
    }
}
echo "Схема применена.\n";

$fresh = (int) $db->value('SELECT COUNT(*) FROM users') === 0;
if (!$fresh) {
    echo "Данные уже существуют — сид пропущен.\n";
    return;
}

// Пользователи
$admin = $db->insert(
    'INSERT INTO users (login, name, email, phone, password_hash, role, avatar_color) VALUES (?,?,?,?,?,?,?)',
    ['admin', 'Артём Абрамов', 'admin@aa7.crm', '+7 900 000-00-07', Security::hashPassword('admin123'), 'admin', '#2f7d5b']
);
$manager = $db->insert(
    'INSERT INTO users (login, name, email, phone, password_hash, role, avatar_color) VALUES (?,?,?,?,?,?,?)',
    ['marina', 'Марина Котова', 'marina@aa7.crm', '+7 900 111-22-33', Security::hashPassword('manager123'), 'manager', '#b4652a']
);

// Клиенты — компании
$acme = $db->insert(
    'INSERT INTO clients (type, name, legal_name, email, phone, website, inn, country, city, industry, source, status, owner_id)
     VALUES ("company",?,?,?,?,?,?,?,?,?,?,?,?)',
    ['Nordic Systems', 'ООО «Нордик Системс»', 'hello@nordic.io', '+7 812 555-10-20', 'nordic.io', '7801234567', 'Россия', 'Санкт-Петербург', 'IT и разработка', '2gis', 'active', $admin]
);
$loft = $db->insert(
    'INSERT INTO clients (type, name, legal_name, email, phone, website, country, city, industry, source, status, owner_id)
     VALUES ("company",?,?,?,?,?,?,?,?,?,?,?)',
    ['Loft Coffee', 'ИП Данилов', 'team@loftcoffee.ru', '+7 495 200-30-40', 'loftcoffee.ru', 'Россия', 'Москва', 'HoReCa', 'yandex', 'vip', $admin]
);

// Клиенты — физлица, часть привязана к компаниям
$people = [
    ['Игорь Соколов', $acme, 'CTO', 'igor@nordic.io', '+7 921 100-10-10', 'Россия', 'Санкт-Петербург', 'active'],
    ['Елена Данилова', $loft, 'Управляющий', 'elena@loftcoffee.ru', '+7 916 300-40-50', 'Россия', 'Москва', 'vip'],
    ['Максим Верещагин', null, 'Основатель', 'max@vereshagin.dev', '+7 903 777-88-99', 'Казахстан', 'Алматы', 'lead'],
    ['Дарья Лунева', null, 'Маркетолог', 'daria@brandline.kz', '+7 705 555-66-77', 'Казахстан', 'Астана', 'active'],
];
foreach ($people as $p) {
    $db->insert(
        'INSERT INTO clients (type, company_id, name, position, email, phone, country, city, status, owner_id)
         VALUES ("person",?,?,?,?,?,?,?,?,?)',
        [$p[1], $p[0], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $admin]
    );
}

// Заказы
$statuses = ['new', 'negotiation', 'in_progress', 'review', 'done', 'done', 'cancelled'];
$titles = [
    'Корпоративный сайт под ключ',
    'Мобильное приложение доставки',
    'Интеграция CRM и телефонии',
    'Редизайн лендинга',
    'Highload API для маркетплейса',
    'Панель аналитики продаж',
    'Автоматизация складского учёта',
];
$clients = [$acme, $loft];
for ($i = 0; $i < 24; $i++) {
    $currency = $i % 3 === 0 ? 'USD' : 'RUB';
    $rate = $currency === 'USD' ? 92.5 : 1.0;
    $input = $currency === 'USD' ? rand(500, 8000) : rand(40000, 900000);
    $rub = round($input * $rate, 2);
    $status = $statuses[$i % count($statuses)];
    $paid = in_array($status, ['done'], true) ? $rub : ($status === 'in_progress' ? round($rub * 0.4, 2) : 0);
    $created = date('Y-m-d H:i:s', strtotime('-' . rand(1, 160) . ' days'));
    $orderId = $db->insert(
        'INSERT INTO orders (number, title, description, client_id, owner_id, status, priority, amount_input, currency, fx_rate, amount_rub, paid_rub, payment_status, progress, due_at, created_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            'AA7-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            $titles[$i % count($titles)],
            'Проект в рамках долгосрочного сотрудничества. Согласованы этапы, сроки и бюджет.',
            $clients[$i % count($clients)],
            $i % 2 === 0 ? $admin : $manager,
            $status,
            ['low', 'normal', 'high', 'urgent'][$i % 4],
            $input, $currency, $rate, $rub, $paid,
            $paid >= $rub && $rub > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            $status === 'done' ? 100 : rand(10, 85),
            date('Y-m-d', strtotime('+' . rand(3, 60) . ' days')),
            $created,
        ]
    );
    $db->insert(
        'INSERT INTO order_events (order_id, user_id, type, message, created_at) VALUES (?,?,?,?,?)',
        [$orderId, $admin, 'created', 'Заказ создан и добавлен в воронку.', $created]
    );
    if ($paid > 0) {
        $db->insert(
            'INSERT INTO payments (order_id, amount_input, currency, fx_rate, amount_rub, method, note, paid_at, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [$orderId, $paid, 'RUB', 1.0, $paid, 'Безнал', 'Оплата по договору', $created, $admin]
        );
    }
}

// Уведомления
$notifs = [
    ['success', 'check-circle', 'Заказ AA7-0005 завершён', 'Панель аналитики продаж передана клиенту.'],
    ['warning', 'clock', 'Приближается дедлайн', 'AA7-0002 нужно сдать в течение 3 дней.'],
    ['info', 'user-plus', 'Новый лид из 2ГИС', 'Найдено 42 организации по нише «кофейни».'],
];
foreach ($notifs as $n) {
    $db->insert(
        'INSERT INTO notifications (user_id, type, icon, title, body, link) VALUES (?,?,?,?,?,?)',
        [$admin, $n[0], $n[1], $n[2], $n[3], '/orders']
    );
}

echo "Демонстрационные данные добавлены.\n";
echo "Вход: admin / admin123\n";
