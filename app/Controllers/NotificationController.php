<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Notification;

final class NotificationController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $userId = (int) Auth::id();
        Notification::markAllRead($userId);
        $items = Notification::forUser($userId, 60);

        $this->render('notifications/index', [
            'title' => 'Уведомления',
            'items' => $items,
        ]);
    }

    public function readAll(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        Notification::markAllRead((int) Auth::id());
        $this->json(['ok' => true]);
    }

    public function read(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        Notification::markRead($id, (int) Auth::id());
        $this->json(['ok' => true]);
    }
}
