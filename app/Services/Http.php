<?php

declare(strict_types=1);

namespace App\Services;

final class Http
{
    public static function getJson(string $url, array $query = [], array $headers = []): ?array
    {
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        $raw = self::get($url, $headers);
        if ($raw === null) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function get(string $url, array $headers = []): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $curlHeaders = [];
            foreach ($headers as $k => $v) {
                $curlHeaders[] = $k . ': ' . $v;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => $curlHeaders,
                CURLOPT_USERAGENT => 'AA7CRM/1.0 (+https://aa7.crm)',
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($res === false || $code >= 400) ? null : (string) $res;
        }

        $opts = ['http' => ['timeout' => 8, 'header' => '']];
        foreach ($headers as $k => $v) {
            $opts['http']['header'] .= "$k: $v\r\n";
        }
        $res = @file_get_contents($url, false, stream_context_create($opts));
        return $res === false ? null : (string) $res;
    }
}
