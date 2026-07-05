<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\Payment;
use App\Support\Format;
use App\Support\Labels;

/**
 * JSON API для слайд-панели заказа.
 * Все роуты защищены AuthMiddleware, данные отдаются только аутентифицированным менеджерам.
 */
final class ApiOrderController extends Controller
{
    /** GET /api/orders/{id}/panel */
    public function panel(Request $request, array $params = []): void
    {
        $order = $this->findOrFail((int)($params['id'] ?? 0));

        $events   = Order::events((int)$order['id']);
        $payments = Payment::forOrder((int)$order['id']);

        $eventsData = array_slice(array_map(fn($e) => [
            'type'         => $e['type'],
            'user_name'    => $e['user_name'] ?? 'Система',
            'avatar_color' => $e['avatar_color'] ?? '#6366f1',
            'message'      => $e['message'],
            'created_at'   => $e['created_at'],
            'ago'          => Format::ago($e['created_at']),
        ], $events), 0, 40);

        $paymentsData = array_map(fn($p) => [
            'amount_rub'  => $p['amount_rub'],
            'method'      => $p['method'] ?? '',
            'note'        => $p['note'] ?? '',
            'paid_at_fmt' => Format::date($p['paid_at'], 'd.m.Y'),
        ], $payments);

        $this->json([
            'id'             => $order['id'],
            'number'         => $order['number'],
            'title'          => $order['title'],
            'description'    => $order['description'] ?? '',
            'status'         => $order['status'],
            'priority'       => $order['priority'],
            'amount_rub'     => $order['amount_rub'],
            'paid_rub'       => $order['paid_rub'],
            'progress'       => $order['progress'],
            'due_at'         => $order['due_at'] ? Format::date($order['due_at']) : null,
            'client_id'      => $order['client_id'],
            'client_name'    => $order['client_name'] ?? null,
            'events'         => $eventsData,
            'payments'       => $paymentsData,
        ]);
    }

    /** POST /api/orders/{id}/status */
    public function status(Request $request, array $params = []): void
    {
        $this->verifyCsrfJson($request);
        $order = $this->findOrFail((int)($params['id'] ?? 0));
        $id    = (int)$order['id'];

        $allowed = ['new', 'negotiation', 'in_progress', 'review', 'done', 'cancelled'];
        $status  = $this->jsonBody()['status'] ?? $request->input('status', '');

        if (!in_array($status, $allowed, true)) {
            $this->json(['error' => 'Неверный статус.'], 422);
        }

        Order::changeStatus($id, $status);
        Order::addEvent($id, Auth::id(), 'status', "Статус изменён на «{$status}»");
        ActivityLog::record('order_status', 'order', $id, "API: статус {$status}");

        $this->json(['ok' => true, 'status' => $status]);
    }

    /** POST /api/orders/{id}/comment */
    public function comment(Request $request, array $params = []): void
    {
        $this->verifyCsrfJson($request);
        $id   = (int)($params['id'] ?? 0);
        $body = $this->jsonBody();
        $text = mb_substr(trim($body['message'] ?? $request->input('message', '')), 0, 400);

        if ($text === '') {
            $this->json(['error' => 'Пустое сообщение.'], 422);
        }

        Order::addEvent($id, Auth::id(), 'comment', $text);

        $user = Auth::user();
        $this->json([
            'type'         => 'comment',
            'user_name'    => $user['name'] ?? 'Вы',
            'avatar_color' => $user['avatar_color'] ?? '#6366f1',
            'message'      => $text,
            'created_at'   => date('Y-m-d H:i:s'),
            'ago'          => 'только что',
        ]);
    }

    /** POST /api/orders/{id}/files — загрузка файлов из CRM */
    public function uploadFile(Request $request, array $params = []): void
    {
        $order = $this->findOrFail((int)($params['id'] ?? 0));
        $id    = (int)$order['id'];

        if (empty($_FILES['file'])) {
            $this->json(['error' => 'Файл не найден.'], 400);
        }

        try {
            $fileData = OrderFile::upload(
                $_FILES['file'],
                $id,
                $order['client_id'] ? (int)$order['client_id'] : null,
                null,
                Auth::id(),
                false
            );
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 422);
        }

