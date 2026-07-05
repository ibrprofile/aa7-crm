<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\User;

final class TeamController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $this->render('team/index', [
            'title' => 'Команда',
            'users' => User::all(),
        ]);
    }

    public function store(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $login    = trim($request->input('login', ''));
        $password = trim($request->input('password', ''));

        if ($login === '' || strlen($password) < 8) {
            Flash::error('Логин обязателен, пароль — минимум 8 символов.');
            $this->redirect('/team');
        }

        $colors = ['#3ecf8e', '#6366f1', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6'];
        $color  = $colors[array_rand($colors)];

        $id = User::create([
            'login'         => $login,
            'name'          => $request->input('name') ?: $login,
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'password_hash' => Security::hashPassword($password),
            'role'          => $request->input('role', 'manager'),
            'avatar_color'  => $color,
            'is_active'     => 1,
        ]);

        ActivityLog::record('team_add', 'user', $id, "Добавлен сотрудник: {$login}");
        Flash::success('Сотрудник добавлен.');
        $this->redirect('/team');
    }

    public function toggle(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id     = (int) ($params['id'] ?? 0);
        $active = (bool) $request->integer('active');
        User::toggleActive($id, $active);
        ActivityLog::record('team_toggle', 'user', $id, $active ? 'Активирован' : 'Деактивирован');
        Flash::success($active ? 'Аккаунт активирован.' : 'Аккаунт деактивирован.');
        $this->redirect('/team');
    }

    public function changeRole(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id   = (int) ($params['id'] ?? 0);
        $role = $request->input('role', 'manager');
        if (!in_array($role, ['admin', 'manager', 'viewer'], true)) {
            Flash::error('Недопустимая роль.');
            $this->redirect('/team');
        }
        $user = User::find($id);
        if ($user) {
            User::update($id, [
                'name'         => $user['name'],
                'email'        => $user['email'],
                'phone'        => $user['phone'],
                'role'         => $role,
                'avatar_color' => $user['avatar_color'],
                'is_active'    => $user['is_active'],
            ]);
        }
        ActivityLog::record('team_role', 'user', $id, "Роль изменена на: {$role}");
        Flash::success('Роль обновлена.');
        $this->redirect('/team');
    }

    public function logs(Request $request, array $params = []): void
    {
        $userId = $request->integer('user_id') ?: null;
        $this->render('team/logs', [
            'title'   => 'Журнал действий',
            'logs'    => ActivityLog::recent(100, $userId),
            'users'   => User::all(),
            'userId'  => $userId,
        ]);
    }
}
