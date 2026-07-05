<?php

declare(strict_types=1);

namespace App\Services\Leads;

final class LeadFinder
{
    /** @var array<string,LeadProvider> */
    private array $providers = [];

    public function __construct()
    {
        $cfg = $GLOBALS['config'] ?? [];

        $gisKey    = ($cfg['leads']['2gis']['key']   ?? '') ?: (getenv('TWOGIS_API_KEY')      ?: null);
        $yandexKey = ($cfg['leads']['yandex']['key'] ?? '') ?: (getenv('YANDEX_MAPS_API_KEY') ?: null);

        $this->register(new OsmProvider());
        $this->register(new TwoGisProvider($gisKey    ?: null));
        $this->register(new YandexProvider($yandexKey ?: null));
    }

    public function register(LeadProvider $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    public function provider(string $key): ?LeadProvider
    {
        return $this->providers[$key] ?? null;
    }

    /** @return array<string,string> */
    public function options(): array
    {
        $out = [];
        foreach ($this->providers as $key => $provider) {
            $out[$key] = $provider->label();
        }
        return $out;
    }

    public function search(string $source, string $category, string $city, int $limit = 20): array
    {
        $provider = $this->provider($source);
        if (!$provider) {
            return ['items' => [], 'meta' => ['error' => 'unknown_source']];
        }
        return $provider->search($category, $city, $limit);
    }
}
