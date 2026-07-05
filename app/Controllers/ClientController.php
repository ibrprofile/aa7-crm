<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Order;

final class ClientController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $filters = [
            'type'   => $request->input('type'),
            'status' => $request->input('status'),
            'q'      => $request->input('q'),
        ];
        $clients = Client::paginate(array_filter($filters));

        $this->render('clients/index', [
            'title'   => 'Клиенты',
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, array $params = []): void
    {
        $this->render('clients/form', [
            'title'     => 'Новый клиент',
            'client'    => null,
            'companies' => Client::companies(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $data = $this->collectData($request);
        $id   = Client::create($data);

        ActivityLog::record('client_create', 'client', $id, "Создан клиент: {$data['name']}");
        Flash::success('Клиент добавлен.');
        $this->redirect("/clients/{$id}");
    }

    public function show(Request $request, array $params = []): void
    {
        $client     = $this->findClient((int) ($params['id'] ?? 0));
        $summary    = Client::summary((int) $client['id']);
        $orders     = Order::paginate(['client_id' => $client['id']], 30);
        $contacts   = $client['type'] === 'company'
            ? Client::contactsOf((int) $client['id'])
            : [];
        $credential = ClientCredential::find((int) $client['id']);

        $this->render('clients/show', [
            'title'      => $client['name'],
            'client'     => $client,
            'summary'    => $summary,
            'orders'     => $orders,
            'contacts'   => $contacts,
            'credential' => $credential,
        ]);
    }

    public function setCabinet(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $client   = $this->findClient((int) ($params['id'] ?? 0));
        $id       = (int) $client['id'];
        $login    = trim($request->input('cab_login', ''));
        $password = $request->input('cab_password', '');

        if ($login === '') {
            Flash::error('Укажите логин для кабинета.');
            $this->redirect("/clients/{$id}");
        }

        if (ClientCredential::loginExists($login, $id)) {
            Flash::error('Этот логин уже занят другим клиентом.');
            $this->redirect("/clients/{$id}");
        }

        $existing = ClientCredential::find($id);
        if ($existing && $password === '') {
            // Обновляем только логин
            Database::instance()->run(
                'UPDATE client_credentials SET login = ? WHERE client_id = ?',
                [$login, $id]
            );
        } else {
            if ($password === '') {
                Flash::error('Введите пароль.');
                $this->redirect("/clients/{$id}");
            }
            ClientCredential::upsert($id, $login, $password);
        }

        ActivityLog::record('client_cabinet', 'client', $id, "Установлен/обновлён доступ к кабинету");
        Flash::success('Доступ к кабинету сохранён.');
        $this->redirect("/clients/{$id}");
    }

    public function edit(Request $request, array $params = []): void
    {
        $client = $this->findClient((int) ($params['id'] ?? 0));
        $this->render('clients/form', [
            'title'     => 'Редактировать · ' . $client['name'],
            'client'    => $client,
            'companies' => Client::companies(),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $client = $this->findClient((int) ($params['id'] ?? 0));
        $id     = (int) $client['id'];

        Client::update($id, $this->collectData($request));
        ActivityLog::record('client_update', 'client', $id, "Обновлён клиент: {$client['name']}");
        Flash::success('Данные клиента сохранены.');
        $this->redirect("/clients/{$id}");
    }

    private function findClient(int $id): array
    {
        $client = Client::find($id);
        if (!$client) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Клиент не найден']);
            exit;
        }
        return $client;
    }

    private function collectData(Request $request): array
    {
        return [
            'type'       => $request->input('type', 'person'),
            'company_id' => $request->integer('company_id') ?: null,
            'name'       => $request->input('name'),
            'legal_name' => $request->input('legal_name'),
            'position'   => $request->input('position'),
            'email'      => $request->input('email'),
            'phone'      => $request->input('phone'),
            'telegram'   => $request->input('telegram'),
            'whatsapp'   => $request->input('whatsapp'),
            'website'    => $request->input('website'),
            'inn'        => $request->input('inn'),
            'country'    => $request->input('country'),
            'city'       => $request->input('city'),
            'address'    => $request->input('address'),
            'industry'   => $request->input('industry'),
            'source'     => $request->input('source'),
            'status'     => $request->input('status', 'lead'),
            'notes'      => $request->input('notes'),
            'owner_id'   => Auth::id(),
        ];
    }
}
