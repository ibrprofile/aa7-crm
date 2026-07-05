<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Services\Http;

final class YandexProvider implements LeadProvider
{
    use LeadSynthesizer;

    private const SEARCH = 'https://search-maps.yandex.ru/v1/';

    public function __construct(private ?string $apiKey = null)
    {
    }

    public function key(): string
    {
        return 'yandex';
    }

    public function label(): string
    {
        return 'Яндекс Карты';
    }

    public function search(string $category, string $city, int $limit): array
    {
        if ($this->apiKey) {
            $live = $this->fetchLive($category, $city, $limit);
            if ($live !== null) {
                return $live;
            }
        }
        return $this->synthesize($this->key(), $category, $city, $limit);
    }

    private function fetchLive(string $category, string $city, int $limit): ?array
    {
        $data = Http::getJson(self::SEARCH, [
            'text'    => trim($category . ' ' . $city),
            'type'    => 'biz',
            'lang'    => 'ru_RU',
            'results' => min($limit, 50),
            'apikey'  => $this->apiKey,
        ]);

        if (!$data || empty($data['features'])) {
            return null;
        }

        $items = [];
        foreach ($data['features'] as $feature) {
            $meta = $feature['properties']['CompanyMetaData'] ?? [];
            $phones = $meta['Phones'][0]['formatted'] ?? null;
            $site = $meta['url'] ?? null;
            $items[] = [
                'external_id' => 'yandex_' . ($meta['id'] ?? md5(json_encode($feature))),
                'name'        => $meta['name'] ?? 'Без названия',
                'category'    => $meta['Categories'][0]['name'] ?? $category,
                'city'        => $city,
                'phone'       => $phones,
                'website'     => $site,
                'instagram'   => null,
                'whatsapp'    => null,
                'address'     => $meta['address'] ?? null,
                'rating'      => 0.0,
                'reviews'     => 0,
                'stats'       => $this->insights(4.0, 0),
            ];
        }

        return [
            'items' => $items,
            'meta'  => ['mode' => 'live', 'source' => 'yandex', 'count' => count($items)],
        ];
    }
}
