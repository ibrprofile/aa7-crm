<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Services\Http;

/**
 * Провайдер на базе OpenStreetMap / Overpass API.
 * Бесплатный, без ключей, реальные данные.
 */
final class OsmProvider implements LeadProvider
{
    use LeadSynthesizer;

    private const OVERPASS = 'https://overpass-api.de/api/interpreter';
    private const NOMINATIM = 'https://nominatim.openstreetmap.org/search';

    /**
     * Маппинг популярных русских ниш → OSM теги.
     * Формат: [ [key => value], ... ] — несколько тегов ищутся через union.
     */
    private const CATEGORY_MAP = [
        'ресторан'        => [['amenity' => 'restaurant']],
        'рестораны'       => [['amenity' => 'restaurant']],
        'кафе'            => [['amenity' => 'cafe']],
        'бар'             => [['amenity' => 'bar']],
        'бары'            => [['amenity' => 'bar']],
        'салон красоты'   => [['shop' => 'beauty'], ['amenity' => 'beauty_salon']],
        'салоны красоты'  => [['shop' => 'beauty'], ['amenity' => 'beauty_salon']],
        'парикмахерская'  => [['shop' => 'hairdresser']],
        'парикмахерские'  => [['shop' => 'hairdresser']],
        'стоматология'    => [['amenity' => 'dentist']],
        'стоматологии'    => [['amenity' => 'dentist']],
        'клиника'         => [['amenity' => 'clinic']],
        'клиники'         => [['amenity' => 'clinic']],
        'аптека'          => [['amenity' => 'pharmacy']],
        'аптеки'          => [['amenity' => 'pharmacy']],
        'фитнес'          => [['leisure' => 'fitness_centre'], ['leisure' => 'sports_centre']],
        'фитнес-клубы'    => [['leisure' => 'fitness_centre'], ['leisure' => 'sports_centre']],
        'спортзал'        => [['leisure' => 'fitness_centre']],
        'гостиница'       => [['tourism' => 'hotel']],
        'отель'           => [['tourism' => 'hotel']],
        'отели'           => [['tourism' => 'hotel']],
        'магазин'         => [['shop' => 'supermarket'], ['shop' => 'convenience']],
        'супермаркет'     => [['shop' => 'supermarket']],
        'супермаркеты'    => [['shop' => 'supermarket']],
        'автосервис'      => [['shop' => 'car_repair']],
        'автосервисы'     => [['shop' => 'car_repair']],
        'заправка'        => [['amenity' => 'fuel']],
        'заправки'        => [['amenity' => 'fuel']],
        'юридические услуги' => [['office' => 'lawyer']],
        'адвокат'         => [['office' => 'lawyer']],
        'нотариус'        => [['office' => 'notary']],
        'бухгалтерия'     => [['office' => 'accountant']],
        'строительство'   => [['office' => 'construction_company']],
        'недвижимость'    => [['office' => 'estate_agent']],
        'банк'            => [['amenity' => 'bank']],
        'банки'           => [['amenity' => 'bank']],
        'it'              => [['office' => 'it']],
        'it-компании'     => [['office' => 'it']],
        'школа'           => [['amenity' => 'school']],
        'школы'           => [['amenity' => 'school']],
        'детский сад'     => [['amenity' => 'kindergarten']],
        'университет'     => [['amenity' => 'university']],
        'гостиницы'       => [['tourism' => 'hotel']],
        'пекарня'         => [['shop' => 'bakery']],
        'пекарни'         => [['shop' => 'bakery']],
        'цветы'           => [['shop' => 'florist']],
        'ветеринарная клиника' => [['amenity' => 'veterinary']],
        'ветеринарные клиники' => [['amenity' => 'veterinary']],
    ];

    public function key(): string
    {
        return 'osm';
    }

    public function label(): string
    {
        return 'OpenStreetMap';
    }

    public function search(string $category, string $city, int $limit): array
    {
        $live = $this->fetchLive($category, $city, $limit);
        if ($live !== null) {
            return $live;
        }
        // fallback на синтетику если нет результатов
        return $this->synthesize($this->key(), $category, $city, $limit);
    }

