<?php

declare(strict_types=1);

namespace App\Services\Leads;

interface LeadProvider
{
    public function key(): string;

    public function label(): string;

    /**
     * @return array{items: array<int,array<string,mixed>>, meta: array<string,mixed>}
     */
    public function search(string $category, string $city, int $limit): array;
}
