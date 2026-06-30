<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(private WebhookService $webhookService) {}

    // Vente multi-produits (panier complet)
    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'ticket_number'        => 'nullable|string|max:100',
            'payment_method'       => 'nullable|in:wave,om,cash',
            'notes'                => 'nullable|string|max:500',
        ]);

        $sales = DB::transaction(function () use ($data, $request) {
            $createdSales = [];
            $ticketNumber = $data['ticket_number'] ?? null;
            $paymentMethod = $data['payment_method'] ?? null;
            $notes = $data['notes'] ?? null;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stock_boutique < $item['quantity']) {
                    throw new \Exception("Stock boutique insuffisant pour « {$product->name} ». Disponible : {$product->stock_boutique}");
                }

                $product->decrement('stock_boutique', $item['quantity']);
                $product->refresh();

                $sale = Sale::create([
                    'product_id'     => $product->id,
                    'user_id'        => $request->user()->id,
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $product->sale_price,
                    'total_price'    => $product->sale_price * $item['quantity'],
                    'notes'          => $notes,
                    'ticket_number'  => $ticketNumber,
                    'payment_method' => $paymentMethod,
                ]);

                $this->webhookService->dispatch('sale.created', [
                    'sale_id'              => $sale->id,
                    'ticket_number'        => $ticketNumber,
                    'payment_method'       => $paymentMethod,
                    'product_id'           => $product->id,
                    'product_name'         => $product->name,
                    'quantity'             => $item['quantity'],
                    'unit_price'           => (float) $product->sale_price,
                    'total_price'          => (float) $sale->total_price,
                    'stock_boutique_after' => $product->stock_boutique,
                    'cashier'              => $request->user()->name,
                ]);

                if ($product->isBoutiqueAlerted()) {
                    $this->webhookService->dispatch('stock.alert', [
                        'product_id'    => $product->id,
                        'name'          => $product->name,
                        'location'      => 'boutique',
                        'current_stock' => $product->stock_boutique,
                        'threshold'     => $product->alert_threshold_boutique,
                    ]);
                }

                $createdSales[] = $sale->load('product');
            }

            return $createdSales;
        });

        $total = collect($sales)->sum(fn($s) => (float) $s->total_price);

        return response()->json([
            'message'        => 'Vente enregistrée avec succès.',
            'sales'          => $sales,
            'total'          => $total,
            'ticket_number'  => $data['ticket_number'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
        ], 201);
    }

    // Vente unitaire — conservé pour compatibilité API
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'notes'          => 'nullable|string|max:500',
            'ticket_number'  => 'nullable|string|max:100',
            'payment_method' => 'nullable|in:wave,om,cash',
        ]);

        $product = Product::findOrFail($data['product_id']);

        if ($product->stock_boutique < $data['quantity']) {
            return response()->json([
                'message' => "Stock boutique insuffisant. Disponible: {$product->stock_boutique}",
            ], 422);
        }

        $sale = DB::transaction(function () use ($data, $product, $request) {
            $product->decrement('stock_boutique', $data['quantity']);
            $product->refresh();

            $sale = Sale::create([
                'product_id'     => $product->id,
                'user_id'        => $request->user()->id,
                'quantity'       => $data['quantity'],
                'unit_price'     => $product->sale_price,
                'total_price'    => $product->sale_price * $data['quantity'],
                'notes'          => $data['notes'] ?? null,
                'ticket_number'  => $data['ticket_number'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
            ]);

            $this->webhookService->dispatch('sale.created', [
                'sale_id'              => $sale->id,
                'product_id'           => $product->id,
                'product_name'         => $product->name,
                'quantity'             => $data['quantity'],
                'unit_price'           => (float) $product->sale_price,
                'total_price'          => (float) $sale->total_price,
                'stock_boutique_after' => $product->stock_boutique,
                'cashier'              => $request->user()->name,
            ]);

            if ($product->isBoutiqueAlerted()) {
                $this->webhookService->dispatch('stock.alert', [
                    'product_id'    => $product->id,
                    'name'          => $product->name,
                    'location'      => 'boutique',
                    'current_stock' => $product->stock_boutique,
                    'threshold'     => $product->alert_threshold_boutique,
                ]);
            }

            return $sale;
        });

        return response()->json([
            'message'                  => 'Vente enregistrée avec succès.',
            'sale'                     => $sale->load('product'),
            'stock_boutique_remaining' => $product->fresh()->stock_boutique,
        ], 201);
    }

    public function history(Request $request)
    {
        $query = Sale::with(['product.category', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->has('category_id')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
        }
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->has('period')) {
            match($request->period) {
                'day'   => $query->whereDate('created_at', today()),
                'week'  => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                default => null,
            };
        }

        $sales = $query->paginate(50);

        $summary = [
            'total_sales'       => $query->count(),
            'total_revenue'     => $query->sum('total_price'),
            'total_items_sold'  => $query->sum('quantity'),
        ];

        return response()->json([
            'data'    => $sales,
            'summary' => $summary,
        ]);
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['product.category', 'user']));
    }
}
