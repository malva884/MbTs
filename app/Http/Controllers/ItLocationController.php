<?php

namespace App\Http\Controllers;

use App\Models\ItLocation;
use Illuminate\Http\Request;

class ItLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = ItLocation::with('children')->where('disabled', false);

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $locations = $query->orderBy('name')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Office,Warehouse,Data Center',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:it_locations,id',
        ]);

        $location = ItLocation::create($validated);

        return response()->json($location, 201);
    }

    public function show($id)
    {
        $location = ItLocation::with('children', 'assets')->findOrFail($id);

        return response()->json($location);
    }

    public function update(Request $request, $id)
    {
        $location = ItLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:Office,Warehouse,Data Center',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:it_locations,id',
            'disabled' => 'sometimes|boolean',
        ]);

        $location->update($validated);

        return response()->json($location);
    }

    public function destroy($id)
    {
        $location = ItLocation::findOrFail($id);
        $location->disabled = true;
        $location->save();

        return response()->json(['message' => 'Location disabled']);
    }
}
