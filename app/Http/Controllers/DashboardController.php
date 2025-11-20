<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardController extends Controller
{
    public function index()
    {
        $now          = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth   = $now->copy()->endOfMonth();

        /* =======================================================
         * GRÁFICA: total de ventas por día (mes actual)
         * ======================================================= */
        $rawDaily = Sale::selectRaw('DATE(issue_date) as date, SUM(total) as total')
            ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $labels = [];
        $values = [];

        foreach ($period as $date) {
            $labels[] = $date->translatedFormat('j M'); // "1 nov", "2 nov"
            $row      = $rawDaily->firstWhere('date', $date->toDateString());
            $values[] = $row ? (float) $row->total : 0;
        }

        $totalSalesMonth = array_sum($values);

        /* =======================================================
         * KPIs tipo Alegra (mes actual)
         * ======================================================= */

        // Ventas del mes
        $salesMonthQuery = Sale::whereBetween('issue_date', [$startOfMonth, $endOfMonth]);

        // Total de impuestos (IGV) en ventas
        $totalTaxMonth = (float) $salesMonthQuery->clone()->sum('tax');

        // Base imponible de ventas afectas a IGV (si tax > 0)
        $taxableBaseMonth = (float) $salesMonthQuery->clone()
            ->where('tax', '>', 0)
            ->sum('subtotal');

        // Productos vendidos
        $productsSoldMonth = (float) SaleItem::whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('issue_date', [$startOfMonth, $endOfMonth]);
            })
            ->sum('quantity');

        // Clientes con ventas
        $customersWithSalesMonth = (int) $salesMonthQuery->clone()
            ->distinct('customer_id')
            ->count('customer_id');

        /* =======================================================
         * Cuentas por cobrar: issued / partial
         * ======================================================= */
        $pendingSales = Sale::with('payments')
            ->whereIn('status', ['issued', 'partial'])
            ->get();

        $accountsReceivableTotal = $pendingSales->sum(fn (Sale $s) => $s->balance);

        $today = $now->copy()->startOfDay();

        $vigentes = $pendingSales->filter(fn (Sale $s) => !$s->due_date || $s->due_date >= $today);
        $vencidas = $pendingSales->filter(fn (Sale $s) => $s->due_date && $s->due_date < $today);

        $stats = [
            'accountsReceivable' => [
                'total'         => $accountsReceivableTotal,
                'vigente_total' => $vigentes->sum(fn (Sale $s) => $s->balance),
                'vencida_total' => $vencidas->sum(fn (Sale $s) => $s->balance),
                'vigente_docs'  => $vigentes->count(),
                'vencida_docs'  => $vencidas->count(),
            ],

            'totalSalesMonth'         => $totalSalesMonth,
            'totalTaxMonth'           => $totalTaxMonth,
            'taxableBaseMonth'        => $taxableBaseMonth,
            'productsSoldMonth'       => $productsSoldMonth,
            'customersWithSalesMonth' => $customersWithSalesMonth,

            // placeholder por ahora (cuando tengas compras, se llena)
            'accountsPayable' => [
                'total'         => 0,
                'vigente_total' => 0,
                'vencida_total' => 0,
                'vigente_docs'  => 0,
                'vencida_docs'  => 0,
            ],

            'totalSalesMonth'         => $totalSalesMonth,
            'totalTaxMonth'           => $totalTaxMonth,
            'taxableBaseMonth'        => $taxableBaseMonth,
            'productsSoldMonth'       => $productsSoldMonth,
            'customersWithSalesMonth' => $customersWithSalesMonth,
            'returnsTotal'            => 0, // aquí luego conectamos notas de crédito / devoluciones
                ];

        return Inertia::render('Dashboard/Index', [
            'salesChart' => [
                'labels' => $labels,
                'values' => $values,
                'total'  => $totalSalesMonth,
                'period' => $startOfMonth->translatedFormat('j M Y') . ' - ' . $endOfMonth->translatedFormat('j M Y'),
            ],
            'stats'      => $stats,
        ]);
    }
}
