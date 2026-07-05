<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Stats;
use App\Models\Payment;

final class StatsController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $period = max(3, min(24, $request->integer('period', 12)));

        $this->render('stats/index', [
            'title'       => 'Статистика',
            'overview'    => Stats::overview(),
            'byMonth'     => Stats::revenueByMonth($period),
            'byStatus'    => Stats::ordersByStatus(),
            'topClients'  => Stats::topClients(10),
            'byCountry'   => Stats::revenueByCountry(),
            'currency'    => Stats::currencySplit(),
            'team'        => Stats::teamPerformance(),
            'conversion'  => Stats::conversion(),
            'recentPay'   => Payment::recent(20),
            'period'      => $period,
        ]);
    }
}
