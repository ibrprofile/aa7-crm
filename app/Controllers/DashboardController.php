<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Stats;

final class DashboardController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $overview  = Stats::overview();
        $byMonth   = Stats::revenueByMonth(6);
        $byStatus  = Stats::ordersByStatus();
        $topClients = Stats::topClients(5);
        $recent    = Order::paginate([], 8);
        $payments  = Payment::recent(6);
        $activity  = ActivityLog::recent(12);
        $conversion = Stats::conversion();

        $this->render('dashboard/index', [
            'title'      => 'Дашборд',
            'overview'   => $overview,
            'byMonth'    => $byMonth,
            'byStatus'   => $byStatus,
            'topClients' => $topClients,
            'recent'     => $recent,
            'payments'   => $payments,
            'activity'   => $activity,
            'conversion' => $conversion,
        ]);
    }
}
