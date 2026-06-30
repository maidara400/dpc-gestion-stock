<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(
            Supplier::withCount('products')->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'contact'       => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:1000',
            'payment_terms' => 'nullable|in:immediate,30j,60j,90j,custom',
        ]);

        return response()->json(Supplier::create($data), 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'contact'       => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:1000',
            'active'        => 'sometimes|boolean',
            'payment_terms' => 'nullable|in:immediate,30j,60j,90j,custom',
        ]);

        $supplier->update($data);
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un fournisseur associé à des produits.',
            ], 422);
        }

        $supplier->delete();
        return response()->json(['message' => 'Fournisseur supprimé.']);
    }
}
