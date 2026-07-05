<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ExchangeRate;

final class OrderController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $filters = [
            'status'   => $request->input('status'),
            'owner_id' => $request->integer('owner_id') ?: null,
            'q'        => $request->input('q'),
        ];
        $orders = Order::paginate(array_filter($filters));

        $this->render('orders/index', [
            'title'   => 'Заказы',
            'orders'  => $orders,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->render('orders/form', [
            'title'   => 'Новый заказ',
            'order'   => null,
            'clients' => Client::options(),
            'rates'   => ExchangeRate::all(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $currency = strtoupper($request->input('currency', 'RUB'));
        $amount   = $request->float('amount_input');
        $fx       = ExchangeRate::convertToRub($amount, $currency);
        $number   = Order::nextNumber();

        $clientId = $this->resolveClient($request);

        $data = [
            'number'         => $number,
            'title'          => $request->input('title'),
            'description'    => $request->input('description'),
            'client_id'      => $clientId,
            'owner_id'       => Auth::id(),
            'status'         => 'new',
            'priority'       => $request->input('priority', 'normal'),
            'amount_input'   => $amount,
            'currency'       => $currency,
            'fx_rate'        => $fx['rate'],
            'amount_rub'     => $fx['rub'],
            'payment_status' => 'unpaid',
            'progress'       => 0,
            'tags'           => $request->input('tags'),
            'due_at'         => $request->input('due_at') ?: null,
        ];

        $id = Order::create($data);
        Order::addEvent($id, Auth::id(), 'created', 'Заказ создан');
        ActivityLog::record('order_create', 'order', $id, "Создан заказ {$number}");
        Notification::push(null, 'success', 'orders', "Новый заказ {$number}", $data['title'], "/orders/{$id}");

        Flash::success("Заказ {$number} создан.");
        $this->redirect("/orders/{$id}");
    }

    public function show(Request $request, array $params = []): void
    {
        $order = $this->findOrder((int) ($params['id'] ?? 0));
        $events   = Order::events((int) $order['id']);
        $payments = Payment::forOrder((int) $order['id']);

        $this->render('orders/show', [
            'title'    => $order['number'] . ' · ' . $order['title'],
            'order'    => $order,
            'events'   => $events,
            'payments' => $payments,
        ]);
    }

    public function edit(Request $request, array $params = []): void
    {
        $order = $this->findOrder((int) ($params['id'] ?? 0));
        $this->render('orders/form', [
            'title'   => 'Редактировать · ' . $order['number'],
            'order'   => $order,
            'clients' => Client::options(),
            'rates'   => ExchangeRate::all(),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $order = $this->findOrder((int) ($params['id'] ?? 0));
        $id    = (int) $order['id'];

        $currency = strtoupper($request->input('currency', 'RUB'));
        $amount   = $request->float('amount_input');
        $fx       = ExchangeRate::convertToRub($amount, $currency);

        $data = [
            'title'        => $request->input('title'),
            'description'  => $request->input('description'),
            'client_id'    => $request->integer('client_id') ?: null,
            'owner_id'     => Auth::id(),
            'status'       => $request->input('status'),
            'priority'     => $request->input('priority', 'normal'),
            'amount_input' => $amount,
            'currency'     => $currency,
            'fx_rate'      => $fx['rate'],
            'amount_rub'   => $fx['rub'],
            'progress'     => $request->integer('progress'),
            'tags'         => $request->input('tags'),
            'due_at'       => $request->input('due_at') ?: null,
        ];

        Order::update($id, $data);
        Order::addEvent($id, Auth::id(), 'updated', 'Заказ обновлён');
        ActivityLog::record('order_update', 'order', $id, "Обновлён заказ {$order['number']}");
        Flash::success('Изменения сохранены.');
        $this->redirect("/orders/{$id}");
    }

    public function updateStatus(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $order  = $this->findOrder((int) ($params['id'] ?? 0));
        $id     = (int) $order['id'];
        $status = $request->input('status', '');

        $allowed = ['new', 'negotiation', 'in_progress', 'review', 'done', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            Flash::error('Неверный статус.');
            $this->redirect("/orders/{$id}");
        }

        Order::changeStatus($id, $status);
        Order::addEvent($id, Auth::id(), 'status', "Статус изменён на «{$status}»");
        ActivityLog::record('order_status', 'order', $id, "Статус: {$status}");
        Flash::success('Статус обновлён.');
        $this->redirect("/orders/{$id}");
    }

    public function comment(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id   = (int) ($params['id'] ?? 0);
        $text = trim($request->input('message', ''));
        if ($text === '') {
            $this->redirect("/orders/{$id}");
        }
        Order::addEvent($id, Auth::id(), 'comment', mb_substr($text, 0, 400));
        $this->redirect("/orders/{$id}");
    }

    public function addPayment(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $order    = $this->findOrder((int) ($params['id'] ?? 0));
        $id       = (int) $order['id'];
        $currency = strtoupper($request->input('currency', 'RUB'));
        $amount   = $request->float('amount_input');
        $fx       = ExchangeRate::convertToRub($amount, $currency);

        Payment::create([
            'order_id'     => $id,
            'amount_input' => $amount,
            'currency'     => $currency,
            'fx_rate'      => $fx['rate'],
            'amount_rub'   => $fx['rub'],
            'method'       => $request->input('method'),
            'note'         => $request->input('note'),
            'created_by'   => Auth::id(),
            'paid_at'      => $request->input('paid_at') ?: date('Y-m-d H:i:s'),
        ]);

        Order::recalcPayment($id);
        Order::addEvent($id, Auth::id(), 'payment', "Оплата {$amount} {$currency}");
        ActivityLog::record('payment_add', 'order', $id, "Платёж {$amount} {$currency}");
        Flash::success('Платёж добавлен.');
        $this->redirect("/orders/{$id}");
    }

    public function deletePayment(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $paymentId = (int) ($params['id'] ?? 0);
        $orderId   = Payment::delete($paymentId);
        if ($orderId) {
            Order::recalcPayment($orderId);
        }
        Flash::success('Платёж удалён.');
        $this->redirect($orderId ? "/orders/{$orderId}" : '/orders');
    }

    private function findOrder(int $id): array
    {
        $order = Order::find($id);
        if (!$order) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Заказ не найден']);
            exit;
        }
        return $order;
    }

    private function resolveClient(Request $request): ?int
    {
        $existing = $request->integer('client_id');
        if ($existing > 0) {
            return $existing;
        }
        $name = trim($request->input('new_client_name', ''));
        if ($name === '') {
            return null;
        }
        return Client::create([
            'type'    => 'person',
            'name'    => $name,
            'phone'   => $request->input('new_client_phone'),
            'email'   => $request->input('new_client_email'),
            'status'  => 'active',
            'owner_id' => Auth::id(),
        ]);
    }
}
