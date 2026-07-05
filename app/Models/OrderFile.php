<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class OrderFile
{
    private const UPLOAD_DIR = 'uploads/';
    private const MAX_SIZE   = 30 * 1024 * 1024; // 30 MB
    private const ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/zip', 'application/x-zip-compressed',
        'application/x-rar-compressed', 'application/x-7z-compressed',
        'text/plain', 'text/html', 'text/css', 'text/javascript',
        'application/json', 'application/xml',
        'application/octet-stream',
        'video/mp4', 'video/webm',
        'audio/mpeg', 'audio/ogg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
    ];

    public static function forOrder(int $orderId): array
    {
        return Database::instance()->all(
            'SELECT f.*, u.name AS uploader_name
             FROM order_files f LEFT JOIN users u ON u.id = f.uploaded_by
             WHERE f.order_id = ? ORDER BY f.created_at DESC',
            [$orderId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::instance()->first(
            'SELECT * FROM order_files WHERE id = ?', [$id]
        );
    }

    /**
     * Сохраняет файл из $_FILES[key] и создаёт запись в БД.
     * Возвращает массив с данными файла или бросает RuntimeException.
     */
    public static function upload(
        array $fileEntry,
        int $orderId,
        ?int $clientId,
        ?int $messageId,
        ?int $uploadedBy,
        bool $fromClient
    ): array {
        if ($fileEntry['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Ошибка загрузки файла.');
        }
        if ($fileEntry['size'] > self::MAX_SIZE) {
            throw new \RuntimeException('Файл слишком большой (максимум 30 МБ).');
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $fileEntry['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException("Тип файла не разрешён: {$mime}");
        }

        $ext        = pathinfo($fileEntry['name'], PATHINFO_EXTENSION);
        $safeName   = bin2hex(random_bytes(16)) . ($ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '');
        $uploadRoot = dirname(__DIR__, 2) . '/' . self::UPLOAD_DIR;

        if (!is_dir($uploadRoot)) {
            mkdir($uploadRoot, 0750, true);
        }

        if (!move_uploaded_file($fileEntry['tmp_name'], $uploadRoot . $safeName)) {
            throw new \RuntimeException('Не удалось сохранить файл.');
        }

        $id = Database::instance()->insert(
            'INSERT INTO order_files
                (order_id, client_id, message_id, uploaded_by, from_client, original_name, stored_name, mime_type, size_bytes)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [
                $orderId, $clientId, $messageId, $uploadedBy, (int)$fromClient,
                mb_substr($fileEntry['name'], 0, 255),
                $safeName,
                $mime,
                $fileEntry['size'],
            ]
        );

        return ['id' => $id, 'original_name' => $fileEntry['name'], 'stored_name' => $safeName, 'mime_type' => $mime];
    }

    public static function publicPath(string $storedName): string
    {
        return '/' . self::UPLOAD_DIR . $storedName;
    }

    public static function fullPath(string $storedName): string
    {
        return dirname(__DIR__, 2) . '/' . self::UPLOAD_DIR . $storedName;
    }
}
