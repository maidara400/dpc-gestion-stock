<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::where('active', true)->count();

        $alertProducts = Product::where('active', true)
            ->where(function ($q) {
                $q->whereRaw('stock_boutique <= alert_threshold_boutique')
                  ->orWhereRaw('stock_depot <= alert_threshold_depot');
            })
            ->with('category')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name,
                'stock_depot' => $p->stock_depot,
                'stock_boutique' => $p->stock_boutique,
                'alert_threshold_depot' => $p->alert_threshold_depot,
                'alert_threshold_boutique' => $p->alert_threshold_boutique,
                'depot_alerted' => $p->isDepotAlerted(),
                'boutique_alerted' => $p->isBoutiqueAlerted(),
            ]);

        $salesToday = Sale::whereDate('created_at', today())->sum('total_price');
        $salesWeek = Sale::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_price');
        $salesMonth = Sale::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_price');

        $recentSales = Sale::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentMovements = StockMovement::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stockSummary = Product::where('active', true)->selectRaw('
            SUM(stock_depot) as total_depot,
            SUM(stock_boutique) as total_boutique,
            SUM(stock_depot * purchase_price) as valeur_depot,
            SUM(stock_boutique * sale_price) as valeur_boutique
        ')->first();

        return response()->json([
            'stats' => [
                'total_products' => $totalProducts,
                'alert_count' => $alertProducts->count(),
                'sales_today' => round((float) $salesToday, 2),
                'sales_week' => round((float) $salesWeek, 2),
                'sales_month' => round((float) $salesMonth, 2),
                'total_depot_stock' => (int) $stockSummary->total_depot,
                'total_boutique_stock' => (int) $stockSummary->total_boutique,
                'valeur_depot' => round((float) $stockSummary->valeur_depot, 2),
                'valeur_boutique' => round((float) $stockSummary->valeur_boutique, 2),
            ],
            'alert_products' => $alertProducts,
            'recent_sales' => $recentSales,
            'recent_movements' => $recentMovements,
        ]);
    }
}
