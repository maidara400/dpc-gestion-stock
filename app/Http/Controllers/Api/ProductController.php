<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('alert') && $request->alert === 'true') {
            $query->where(function ($q) {
                $q->whereRaw('stock_boutique <= alert_threshold_boutique')
                  ->orWhereRaw('stock_depot <= alert_threshold_depot');
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        $products = $query->where('active', true)->orderBy('name')->get();

        return response()->json($products->map(fn($p) => $this->formatProduct($p)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'stock_depot' => 'integer|min:0',
            'stock_boutique' => 'integer|min:0',
            'alert_threshold_depot' => 'integer|min:0',
            'alert_threshold_boutique' => 'integer|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $product = Product::create($data);
        $product->load(['category', 'supplier']);

        return response()->json($this->formatProduct($product), 201);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'supplier']);
        return response()->json($this->formatProduct($product));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'alert_threshold_depot' => 'sometimes|integer|min:0',
            'alert_threshold_boutique' => 'sometimes|integer|min:0',
            'purchase_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                \Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $product->update($data);
        $product->load(['category', 'supplier']);

        return response()->json($this->formatProduct($product));
    }

    public function destroy(Product $product)
    {
        $product->update(['active' => false]);
        return response()->json(['message' => 'Produit archivé avec succès.']);
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'supplier' => $product->supplier,
            'stock_depot' => $product->stock_depot,
            'stock_boutique' => $product->stock_boutique,
            'alert_threshold_depot' => $product->alert_threshold_depot,
            'alert_threshold_boutique' => $product->alert_threshold_boutique,
            'purchase_price' => (float) $product->purchase_price,
            'sale_price' => (float) $product->sale_price,
            'active' => $product->active,
            'depot_alerted' => $product->isDepotAlerted(),
            'boutique_alerted' => $product->isBoutiqueAlerted(),
            'image_url' => $product->image_path
                ? asset('storage/' . $product->image_path)
                : null,
        ];
    }
}
