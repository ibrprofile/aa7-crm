<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\User;

final class SettingsController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $this->render('settings/index', [
            'title' => 'Настройки',
            'user'  => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id   = (int) Auth::id();
        $user = Auth::user();

        $data = [
            'name'         => $request->input('name') ?: $user['name'],
            'email'        => $request->input('email'),
            'phone'        => $request->input('phone'),
            'role'         => $user['role'],
            'avatar_color' => $request->input('avatar_color') ?: $user['avatar_color'],
            'is_active'    => 1,
        ];

        User::update($id, $data);
        $_SESSION['user_name'] = $data['name'];
        ActivityLog::record('profile_update', 'user', $id, 'Профиль обновлён');
        Flash::success('Профиль обновлён.');
        $this->redirect('/settings');
    }

    public function updatePassword(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        $id      = (int) Auth::id();
        $current = $request->input('current_password', '');
        $new     = $request->input('new_password', '');
        $confirm = $request->input('confirm_password', '');

        $user = Auth::user();
        if (!Security::verifyPassword($current, $user['password_hash'])) {
            Flash::error('Текущий пароль введён неверно.');
            $this->redirect('/settings');
        }
        if (strlen($new) < 8) {
            Flash::error('Новый пароль должен содержать минимум 8 символов.');
            $this->redirect('/settings');
        }
        if ($new !== $confirm) {
            Flash::error('Пароли не совпадают.');
            $this->redirect('/settings');
        }

        User::updatePassword($id, Security::hashPassword($new));
        ActivityLog::record('password_change', 'user', $id, 'Пароль изменён');
        Flash::success('Пароль успешно изменён.');
        $this->redirect('/settings');
    }
}
