<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Security;
use App\Models\ActivityLog;

final class AuthController extends Controller
{
    public function showLogin(Request $request, array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', ['title' => 'Вход'], 'auth');
    }

    public function login(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);

        $login    = $request->input('login', '');
        $password = $request->input('password', '');
        $bucket   = 'login_' . Security::clientIp();

        if (!Security::rateLimit($bucket, 8, 600)) {
            Flash::error('Слишком много попыток входа. Подождите 10 минут.');
            $this->redirect('/login');
        }

        $user = Auth::attempt($login, $password);

        if (!$user) {
            Flash::error('Неверный логин или пароль.');
            $this->redirect('/login');
        }

        Auth::login($user);
        ActivityLog::record('login', 'user', (int) $user['id'], 'Вход в систему');
        $this->redirect('/dashboard');
    }

    public function logout(Request $request, array $params = []): void
    {
        $this->requireCsrf($request);
        ActivityLog::record('logout');
        Auth::logout();
        $this->redirect('/login');
    }
}
