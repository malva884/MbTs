<?php

namespace App\Http\Controllers;

use App\Models\ItAsset;
use App\Models\ItAssetAssignment;
use App\Models\ItTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ItAssetAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = ItAssetAssignment::with(['asset.category', 'assignable', 'assignedBy']);

        if ($request->asset_id) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->assignable_type) {
            $query->where('assignable_type', $request->assignable_type);
        }

        if ($request->assignable_id) {
            $query->where('assignable_id', $request->assignable_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $assignments = $query->orderBy('assigned_at', 'desc')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:it_assets,id',
            'assignable_type' => 'required|in:App\Models\HrEmployee,App\Models\ItMachine',
            'assignable_id' => 'required|uuid',
            'assigned_quantity' => 'integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $asset = ItAsset::with('category')->findOrFail($validated['asset_id']);

        if ($asset->quantity < $validated['assigned_quantity']) {
            return response()->json(['message' => 'Insufficient quantity available'], 400);
        }

        $requireLabel = $asset->category->require_label ?? false;

        DB::transaction(function () use ($validated, $asset) {
            $assignment = ItAssetAssignment::create([
                'asset_id' => $validated['asset_id'],
                'assignable_type' => $validated['assignable_type'],
                'assignable_id' => $validated['assignable_id'],
                'assigned_by' => Auth::id(),
                'assigned_quantity' => $validated['assigned_quantity'],
                'status' => 'Active',
                'notes' => $validated['notes'],
            ]);

            // Update asset quantity
            $asset->quantity -= $validated['assigned_quantity'];
            if ($asset->quantity === 0) {
                $asset->status = 'Assigned';
            }
            $asset->save();

            // Create OUT transaction
            ItTransaction::create([
                'asset_id' => $asset->id,
                'type' => 'Out',
                'from_location_id' => $asset->location_id,
                'performed_by' => Auth::id(),
                'notes' => 'Assigned to ' . ($validated['assignable_type'] === 'App\Models\ItMachine' ? 'machine' : 'employee'),
            ]);

            // Check for low stock notification
            \App\Http\Controllers\ItAssetController::checkLowStock($asset);
        });

        $response = ['message' => 'Asset assigned successfully'];

        if ($requireLabel) {
            $response['print_label_url'] = '/it/assets/print/label?id=' . $asset->id;
        }

        return response()->json($response, 201);
    }

    public function return(Request $request, $id)
    {
        $assignment = ItAssetAssignment::findOrFail($id);

        $validated = $request->validate([
            'returned_quantity' => 'required|integer|min:1|max:' . $assignment->assigned_quantity,
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($assignment, $validated) {
            $assignment->returned_at = now();
            $assignment->returned_quantity = $validated['returned_quantity'];
            $assignment->status = 'Returned';
            $assignment->notes = $validated['notes'] ?? null;
            $assignment->save();

            // Update asset quantity
            $asset = $assignment->asset;
            $asset->quantity += $validated['returned_quantity'];
            $asset->status = 'Available';
            $asset->save();

            // Create RETURN transaction
            ItTransaction::create([
                'asset_id' => $asset->id,
                'type' => 'Return',
                'to_location_id' => $asset->location_id,
                'performed_by' => Auth::id(),
                'notes' => 'Returned from employee',
            ]);

            // Check for low stock notification (though this is unlikely when returning)
            \App\Http\Controllers\ItAssetController::checkLowStock($asset);
        });

        return response()->json(['message' => 'Asset returned successfully']);
    }

    public function show($id)
    {
        $assignment = ItAssetAssignment::with(['asset.category', 'assignable', 'assignedBy'])->findOrFail($id);

        return response()->json($assignment);
    }
}
