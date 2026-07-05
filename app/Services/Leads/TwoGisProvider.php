<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Services\Http;

final class TwoGisProvider implements LeadProvider
{
    use LeadSynthesizer;

    private const CATALOG = 'https://catalog.api.2gis.com/3.0/items';

    public function __construct(private ?string $apiKey = null)
    {
    }

    public function key(): string
    {
        return '2gis';
    }

    public function label(): string
    {
        return '2ГИС';
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
        $data = Http::getJson(self::CATALOG, [
            'q'      => trim($category . ' ' . $city),
            'fields' => 'items.point,items.contact_groups,items.rubrics,items.reviews,items.external_content',
            'page_size' => min($limit, 50),
            'key'    => $this->apiKey,
        ]);

        if (!$data || empty($data['result']['items'])) {
            return null;
        }

        $items = [];
        foreach ($data['result']['items'] as $row) {
            $contacts = $this->contacts($row['contact_groups'] ?? []);
            $reviews = (int) ($row['reviews']['count'] ?? 0);
            $rating = (float) ($row['reviews']['rating'] ?? 0);
            $items[] = [
                'external_id' => '2gis_' . ($row['id'] ?? md5(json_encode($row))),
                'name'        => $row['name'] ?? 'Без названия',
                'category'    => $row['rubrics'][0]['name'] ?? $category,
                'city'        => $city,
                'phone'       => $contacts['phone'],
                'website'     => $contacts['website'],
                'instagram'   => $contacts['instagram'],
                'whatsapp'    => $contacts['whatsapp'],
                'address'     => $row['address_name'] ?? null,
                'rating'      => $rating,
                'reviews'     => $reviews,
                'stats'       => $this->insights($rating ?: 4.0, $reviews),
            ];
        }

        return [
            'items' => $items,
            'meta'  => ['mode' => 'live', 'source' => '2gis', 'count' => count($items)],
        ];
    }

    private function contacts(array $groups): array
    {
        $out = ['phone' => null, 'website' => null, 'instagram' => null, 'whatsapp' => null];
        foreach ($groups as $group) {
            foreach ($group['contacts'] ?? [] as $contact) {
                $type = $contact['type'] ?? '';
                $value = $contact['value'] ?? ($contact['text'] ?? null);
                if ($type === 'phone' && !$out['phone']) {
                    $out['phone'] = $value;
                } elseif ($type === 'website' && !$out['website']) {
                    $out['website'] = $value;
                } elseif ($type === 'instagram' && !$out['instagram']) {
                    $out['instagram'] = $value;
                } elseif ($type === 'whatsapp' && !$out['whatsapp']) {
                    $out['whatsapp'] = $value;
                }
            }
        }
        return $out;
    }
}
