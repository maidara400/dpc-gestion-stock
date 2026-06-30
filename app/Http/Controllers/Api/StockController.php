<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function addDepot(Request $request)
    {
        $data = $request->validate([
            'product_id'       => 'required|exists:products,id',
            'quantity'         => 'required|integer|min:1',
            'notes'            => 'nullable|string|max:500',
            'supplier_id'      => 'nullable|exists:suppliers,id',
            'amount_due'       => 'nullable|numeric|min:0',
            'payment_due_date' => 'nullable|date',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $movement = $this->stockService->addToDepot(
            $product,
            $data['quantity'],
            $data['notes'] ?? null,
            $request->user()->id,
            $data['supplier_id'] ?? null,
            isset($data['amount_due']) ? (float) $data['amount_due'] : null,
            $data['payment_due_date'] ?? null,
        );

        return response()->json([
            'message'  => 'Stock dépôt mis à jour.',
            'movement' => $movement->load('supplier'),
            'product'  => [
                'id'             => $product->id,
                'name'           => $product->name,
                'stock_depot'    => $product->fresh()->stock_depot,
                'stock_boutique' => $product->fresh()->stock_boutique,
            ],
        ], 201);
    }

    public function transferToBoutique(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'notes'      => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($data['product_id']);

        try {
            $movement = $this->stockService->transferToBoutique(
                $product,
                $data['quantity'],
                $data['notes'] ?? null,
                $request->user()->id
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $product->refresh();

        return response()->json([
            'message'  => 'Transfert effectué avec succès.',
            'movement' => $movement,
            'product'  => [
                'id'             => $product->id,
                'name'           => $product->name,
                'stock_depot'    => $product->stock_depot,
                'stock_boutique' => $product->stock_boutique,
            ],
        ], 201);
    }

    public function setInitialBoutiqueStock(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:0',
            'notes'      => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($data['product_id']);

        $movement = $this->stockService->setInitialBoutiqueStock(
            $product,
            $data['quantity'],
            $data['notes'] ?? null,
            $request->user()->id
        );

        $product->refresh();

        return response()->json([
            'message'  => 'Stock boutique initial enregistré.',
            'movement' => $movement,
            'product'  => [
                'id'             => $product->id,
                'name'           => $product->name,
                'stock_depot'    => $product->stock_depot,
                'stock_boutique' => $product->stock_boutique,
            ],
        ], 201);
    }

    public function movements(Request $request)
    {
        $query = StockMovement::with(['product.category', 'user', 'supplier'])
            ->orderBy('created_at', 'desc');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json($query->paginate(50));
    }

    public function pendingPayments()
    {
        // Auto-passage en overdue
        StockMovement::where('payment_status', 'pending')
            ->whereDate('payment_due_date', '<', now()->toDateString())
            ->update(['payment_status' => 'overdue']);

        $movements = StockMovement::with(['product', 'supplier'])
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->orderBy('payment_due_date')
            ->get()
            ->map(fn($m) => [
                'id'               => $m->id,
                'product_name'     => $m->product?->name,
                'supplier_name'    => $m->supplier?->name,
                'quantity'         => $m->quantity,
                'amount_due'       => (float) $m->amount_due,
                'payment_due_date' => $m->payment_due_date?->toDateString(),
                'payment_status'   => $m->payment_status,
                'created_at'       => $m->created_at,
                'is_overdue'       => $m->payment_status === 'overdue',
            ]);

        $totalPending = $movements->sum('amount_due');

        return response()->json([
            'movements'     => $movements,
            'total_pending' => $totalPending,
        ]);
    }

    public function markPaid(Request $request, StockMovement $movement)
    {
        if (!in_array($movement->payment_status, ['pending', 'overdue'])) {
            return response()->json(['message' => 'Ce mouvement est déjà marqué comme payé.'], 422);
        }

        $movement->update([
            'payment_status' => 'paid',
            'paid_at'        => now(),
        ]);

        return response()->json([
            'message'  => 'Paiement enregistré.',
            'movement' => $movement->fresh(),
        ]);
    }
}
