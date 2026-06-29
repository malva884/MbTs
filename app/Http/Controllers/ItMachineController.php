<?php

namespace App\Http\Controllers;

use App\Models\ItMachine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ItMachineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ItMachine::with('location');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $machines = $query->orderBy('name')->paginate($request->itemsPerPage ?? 25);

        return response()->json($machines);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:it_machines',
            'description' => 'nullable|string',
            'location_id' => 'nullable|uuid|exists:it_locations,id',
            'status' => 'required|in:Active,Inactive,Maintenance',
        ]);

        $machine = ItMachine::create($validated);

        return response()->json($machine, 201);
    }

    public function show(ItMachine $machine): JsonResponse
    {
        $machine->load('location', 'assignments.asset');

        return response()->json($machine);
    }

    public function update(Request $request, ItMachine $machine): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:it_machines,code,' . $machine->id,
            'description' => 'nullable|string',
            'location_id' => 'nullable|uuid|exists:it_locations,id',
            'status' => 'required|in:Active,Inactive,Maintenance',
        ]);

        $machine->update($validated);

        return response()->json($machine);
    }

    public function destroy(ItMachine $machine): JsonResponse
    {
        $machine->delete();

        return response()->json(null, 204);
    }
}
