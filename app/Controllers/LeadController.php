<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\SavedLead;
use App\Services\Leads\LeadFinder;
use App\Services\Leads\SearchHistory;

final class LeadController extends Controller
{
    private LeadFinder $finder;
    private SearchHistory $history;

    public function __construct()
    {
        $this->finder  = new LeadFinder();
        $this->history = new SearchHistory(dirname(__DIR__, 2));
    }

    public function index(Request $request, array $params = []): void
    {
        $this->render('leads/index', [
            'title'   => 'Поиск клиентов',
            'sources' => $this->finder->options(),
            'history' => $this->history->list(),
        ]);
    }

    public function search(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $bucket = 'leads_search_' . Security::clientIp();
        if (!Security::rateLimit($bucket, 30, 60)) {
            $this->json(['error' => 'Слишком много запросов. Подождите минуту.'], 429);
        }

        $source     = $request->input('source', 'osm');
        $category   = trim($request->input('category', ''));
        $city       = trim($request->input('city', ''));
        $limit      = min(50, max(6, $request->integer('limit', 20)));
        $noWebsite  = $request->input('no_website', '0') === '1';

        if ($category === '' || $city === '') {
            $this->json(['error' => 'Укажите нишу и город.'], 422);
        }

        // Запрашиваем с запасом — после фильтрации может остаться меньше
        $fetchLimit = $noWebsite ? min(50, $limit * 4) : $limit;
        $result = $this->finder->search($source, $category, $city, $fetchLimit);

        $meta = [
            'source'          => $source,
            'category'        => $category,
            'city'            => $city,
            'no_website_only' => $noWebsite,
        ];

        // Фильтрация: убрать уже виденных + без сайта если нужно
        $filtered = $this->history->filterAndSave($result['items'] ?? [], $meta, $noWebsite);

        // Обрезаем до запрошенного лимита
        $filtered = array_slice($filtered, 0, $limit);

        $result['items']               = $filtered;
        $result['meta']['count']       = count($filtered);
        $result['meta']['no_website']  = $noWebsite;
        $result['meta']['deduplicated'] = true;

        $this->json($result);
    }

    public function historyFile(Request $request, array $params = []): void
    {
        $file = $request->input('file', '');
        $data = $this->history->load($file);
        if (!$data) {
            $this->json(['error' => 'Файл не найден.'], 404);
        }
        $this->json($data);
    }

    public function resetSeen(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $this->history->resetSeen();
        $this->json(['ok' => true]);
    }

    public function save(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $externalId = $request->input('external_id', '');
        $source     = $request->input('source', '');

        if (SavedLead::exists($source, $externalId)) {
            $this->json(['saved' => false, 'reason' => 'already_saved']);
        }

        $rating = $request->float('rating');
        $reviews = $request->integer('reviews');

        SavedLead::create([
            'source'      => $source,
            'external_id' => $externalId,
            'name'        => $request->input('name'),
            'category'    => $request->input('category'),
            'city'        => $request->input('city'),
            'address'     => $request->input('address'),
            'phone'       => $request->input('phone'),
            'website'     => $request->input('website'),
            'instagram'   => $request->input('instagram'),
            'whatsapp'    => $request->input('whatsapp'),
            'rating'      => $rating ?: null,
            'reviews'     => $reviews ?: null,
            'payload'     => null,
            'status'      => 'new',
            'created_by'  => Auth::id(),
        ]);

        ActivityLog::record('lead_save', 'lead', null, "Сохранён лид: " . $request->input('name'));
        $this->json(['saved' => true]);
    }

    public function saved(Request $request, array $params = []): void
    {
        $filters = [
            'source' => $request->input('source'),
            'status' => $request->input('status'),
        ];
        $leads  = SavedLead::all(array_filter($filters));
        $counts = SavedLead::counts();

        $this->render('leads/saved', [
            'title'   => 'Сохранённые лиды',
            'leads'   => $leads,
            'counts'  => $counts,
            'filters' => $filters,
        ]);
    }

    public function updateStatus(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id     = (int) ($params['id'] ?? 0);
        $status = $request->input('status', '');

        $allowed = ['new', 'contacted', 'converted', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            Flash::error('Недопустимый статус.');
            $this->redirect('/leads/saved');
        }

        SavedLead::updateStatus($id, $status);
        Flash::success('Статус обновлён.');
        $this->redirect('/leads/saved');
    }

    public function convert(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id   = (int) ($params['id'] ?? 0);
        $lead = SavedLead::find($id);

        if (!$lead) {
            Flash::error('Лид не найден.');
            $this->redirect('/leads/saved');
        }

        $clientId = Client::create([
            'type'    => 'company',
            'name'    => $lead['name'],
            'phone'   => $lead['phone'],
            'website' => $lead['website'],
            'city'    => $lead['city'],
            'status'  => 'lead',
            'source'  => $lead['source'],
            'owner_id' => Auth::id(),
        ]);

        SavedLead::updateStatus($id, 'converted');
        ActivityLog::record('lead_convert', 'client', $clientId, "Лид конвертирован: {$lead['name']}");
        Flash::success("Лид конвертирован в клиента.");
        $this->redirect("/clients/{$clientId}");
    }

    public function delete(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id = (int) ($params['id'] ?? 0);
        SavedLead::delete($id);
        Flash::success('Лид удалён.');
        $this->redirect('/leads/saved');
    }
}
