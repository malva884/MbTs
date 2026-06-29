<?php

namespace App\Http\Controllers;

use App\Models\ItTransaction;
use Illuminate\Http\Request;

class ItTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = ItTransaction::with(['asset.category', 'fromLocation', 'toLocation', 'performedBy']);

        if ($request->asset_id) {
            $query->join('it_assets', 'it_transactions.asset_id', '=', 'it_assets.id')
                ->where(function ($q) use ($request) {
                    $q->where('it_assets.serial_number', 'like', '%' . $request->asset_id . '%')
                        ->orWhere('it_assets.asset_tag', 'like', '%' . $request->asset_id . '%');
                });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->from_location_id) {
            $query->where('from_location_id', $request->from_location_id);
        }

        if ($request->to_location_id) {
            $query->where('to_location_id', $request->to_location_id);
        }

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('date', 'desc')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:it_assets,id',
            'type' => 'required|in:In,Out,Transfer,Maintenance,Return,Retire',
            'from_location_id' => 'nullable|uuid|exists:it_locations,id',
            'to_location_id' => 'nullable|uuid|exists:it_locations,id',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['performed_by'] = auth()->id();

        $transaction = ItTransaction::create($validated);

        return response()->json($transaction, 201);
    }

    public function show($id)
    {
        $transaction = ItTransaction::with(['asset.category', 'fromLocation', 'toLocation', 'performedBy'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    public function update(Request $request, $id)
    {
        $transaction = ItTransaction::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|in:In,Out,Transfer,Maintenance,Return,Retire',
            'from_location_id' => 'nullable|uuid|exists:it_locations,id',
            'to_location_id' => 'nullable|uuid|exists:it_locations,id',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = ItTransaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted']);
    }
}