    private function fetchLive(string $category, string $city, int $limit): ?array
    {
        // 1. Получаем area ID города через Nominatim
        $areaId = $this->resolveAreaId($city);
        if ($areaId === null) {
            return null;
        }

        // 2. Строим теги для запроса
        $tags = $this->resolveTags($category);

        // 3. Строим Overpass QL запрос
        $query = $this->buildQuery($areaId, $tags, $limit);

        $raw = Http::get(self::OVERPASS, [
            'User-Agent' => 'AA7CRM/1.0 (contact@aa7.crm)',
        ]);

        // Overpass требует POST
        $raw = $this->overpassPost($query);
        if ($raw === null) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['elements'])) {
            return null;
        }

        $items = [];
        foreach ($data['elements'] as $el) {
            $tags    = $el['tags'] ?? [];
            $name    = $tags['name'] ?? ($tags['brand'] ?? null);
            if (!$name) {
                continue;
            }

            $phone   = $tags['phone'] ?? ($tags['contact:phone'] ?? ($tags['contact:mobile'] ?? null));
            $website = $tags['website'] ?? ($tags['contact:website'] ?? ($tags['url'] ?? null));
            $addr    = $this->buildAddress($tags);

            // Instagram / WhatsApp из тегов
            $instagram = $tags['contact:instagram'] ?? ($tags['instagram'] ?? null);
            if ($instagram && !str_starts_with($instagram, '@')) {
                $instagram = '@' . ltrim(basename(rtrim($instagram, '/')), '@');
            }
            $whatsapp = $tags['contact:whatsapp'] ?? null;

            // VK / Telegram
            $vk       = $tags['contact:vk'] ?? ($tags['vk'] ?? null);
            $telegram = $tags['contact:telegram'] ?? ($tags['telegram'] ?? null);

            $categoryName = $this->detectCategory($tags, $category);

            $items[] = [
                'external_id' => 'osm_' . ($el['id'] ?? md5($name . $addr)),
                'name'        => $name,
                'category'    => $categoryName,
                'city'        => $city,
                'phone'       => $phone ? preg_replace('/[^\d+\s\-()]/', '', $phone) : null,
                'website'     => $website,
                'instagram'   => $instagram,
                'whatsapp'    => $whatsapp,
                'telegram'    => $telegram,
                'vk'          => $vk,
                'address'     => $addr,
                'rating'      => 0.0,
                'reviews'     => 0,
                'stats'       => $this->insights(4.0, 0),
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        if (empty($items)) {
            return null;
        }

        return [
            'items' => $items,
            'meta'  => [
                'mode'   => 'live',
                'source' => 'osm',
                'count'  => count($items),
            ],
        ];
    }

    private function resolveAreaId(string $city): ?int
    {
        $data = Http::getJson(self::NOMINATIM, [
            'q'              => $city,
            'format'         => 'json',
            'limit'          => 1,
            'addressdetails' => 0,
            'featuretype'    => 'city',
        ], ['User-Agent' => 'AA7CRM/1.0 (contact@aa7.crm)']);

        if (!$data || empty($data[0]['osm_id'])) {
            return null;
        }

        $osmId   = (int) $data[0]['osm_id'];
        $osmType = $data[0]['osm_type'] ?? 'relation';

        // Overpass area ID = osm relation ID + 3_600_000_000
        if ($osmType === 'relation') {
            return 3_600_000_000 + $osmId;
        }
        if ($osmType === 'way') {
            return 2_400_000_000 + $osmId;
        }
        return null;
    }

    private function resolveTags(string $category): array
    {
        $key = mb_strtolower(trim($category));
        if (isset(self::CATEGORY_MAP[$key])) {
            return self::CATEGORY_MAP[$key];
        }
        // Частичное совпадение
        foreach (self::CATEGORY_MAP as $mapKey => $mapTags) {
            if (str_contains($key, $mapKey) || str_contains($mapKey, $key)) {
                return $mapTags;
            }
        }
        // Fallback — искать по name
        return [['name' => $category]];
    }

    private function buildQuery(int $areaId, array $tagGroups, int $limit): string
    {
        $parts = [];
        foreach ($tagGroups as $tagPair) {
            foreach ($tagPair as $k => $v) {
                $filter = "[\"$k\"=\"$v\"]";
                $parts[] = "node{$filter}(area.a);";
                $parts[] = "way{$filter}(area.a);";
            }
        }
        $union = implode("\n  ", $parts);

        return "[out:json][timeout:15];\n"
            . "area({$areaId})->.a;\n"
            . "(\n  {$union}\n);\n"
            . "out tags " . ($limit * 3) . ";";
    }

    private function overpassPost(string $query): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init(self::OVERPASS);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'data=' . urlencode($query),
            CURLOPT_HTTPHEADER     => ['User-Agent: AA7CRM/1.0 (contact@aa7.crm)'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($res === false || $code >= 400) ? null : (string) $res;
    }

    private function buildAddress(array $tags): ?string
    {
        $street  = $tags['addr:street']   ?? null;
        $house   = $tags['addr:housenumber'] ?? null;
        $city    = $tags['addr:city']     ?? null;

        if ($street && $house) {
            return $street . ', ' . $house . ($city ? ', ' . $city : '');
        }
        if ($street) {
            return $street . ($city ? ', ' . $city : '');
        }
        return $tags['address'] ?? null;
    }

    private function detectCategory(array $tags, string $fallback): string
    {
        $map = [
            'amenity'  => [
                'restaurant' => 'Ресторан', 'cafe' => 'Кафе', 'bar' => 'Бар',
                'dentist' => 'Стоматология', 'clinic' => 'Клиника',
                'pharmacy' => 'Аптека', 'bank' => 'Банк', 'fuel' => 'АЗС',
                'school' => 'Школа', 'kindergarten' => 'Детский сад',
                'university' => 'Университет', 'veterinary' => 'Ветклиника',
            ],
            'shop'     => [
                'beauty' => 'Салон красоты', 'hairdresser' => 'Парикмахерская',
                'supermarket' => 'Супермаркет', 'convenience' => 'Магазин',
                'bakery' => 'Пекарня', 'florist' => 'Цветы',
                'car_repair' => 'Автосервис',
            ],
            'leisure'  => [
                'fitness_centre' => 'Фитнес-клуб', 'sports_centre' => 'Спортивный центр',
            ],
            'tourism'  => ['hotel' => 'Отель'],
            'office'   => [
                'lawyer' => 'Юридические услуги', 'notary' => 'Нотариус',
                'accountant' => 'Бухгалтерия', 'it' => 'IT-компания',
                'estate_agent' => 'Недвижимость', 'construction_company' => 'Строительство',
            ],
        ];

        foreach ($map as $key => $values) {
            $val = $tags[$key] ?? null;
            if ($val && isset($values[$val])) {
                return $values[$val];
            }
        }

        return mb_convert_case(trim($fallback), MB_CASE_TITLE, 'UTF-8');
    }
}
