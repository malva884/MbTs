<?php

namespace App\Http\Controllers;

use App\Models\ItCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ItCategory::with('children')->where('disabled', false);

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('name')
            ->paginate($request->itemsPerPage ?? 25);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:it_categories,id',
            'require_label' => 'sometimes|boolean',
        ]);

        $category = ItCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = ItCategory::with('children', 'assets')->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = ItCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|uuid|exists:it_categories,id',
            'disabled' => 'sometimes|boolean',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = ItCategory::findOrFail($id);
        $category->disabled = true;
        $category->save();

        return response()->json(['message' => 'Category disabled']);
    }
}
