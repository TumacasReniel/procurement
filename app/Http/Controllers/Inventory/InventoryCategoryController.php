<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ListDropdown;
use Illuminate\Http\Request;

class InventoryCategoryController extends Controller
{
    private const CLASSIFICATION = 'Item Category';

    public function index()
    {
        return response()->json(
            ListDropdown::where('classification', self::CLASSIFICATION)
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('list_dropdowns', 'name')
                    ->where('classification', self::CLASSIFICATION),
            ],
        ]);

        $category = ListDropdown::create([
            'name'           => $request->name,
            'classification' => self::CLASSIFICATION,
            'type'           => 'n/a',
            'color'          => 'n/a',
            'others'         => 'n/a',
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return response()->json(['data' => $category, 'message' => 'Category created.'], 201);
    }

    public function update(Request $request, $id)
    {
        $category = ListDropdown::where('classification', self::CLASSIFICATION)->findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('list_dropdowns', 'name')
                    ->where('classification', self::CLASSIFICATION)
                    ->ignore($id),
            ],
        ]);

        $category->update([
            'name'      => $request->name,
            'is_active' => $request->boolean('is_active', (bool) $category->is_active),
        ]);

        return response()->json(['data' => $category, 'message' => 'Category updated.']);
    }

    public function destroy($id)
    {
        $category = ListDropdown::where('classification', self::CLASSIFICATION)->findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
