<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $router): void {
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->post('/logout', 'AuthController@logout');

    $router->group(['App\Core\AuthMiddleware'], static function (Router $router): void {
        $router->get('/', 'DashboardController@index');
        $router->get('/dashboard', 'DashboardController@index');

        $router->get('/orders', 'OrderController@index');
        $router->get('/orders/create', 'OrderController@create');
        $router->post('/orders', 'OrderController@store');
        $router->get('/orders/{id}', 'OrderController@show');
        $router->get('/orders/{id}/edit', 'OrderController@edit');
        $router->post('/orders/{id}', 'OrderController@update');
        $router->post('/orders/{id}/status', 'OrderController@updateStatus');
        $router->post('/orders/{id}/comment', 'OrderController@comment');
        $router->post('/orders/{id}/payments', 'OrderController@addPayment');
        $router->post('/payments/{id}/delete', 'OrderController@deletePayment');

        $router->get('/clients', 'ClientController@index');
        $router->get('/clients/create', 'ClientController@create');
        $router->post('/clients', 'ClientController@store');
        $router->get('/clients/{id}', 'ClientController@show');
        $router->get('/clients/{id}/edit', 'ClientController@edit');
        $router->post('/clients/{id}', 'ClientController@update');

        $router->get('/leads', 'LeadController@index');
        $router->post('/leads/search', 'LeadController@search');
        $router->post('/leads/save', 'LeadController@save');
        $router->get('/leads/history', 'LeadController@historyFile');
        $router->post('/leads/reset-seen', 'LeadController@resetSeen');
        $router->get('/leads/saved', 'LeadController@saved');
        $router->post('/leads/{id}/status', 'LeadController@updateStatus');
        $router->post('/leads/{id}/convert', 'LeadController@convert');
        $router->post('/leads/{id}/delete', 'LeadController@delete');

        $router->get('/statistics', 'StatsController@index');

        $router->get('/notifications', 'NotificationController@index');
        $router->post('/notifications/read', 'NotificationController@readAll');
        $router->post('/notifications/{id}/read', 'NotificationController@read');

        $router->get('/settings', 'SettingsController@index');
        $router->post('/settings/profile', 'SettingsController@updateProfile');
        $router->post('/settings/password', 'SettingsController@updatePassword');

            // Управление доступом кабинета клиента
        $router->post('/clients/{id}/cabinet', 'ClientController@setCabinet');

        // API для AJAX-панели заказа
        $router->get('/api/orders/{id}/panel',    'ApiOrderController@panel');
        $router->post('/api/orders/{id}/status',  'ApiOrderController@status');
        $router->post('/api/orders/{id}/comment', 'ApiOrderController@comment');
        $router->get('/api/orders/{id}/files',    'ApiOrderController@listFiles');
        $router->post('/api/orders/{id}/files',   'ApiOrderController@uploadFile');
        $router->get('/api/orders/{id}/messages', 'ApiOrderController@messages');
        $router->post('/api/orders/{id}/messages','ApiOrderController@sendMessage');

        $router->group(['App\Core\AdminMiddleware'], static function (Router $router): void {
            $router->get('/team', 'TeamController@index');
            $router->post('/team', 'TeamController@store');
            $router->post('/team/{id}/toggle', 'TeamController@toggle');
            $router->post('/team/{id}/role', 'TeamController@changeRole');
            $router->get('/team/logs', 'TeamController@logs');
        });
    });

    // Кабинет клиента — не требует CRM-авторизации
    $router->get('/cabinet/login',              'CabinetController@loginForm');
    $router->post('/cabinet/login',             'CabinetController@login');
    $router->post('/cabinet/logout',            'CabinetController@logout');
    $router->get('/cabinet',                    'CabinetController@index');
    $router->get('/cabinet/orders/{id}',        'CabinetController@orderView');
    $router->post('/cabinet/orders/{id}/message', 'CabinetController@sendMessage');
    $router->get('/cabinet/files/{id}/download', 'CabinetController@downloadFile');
};
