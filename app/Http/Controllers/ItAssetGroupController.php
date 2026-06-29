<?php

namespace App\Http\Controllers;

use App\Models\ItAsset;
use App\Models\ItAssetGroup;
use Illuminate\Http\Request;

class ItAssetGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ItAssetGroup::query();

        if ($request->brand) {
            $query->where('brand', $request->brand);
        }

        if ($request->model) {
            $query->where('model', $request->model);
        }

        $groups = $query->get();

        return response()->json($groups);
    }

    public function show($id)
    {
        $group = ItAssetGroup::findOrFail($id);

        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'min_stock' => 'required|integer|min:0',
        ]);

        $group = ItAssetGroup::findOrFail($id);
        $group->update($validated);

        return response()->json($group);
    }

    public function updateOrCreate(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'min_stock' => 'required|integer|min:0',
        ]);

        $group = ItAssetGroup::updateOrCreate(
            ['brand' => $validated['brand'], 'model' => $validated['model']],
            ['min_stock' => $validated['min_stock']]
        );

        return response()->json($group);
    }

    public function suppliers(Request $request)
    {
        $assets = ItAsset::where('disabled', false)
            ->where('brand', $request->brand)
            ->where('model', $request->model)
            ->whereHas('suppliers')
            ->with(['suppliers:id,name,contact_person,email,phone', 'category:id,name', 'location:id,name'])
            ->get();

        $grouped = [];

        foreach ($assets as $asset) {
            foreach ($asset->suppliers as $supplier) {
                if (!isset($grouped[$supplier->id])) {
                    $grouped[$supplier->id] = [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'contact_person' => $supplier->contact_person,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'categories' => [],
                        'locations' => [],
                        'product_links' => [],
                        'unit_costs' => [],
                    ];
                }
                if ($asset->category) {
                    $grouped[$supplier->id]['categories'][] = $asset->category->name;
                }
                if ($asset->location) {
                    $grouped[$supplier->id]['locations'][] = $asset->location->name;
                }
                if ($supplier->pivot->product_link) {
                    $grouped[$supplier->id]['product_links'][] = $supplier->pivot->product_link;
                }
                if ($supplier->pivot->unit_cost !== null) {
                    $grouped[$supplier->id]['unit_costs'][] = $supplier->pivot->unit_cost;
                }
            }
        }

        $result = collect(array_values($grouped))->map(function ($supplier) {
            $supplier['categories'] = collect($supplier['categories'])->unique()->values()->implode(', ');
            $supplier['locations'] = collect($supplier['locations'])->unique()->values()->implode(', ');
            $supplier['product_link'] = collect($supplier['product_links'])->first();
            $supplier['unit_cost'] = collect($supplier['unit_costs'])->min();
            unset($supplier['product_links'], $supplier['unit_costs']);

            return $supplier;
        })->values();

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'min_stock' => 'required|integer|min:0',
        ]);

        $group = ItAssetGroup::create($validated);

        return response()->json($group, 201);
    }
}
