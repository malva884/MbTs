<?php

namespace App\Http\Controllers;

use App\Models\ItAsset;
use App\Models\ItAssetGroup;
use App\Models\ItTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = ItAsset::with(['category', 'location', 'assignments.employee', 'networkDevice'])
            ->where('disabled', false);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('serial_number', 'like', '%' . $request->search . '%')
                    ->orWhere('asset_tag', 'like', '%' . $request->search . '%')
                    ->orWhere('brand', 'like', '%' . $request->search . '%')
                    ->orWhere('model', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->brand || $request->model) {
            if ($request->brand) {
                $query->where('brand', $request->brand);
            }
            if ($request->model) {
                $query->where('model', $request->model);
            }
        }

        if ($request->grouped === 'true') {
            // Group by brand and model
            $grouped = $query->select('brand', 'model', DB::raw('COUNT(*) as total_quantity'), DB::raw('SUM(quantity) as total_stock'))
                ->groupBy('brand', 'model')
                ->orderBy('brand')
                ->orderBy('model')
                ->get();

            // Get individual assets for each group
            $grouped->each(function ($group) use ($request) {
                $group->assets = ItAsset::with(['category', 'location', 'assignments.employee', 'networkDevice'])
                    ->where('brand', $group->brand)
                    ->where('model', $group->model)
                    ->where('disabled', false)
                    ->when($request->search, function ($q) use ($request) {
                        $q->where(function ($q) use ($request) {
                            $q->where('serial_number', 'like', '%' . $request->search . '%')
                                ->orWhere('asset_tag', 'like', '%' . $request->search . '%');
                        });
                    })
                    ->when($request->category_id, function ($q) use ($request) {
                        $q->where('category_id', $request->category_id);
                    })
                    ->when($request->location_id, function ($q) use ($request) {
                        $q->where('location_id', $request->location_id);
                    })
                    ->when($request->status, function ($q) use ($request) {
                        $q->where('status', $request->status);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
                $group->available_count = $group->assets->where('status', 'Available')->count();

                $assetGroup = ItAssetGroup::where('brand', $group->brand)->where('model', $group->model)->first();
                $group->min_stock = $assetGroup ? $assetGroup->min_stock : 0;
                $group->group_id = $assetGroup ? $assetGroup->id : null;
            });

            return response()->json([
                'data' => $grouped,
                'total' => $grouped->count(),
            ]);
        }

        $assets = $query->orderBy('created_at', 'desc')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|uuid|exists:it_categories,id',
            'location_id' => 'nullable|uuid|exists:it_locations,id',
            'serial_number' => 'nullable|string|unique:it_assets,serial_number',
            'asset_tag' => 'nullable|string|unique:it_assets,asset_tag',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'product_link' => 'nullable|url',
            'unit_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'quantity' => 'integer|min:1',
            'status' => 'in:Available,Assigned,In Repair,Retired,Lost',
            'notes' => 'nullable|string',
        ]);

        $asset = ItAsset::create($validated);

        // Create IN transaction
        ItTransaction::create([
            'asset_id' => $asset->id,
            'type' => 'In',
            'to_location_id' => $asset->location_id,
            'performed_by' => Auth::id(),
            'notes' => 'Asset created',
        ]);

        // Check for low stock notification
        self::checkLowStock($asset);

        return response()->json($asset, 201);
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|uuid|exists:it_categories,id',
            'location_id' => 'nullable|uuid|exists:it_locations,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|uuid|exists:it_suppliers,id',
            'purchase_date' => 'nullable|date',
            'product_link' => 'nullable|url',
            'unit_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'serial_numbers' => 'required|array',
            'serial_numbers.*' => 'string',
            'asset_tags' => 'nullable|array',
            'asset_tags.*' => 'string',
            'notes' => 'nullable|string',
        ]);

        $assets = [];
        DB::transaction(function () use ($validated, &$assets) {
            foreach ($validated['serial_numbers'] as $index => $serial) {
                $asset = ItAsset::create([
                    'category_id' => $validated['category_id'],
                    'location_id' => $validated['location_id'] ?? null,
                    'serial_number' => $serial,
                    'asset_tag' => isset($validated['asset_tags']) && isset($validated['asset_tags'][$index]) ? $validated['asset_tags'][$index] : null,
                    'brand' => $validated['brand'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'purchase_date' => $validated['purchase_date'] ?? null,
                    'product_link' => $validated['product_link'] ?? null,
                    'unit_cost' => $validated['unit_cost'] ?? null,
                    'warranty_expiry' => $validated['warranty_expiry'] ?? null,
                    'quantity' => 1,
                    'status' => 'Available',
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Create supplier relationship if supplier_id is provided
                if (!empty($validated['supplier_id'])) {
                    DB::table('it_asset_supplier')->insert([
                        'asset_id' => $asset->id,
                        'supplier_id' => $validated['supplier_id'],
                        'purchase_date' => $validated['purchase_date'] ?? null,
                        'unit_cost' => $validated['unit_cost'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                ItTransaction::create([
                    'asset_id' => $asset->id,
                    'type' => 'In',
                    'to_location_id' => $validated['location_id'] ?? null,
                    'performed_by' => Auth::id(),
                    'notes' => 'Bulk asset creation',
                ]);

                $assets[] = $asset;
            }

            // Check for low stock notification after bulk creation
            if (!empty($validated['brand']) && !empty($validated['model'])) {
                self::checkLowStock($assets[0]);
            }
        });

        return response()->json(['assets' => $assets, 'count' => count($assets)], 201);
    }

    public function show($id)
    {
        $asset = ItAsset::with(['category', 'location', 'assignments.employee', 'assignments.assignedBy', 'networkDevice', 'transactions.fromLocation', 'transactions.toLocation', 'transactions.performedBy', 'suppliers'])
            ->findOrFail($id);

        return response()->json($asset);
    }

    public function update(Request $request, $id)
    {
        $asset = ItAsset::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|uuid|exists:it_categories,id',
            'location_id' => 'nullable|uuid|exists:it_locations,id',
            'serial_number' => 'nullable|string|unique:it_assets,serial_number,' . $id,
            'asset_tag' => 'nullable|string|unique:it_assets,asset_tag,' . $id,
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'product_link' => 'nullable|url',
            'unit_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date|after:purchase_date',
            'quantity' => 'sometimes|integer|min:1',
            'status' => 'in:Available,Assigned,In Repair,Retired,Lost',
            'notes' => 'nullable|string',
            'disabled' => 'sometimes|boolean',
        ]);

        $asset->update($validated);

        return response()->json($asset);
    }

    public function destroy($id)
    {
        $asset = ItAsset::findOrFail($id);
        $asset->disabled = true;
        $asset->save();

        return response()->json(['message' => 'Asset disabled']);
    }

    public function employeeAssets($employeeId)
    {
        $assets = ItAsset::with(['category', 'assignments' => function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId)->where('status', 'Active');
        }])
            ->whereHas('assignments', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId)->where('status', 'Active');
            })
            ->get();

        return response()->json($assets);
    }

    public function myAssets()
    {
        $employee = \App\Models\HrEmployee::where('user_id', Auth::id())->first();

        if (!$employee) {
            return response()->json(['count' => 0, 'items' => []]);
        }

        $assets = ItAsset::with(['category', 'assignments' => function ($q) use ($employee) {
            $q->where('employee_id', $employee->id)->where('status', 'Active');
        }])
            ->whereHas('assignments', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id)->where('status', 'Active');
            })
            ->get();

        return response()->json(['count' => $assets->count(), 'items' => $assets]);
    }

    public function attachSupplier(Request $request, $assetId)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:it_suppliers,id',
            'unit_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'order_reference' => 'nullable|string|max:255',
            'product_link' => 'nullable|url',
            'notes' => 'nullable|string',
        ]);

        $asset = ItAsset::findOrFail($assetId);
        $asset->suppliers()->syncWithoutDetaching([
            $validated['supplier_id'] => [
                'unit_cost' => $validated['unit_cost'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'order_reference' => $validated['order_reference'] ?? null,
                'product_link' => $validated['product_link'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        ]);

        return response()->json($asset->load('suppliers'), 201);
    }

    public function detachSupplier($assetId, $supplierId)
    {
        $asset = ItAsset::findOrFail($assetId);
        $asset->suppliers()->detach($supplierId);

        return response()->json($asset->load('suppliers'));
    }

    public function printLabel(Request $request)
    {
        $asset = ItAsset::with(['category', 'assignments.employee'])->findOrFail($request->id);
        $assignment = $asset->assignments->where('status', 'Active')->first();
        $employee = $assignment ? $assignment->employee : null;

        $html = view('it.asset-label', [
            'asset' => $asset,
            'employee' => $employee,
            'assigned_at' => $assignment ? $assignment->assigned_at : null,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function getBrands()
    {
        $brands = ItAsset::where('disabled', false)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->select('brand')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->filter();

        return response()->json($brands);
    }

    public static function checkLowStock($asset)
    {
        $assetGroup = ItAssetGroup::where('brand', $asset->brand)
            ->where('model', $asset->model)
            ->first();

        if ($assetGroup && $assetGroup->min_stock > 0) {
            $availableCount = ItAsset::where('brand', $asset->brand)
                ->where('model', $asset->model)
                ->where('status', 'Available')
                ->where('disabled', false)
                ->count();

            if ($availableCount <= $assetGroup->min_stock) {
                $users = \App\Models\Utility::users_notify(['it_low_stock']);

                if (!empty($users)) {
                    $info = [
                        'brand' => $asset->brand,
                        'model' => $asset->model,
                        'available_count' => $availableCount,
                        'min_stock' => $assetGroup->min_stock,
                    ];

                    \Illuminate\Support\Facades\Mail::send('emails/email_it_low_stock', compact('info'), function ($message) use ($users, $asset) {
                        $message
                            ->to($users)
                            ->subject("Stock Minimo Raggiunto: {$asset->brand} {$asset->model}");
                    });
                }
            }
        }
    }
}
