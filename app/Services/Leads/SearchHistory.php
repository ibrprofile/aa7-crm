<?php

declare(strict_types=1);

namespace App\Services\Leads;

/**
 * Хранит историю поисков в JSON-файлах в каталоге searches/.
 * Каждый поиск — отдельный файл: searches/YYYY-MM-DD_HH-MM-SS_{hash}.json
 * Также ведёт реестр всех показанных external_id чтобы не дублировать.
 */
final class SearchHistory
{
    private string $dir;
    private string $seenFile;

    public function __construct(string $baseDir)
    {
        $this->dir      = rtrim($baseDir, '/') . '/searches';
        $this->seenFile = $this->dir . '/_seen.json';

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    /**
     * Возвращает все external_id которые уже показывались.
     * @return array<string, true>
     */
    public function seenIds(): array
    {
        if (!is_file($this->seenFile)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->seenFile), true);
        return is_array($data) ? array_fill_keys($data, true) : [];
    }

    /**
     * Фильтрует items — убирает уже виденные, потом сохраняет результаты и
     * обновляет реестр seen.
     *
     * @param  array  $items       массив лидов из провайдера
     * @param  array  $meta        мета поиска (source, category, city, ...)
     * @param  bool   $noWebsite   если true — оставить только тех, у кого нет сайта
     * @return array               отфильтрованные items
     */
    public function filterAndSave(array $items, array $meta, bool $noWebsite = false): array
    {
        $seen = $this->seenIds();

        // 1. Убираем уже виденных
        $fresh = array_values(array_filter($items, fn($item) => !isset($seen[$item['external_id']])));

        // 2. Фильтр: только без сайта И с хотя бы одним контактом
        if ($noWebsite) {
            $fresh = array_values(array_filter($fresh, function ($item) {
                $noSite    = empty($item['website']);
                $hasContact = !empty($item['phone'])
                           || !empty($item['instagram'])
                           || !empty($item['whatsapp'])
                           || !empty($item['telegram'])
                           || !empty($item['vk']);
                return $noSite && $hasContact;
            }));
        }

        if (empty($fresh)) {
            return [];
        }

        // 3. Сохраняем в файл
        $this->saveFile($fresh, $meta);

        // 4. Обновляем реестр seen
        $newIds = array_column($fresh, 'external_id');
        $allIds = array_keys($seen);
        $merged = array_unique(array_merge($allIds, $newIds));
        file_put_contents($this->seenFile, json_encode(array_values($merged), JSON_UNESCAPED_UNICODE));

        return $fresh;
    }

    /**
     * Список всех сохранённых поисков, отсортированный по дате убыванию.
     * @return array[]
     */
    public function list(): array
    {
        $files = glob($this->dir . '/search_*.json') ?: [];
        rsort($files);

        $result = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $result[] = [
                    'file'     => basename($file),
                    'searched_at' => $data['searched_at'] ?? '',
                    'source'   => $data['source']   ?? '',
                    'category' => $data['category'] ?? '',
                    'city'     => $data['city']      ?? '',
                    'count'    => count($data['items'] ?? []),
                    'no_website_only' => $data['no_website_only'] ?? false,
                ];
            }
        }

        return $result;
    }

    /**
     * Загружает полный файл поиска по имени файла.
     */
    public function load(string $filename): ?array
    {
        $path = $this->dir . '/' . basename($filename);
        if (!is_file($path) || !str_starts_with(basename($filename), 'search_')) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    /**
     * Сбрасывает реестр seen (чтобы можно было начать заново).
     */
    public function resetSeen(): void
    {
        if (is_file($this->seenFile)) {
            unlink($this->seenFile);
        }
    }

    private function saveFile(array $items, array $meta): void
    {
        $ts       = date('Y-m-d_H-i-s');
        $hash     = substr(md5($meta['source'] . $meta['category'] . $meta['city'] . $ts), 0, 8);
        $filename = $this->dir . "/search_{$ts}_{$hash}.json";

        $payload = array_merge($meta, [
            'searched_at' => date('Y-m-d H:i:s'),
            'items'       => $items,
        ]);

        file_put_contents($filename, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