        Order::addEvent($id, Auth::id(), 'file', "Загружен файл: {$fileData['original_name']}");
        ActivityLog::record('file_upload', 'order', $id, "Файл: {$fileData['original_name']}");

        $this->json(['ok' => true, 'file' => $fileData]);
    }

    /** GET /api/orders/{id}/files */
    public function listFiles(Request $request, array $params = []): void
    {
        $order = $this->findOrFail((int)($params['id'] ?? 0));
        $files = OrderFile::forOrder((int)$order['id']);

        $out = array_map(fn($f) => [
            'id'            => $f['id'],
            'original_name' => $f['original_name'],
            'mime_type'     => $f['mime_type'],
            'size_bytes'    => $f['size_bytes'],
            'from_client'   => (bool)$f['from_client'],
            'uploader_name' => $f['uploader_name'] ?? null,
            'url'           => OrderFile::publicPath($f['stored_name']),
            'created_at'    => $f['created_at'],
        ], $files);

        $this->json($out);
    }

    /** GET /api/orders/{id}/messages — чат CRM */
    public function messages(Request $request, array $params = []): void
    {
        $order    = $this->findOrFail((int)($params['id'] ?? 0));
        $raw      = OrderMessage::forOrder((int)$order['id']);
        $this->json($this->formatMessages($raw));
    }

    /** POST /api/orders/{id}/messages */
    public function sendMessage(Request $request, array $params = []): void
    {
        $this->verifyCsrfJson($request);
        $order = $this->findOrFail((int)($params['id'] ?? 0));
        $id    = (int)$order['id'];
        $body  = $this->jsonBody();
        $text  = mb_substr(trim($body['body'] ?? $request->input('body', '')), 0, 4000);

        if ($text === '' && empty($_FILES['file'])) {
            $this->json(['error' => 'Пустое сообщение.'], 422);
        }

        $clientId = $order['client_id'] ? (int)$order['client_id'] : null;
        if (!$clientId) {
            $this->json(['error' => 'К заказу не привязан клиент.'], 422);
        }

        $msgId = OrderMessage::create([
            'order_id'    => $id,
            'client_id'   => $clientId,
            'from_client' => 0,
            'sender_id'   => Auth::id(),
            'body'        => $text,
        ]);

        if (!empty($_FILES['file'])) {
            OrderFile::upload($_FILES['file'], $id, $clientId, $msgId, Auth::id(), false);
        }

        $user = Auth::user();
        $this->json([
            'id'           => $msgId,
            'from_client'  => false,
            'sender_name'  => $user['name'] ?? '',
            'sender_color' => $user['avatar_color'] ?? '#6366f1',
            'body'         => $text,
            'files'        => [],
            'created_at'   => date('H:i'),
        ]);
    }

    /* ── Helpers ──────────────────────────── */

    private function findOrFail(int $id): array
    {
        $order = Order::find($id);
        if (!$order) {
            $this->json(['error' => 'Заказ не найден.'], 404);
        }
        return $order;
    }

    private function verifyCsrfJson(Request $request): void
    {
        $body  = $this->jsonBody();
        $token = $body['_csrf'] ?? $request->input('_csrf', '');
        if (!Security::verifyCsrf($token)) {
            $this->json(['error' => 'Недействительный токен.'], 419);
        }
    }

    private function jsonBody(): array
    {
        static $cache = null;
        if ($cache === null) {
            $raw   = file_get_contents('php://input');
            $cache = $raw ? (json_decode($raw, true) ?? []) : [];
        }
        return $cache;
    }

    private function formatMessages(array $rows): array
    {
        return array_map(fn($m) => [
            'id'           => $m['id'],
            'from_client'  => (bool)$m['from_client'],
            'sender_name'  => $m['sender_name'] ?? null,
            'sender_color' => $m['sender_color'] ?? '#6366f1',
            'body'         => $m['body'],
            'files'        => OrderMessage::parseFiles($m['files_raw'] ?? ''),
            'created_at'   => date('H:i', strtotime($m['created_at'])),
        ], $rows);
    }
}
