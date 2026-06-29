<?php

namespace App\Http\Controllers;

use App\Models\ItSupplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ItSupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ItSupplier::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('contact_person', 'like', "%{$request->search}%");
        }

        $suppliers = $query->orderBy('name')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'disabled' => 'boolean',
        ]);

        $supplier = ItSupplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function show($id): JsonResponse
    {
        $supplier = ItSupplier::with('assets')->findOrFail($id);

        return response()->json($supplier);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $supplier = ItSupplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'disabled' => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy($id): JsonResponse
    {
        $supplier = ItSupplier::findOrFail($id);
        $supplier->delete();

        return response()->json(null, 204);
    }
}
