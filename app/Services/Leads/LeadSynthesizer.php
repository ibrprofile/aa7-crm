<?php

declare(strict_types=1);

namespace App\Services\Leads;

/**
 * Детерминированная генерация правдоподобной выдачи, когда ключ внешнего API
 * не задан. Посев зависит от источника, ниши и города, поэтому 2ГИС и Яндекс
 * дают разные, но стабильные наборы — они не пересекаются между собой.
 */
trait LeadSynthesizer
{
    private array $streets = [
        'ул. Абая', 'пр. Достык', 'ул. Пушкина', 'пр. Ленина', 'ул. Гоголя',
        'ул. Кабанбай батыра', 'пр. Назарбаева', 'ул. Толе би', 'ул. Сатпаева',
        'ул. Тимирязева', 'мкр. Самал', 'пр. Аль-Фараби', 'ул. Жандосова',
    ];

    private array $brandParts = [
        'Prime', 'Nova', 'Alma', 'Group', 'Studio', 'Lab', 'House', 'Center',
        'Master', 'City', 'Line', 'Format', 'Standart', 'Expert', 'Partner',
    ];

    protected function synthesize(string $source, string $category, string $city, int $limit): array
    {
        $seed = crc32($source . '|' . mb_strtolower($category) . '|' . mb_strtolower($city));
        mt_srand($seed);

        $count = max(6, min($limit, 24));
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $brand = $this->brandParts[mt_rand(0, count($this->brandParts) - 1)]
                . ' ' . $this->brandParts[mt_rand(0, count($this->brandParts) - 1)];
            $name = $this->titleCase($category) . ' «' . $brand . '»';

            $hasPhone = mt_rand(0, 100) > 8;
            $hasSite = mt_rand(0, 100) > 45;
            $hasInsta = mt_rand(0, 100) > 40;
            $hasWa = mt_rand(0, 100) > 55;

            $rating = round(mt_rand(35, 50) / 10, 1);
            $reviews = mt_rand(3, 640);

            $items[] = [
                'external_id' => $source . '_' . substr(md5($seed . $i), 0, 12),
                'name'        => $name,
                'category'    => $this->titleCase($category),
                'city'        => $city,
                'phone'       => $hasPhone ? $this->phone() : null,
                'website'     => $hasSite ? $this->slug($brand) . '.kz' : null,
                'instagram'   => $hasInsta ? '@' . $this->slug($brand) : null,
                'whatsapp'    => $hasWa ? $this->phone() : null,
                'address'     => $this->streets[mt_rand(0, count($this->streets) - 1)] . ', ' . mt_rand(1, 240),
                'rating'      => $rating,
                'reviews'     => $reviews,
                'stats'       => $this->insights($rating, $reviews),
            ];
        }

        mt_srand();

        return [
            'items' => $items,
            'meta'  => [
                'mode'     => 'offline',
                'source'   => $source,
                'category' => $category,
                'city'     => $city,
                'count'    => count($items),
            ],
        ];
    }

    protected function insights(float $rating, int $reviews): array
    {
        $activity = min(100, (int) round($reviews / 6 + ($rating - 3) * 18));
        return [
            'contactability' => min(100, 40 + $reviews % 55),
            'activity'       => max(8, $activity),
            'demand'         => min(100, 30 + ($reviews * 7) % 65),
            'segment'        => $rating >= 4.5 ? 'Лидер ниши' : ($rating >= 4.0 ? 'Устойчивый' : 'Растущий'),
        ];
    }

    private function phone(): string
    {
        return sprintf('+7 (%03d) %03d-%02d-%02d', mt_rand(700, 778), mt_rand(100, 999), mt_rand(10, 99), mt_rand(10, 99));
    }

    private function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? '';
        return $value !== '' ? $value : 'company' . mt_rand(10, 99);
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }
}
