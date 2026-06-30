<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private WebhookService $webhookService) {}

    public function addToDepot(
        Product $product,
        int $quantity,
        ?string $notes,
        int $userId,
        ?int $supplierId = null,
        ?float $amountDue = null,
        ?string $paymentDueDate = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $notes, $userId, $supplierId, $amountDue, $paymentDueDate) {
            $product->increment('stock_depot', $quantity);
            $product->refresh();

            // Calcul échéance et statut paiement
            $paymentStatus = null;
            $computedDueDate = null;

            if ($amountDue !== null && $amountDue > 0) {
                $supplier = $supplierId ? Supplier::find($supplierId) : null;

                if ($paymentDueDate) {
                    $computedDueDate = $paymentDueDate;
                } elseif ($supplier) {
                    $days = $supplier->paymentTermsDays();
                    $computedDueDate = $days ? now()->addDays($days)->toDateString() : null;
                }

                $paymentStatus = $computedDueDate ? 'pending' : 'paid';
            }

            $movement = StockMovement::create([
                'product_id'          => $product->id,
                'user_id'             => $userId,
                'supplier_id'         => $supplierId,
                'type'                => 'depot_entry',
                'quantity'            => $quantity,
                'location_from'       => null,
                'location_to'         => 'depot',
                'notes'               => $notes,
                'stock_depot_after'   => $product->stock_depot,
                'stock_boutique_after'=> $product->stock_boutique,
                'amount_due'          => $amountDue ?: null,
                'payment_due_date'    => $computedDueDate,
                'payment_status'      => $paymentStatus,
            ]);

            $this->webhookService->dispatch('stock.moved', [
                'movement_type'        => 'depot_entry',
                'product_id'           => $product->id,
                'name'                 => $product->name,
                'quantity'             => $quantity,
                'stock_depot_after'    => $product->stock_depot,
                'stock_boutique_after' => $product->stock_boutique,
            ]);

            $this->checkAndFireAlerts($product);

            return $movement;
        });
    }

    public function transferToBoutique(Product $product, int $quantity, ?string $notes, int $userId): StockMovement
    {
        if ($product->stock_depot < $quantity) {
            throw new \Exception("Stock dépôt insuffisant. Disponible: {$product->stock_depot}");
        }

        return DB::transaction(function () use ($product, $quantity, $notes, $userId) {
            $product->decrement('stock_depot', $quantity);
            $product->increment('stock_boutique', $quantity);
            $product->refresh();

            $movement = StockMovement::create([
                'product_id'           => $product->id,
                'user_id'              => $userId,
                'type'                 => 'transfer',
                'quantity'             => $quantity,
                'location_from'        => 'depot',
                'location_to'          => 'boutique',
                'notes'                => $notes,
                'stock_depot_after'    => $product->stock_depot,
                'stock_boutique_after' => $product->stock_boutique,
            ]);

            $this->webhookService->dispatch('stock.moved', [
                'movement_type'        => 'transfer',
                'product_id'           => $product->id,
                'name'                 => $product->name,
                'quantity'             => $quantity,
                'stock_depot_after'    => $product->stock_depot,
                'stock_boutique_after' => $product->stock_boutique,
            ]);

            $this->checkAndFireAlerts($product);

            return $movement;
        });
    }

    public function setInitialBoutiqueStock(Product $product, int $quantity, ?string $notes, int $userId): StockMovement
    {
        return DB::transaction(function () use ($product, $quantity, $notes, $userId) {
            $product->update(['stock_boutique' => $quantity]);
            $product->refresh();

            $movement = StockMovement::create([
                'product_id'           => $product->id,
                'user_id'              => $userId,
                'type'                 => 'boutique_initial',
                'quantity'             => $quantity,
                'location_from'        => null,
                'location_to'          => 'boutique',
                'notes'                => $notes ?? 'Inventaire initial boutique',
                'stock_depot_after'    => $product->stock_depot,
                'stock_boutique_after' => $product->stock_boutique,
            ]);

            $this->checkAndFireAlerts($product);

            return $movement;
        });
    }

    private function checkAndFireAlerts(Product $product): void
    {
        if ($product->isBoutiqueAlerted()) {
            $this->webhookService->dispatch('stock.alert', [
                'product_id'    => $product->id,
                'name'          => $product->name,
                'location'      => 'boutique',
                'current_stock' => $product->stock_boutique,
                'threshold'     => $product->alert_threshold_boutique,
            ]);
        }

        if ($product->isDepotAlerted()) {
            $this->webhookService->dispatch('stock.alert', [
                'product_id'    => $product->id,
                'name'          => $product->name,
                'location'      => 'depot',
                'current_stock' => $product->stock_depot,
                'threshold'     => $product->alert_threshold_depot,
            ]);
        }
    }
}
