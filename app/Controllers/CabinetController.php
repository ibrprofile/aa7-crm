<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Security;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\ClientSession;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderMessage;

/**
 * Кабинет клиента. Маршруты /cabinet/* не требуют CRM-авторизации,
 * используется собственная cookie-сессия для клиентов.
 */
final class CabinetController extends Controller
{
    private const COOKIE_NAME = 'cab_session';
    private const COOKIE_TTL  = 60 * 60 * 24 * 30;

    /* ── Аутентификация ─────────────────────── */

    /** GET /cabinet/login */
    public function loginForm(Request $request, array $params = []): void
    {
        if ($this->currentClient()) {
            $this->redirect('/cabinet');
        }
        $this->renderCabinet('cabinet/login', ['title' => 'Вход в кабинет']);
    }

    /** POST /cabinet/login */
    public function login(Request $request, array $params = []): void
    {
        $this->requireCsrfCabinet($request);

        $login    = $request->input('login', '');
        $password = $request->input('password', '');

        $cred = ClientCredential::findByLogin($login);

        if (!$cred || !ClientCredential::verifyPassword($password, $cred['password_hash'])) {
            $this->renderCabinet('cabinet/login', [
                'title' => 'Вход в кабинет',
                'error' => 'Неверный логин или пароль.',
            ]);
            return;
        }

        ClientCredential::touch((int)$cred['client_id']);
        $sessionId = ClientSession::create((int)$cred['client_id']);
        $this->setCookie($sessionId);
        $this->redirect('/cabinet');
    }

    /** POST /cabinet/logout */
    public function logout(Request $request, array $params = []): void
    {
        $sessionId = $_COOKIE[self::COOKIE_NAME] ?? '';
        if ($sessionId) {
            ClientSession::destroy($sessionId);
        }
        setcookie(self::COOKIE_NAME, '', time() - 1, '/', '', false, true);
        $this->redirect('/cabinet/login');
    }

    /* ── Кабинет ────────────────────────────── */

    /** GET /cabinet */
    public function index(Request $request, array $params = []): void
    {
        $client = $this->requireClient();
        $orders = Order::paginate(['client_id' => $client['id']], 50);

        $this->renderCabinet('cabinet/index', [
            'title'  => 'Мои заказы',
            'client' => $client,
            'orders' => $orders,
        ]);
    }

    /** GET /cabinet/orders/{id} */
    public function orderView(Request $request, array $params = []): void
    {
        $client = $this->requireClient();
        $order  = $this->findClientOrder((int)($params['id'] ?? 0), (int)$client['id']);

        $messages = OrderMessage::forOrderAndClient((int)$order['id'], (int)$client['id']);
        $messages = array_map(fn($m) => array_merge($m, [
            'files' => OrderMessage::parseFiles($m['files_raw'] ?? ''),
        ]), $messages);

        $files = OrderFile::forOrder((int)$order['id']);

        $this->renderCabinet('cabinet/order', [
            'title'    => $order['number'] . ' · ' . $order['title'],
            'client'   => $client,
            'order'    => $order,
            'messages' => $messages,
            'files'    => $files,
        ]);
    }

    /** POST /cabinet/orders/{id}/message */
    public function sendMessage(Request $request, array $params = []): void
    {
        $this->requireCsrfCabinet($request);
        $client  = $this->requireClient();
        $order   = $this->findClientOrder((int)($params['id'] ?? 0), (int)$client['id']);
        $orderId = (int)$order['id'];

        $body = mb_substr(trim($request->input('body', '')), 0, 4000);
        if ($body === '' && empty($_FILES['file'])) {
            $this->redirect("/cabinet/orders/{$orderId}");
            return;
        }

        $msgId = null;
        if ($body !== '') {
            $msgId = OrderMessage::create([
                'order_id'    => $orderId,
                'client_id'   => (int)$client['id'],
                'from_client' => 1,
                'sender_id'   => null,
                'body'        => $body,
            ]);
        }

        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            try {
                OrderFile::upload($_FILES['file'], $orderId, (int)$client['id'], $msgId, null, true);
            } catch (\RuntimeException) {
                // не прерываем, просто пропускаем невалидный файл
            }
        }

        $this->redirect("/cabinet/orders/{$orderId}");
    }

    /** GET /cabinet/files/{id}/download */
    public function downloadFile(Request $request, array $params = []): void
    {
        $client = $this->requireClient();
        $file   = OrderFile::find((int)($params['id'] ?? 0));

        if (!$file || (int)$file['client_id'] !== (int)$client['id']) {
            http_response_code(403);
            echo 'Доступ запрещён.';
            exit;
        }

        $path = OrderFile::fullPath($file['stored_name']);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Файл не найден.';
            exit;
        }

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($file['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /* ── Helpers ───────────────────────────── */

    private function currentClient(): ?array
    {
        $sessionId = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!$sessionId) return null;
        $session = ClientSession::find($sessionId);
        return $session ?: null;
    }

    private function requireClient(): array
    {
        $client = $this->currentClient();
        if (!$client) {
            $this->redirect('/cabinet/login');
            exit;
        }
        return $client;
    }

    private function findClientOrder(int $orderId, int $clientId): array
    {
        $orders = Order::paginate(['client_id' => $clientId], 200);
        foreach ($orders as $o) {
            if ((int)$o['id'] === $orderId) return $o;
        }
        http_response_code(404);
        $this->renderCabinet('errors/404', ['title' => 'Заказ не найден']);
        exit;
    }

    private function setCookie(string $sessionId): void
    {
        setcookie(
            self::COOKIE_NAME,
            $sessionId,
            ['expires' => time() + self::COOKIE_TTL, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']
        );
    }

    private function requireCsrfCabinet(Request $request): void
    {
        if ($request->isPost() && !Security::verifyCsrf($request->input('_csrf'))) {
            http_response_code(419);
            exit('Недействительный токен.');
        }
    }

    private function renderCabinet(string $view, array $data = []): void
    {
        $data['_csrf'] = Security::csrfToken();
        $this->render($view, $data, 'cabinet');
    }
}
