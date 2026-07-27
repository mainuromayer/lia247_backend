<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\TransportTypeCategory;
use Illuminate\Http\Request;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;

class TransportTypeCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = TransportTypeCategory::latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($data) {
                    $status = $data->status;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.transport_type_categories.edit', ['id' => $category_id = $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';
                    $deleteButton = ' <button type="button" onclick="deleteCategory(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                    return '<div class="d-flex">' . $editButton . ' ' . $deleteButton . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.admin.transport_type_category.index');
    }

    public function create()
    {
        return view('backend.admin.transport_type_category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:transport_type_categories,name',
        ]);

        try {
            TransportTypeCategory::create([
                'name' => $request->name,
                'status' => true,
            ]);
            flash()->success('Category created successfully.');
            return redirect()->route('admin.transport_type_categories.index');
        } catch (Exception $e) {
            flash()->error('Failed to create category: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        $category = TransportTypeCategory::findOrFail($id);
        return view('backend.admin.transport_type_category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:transport_type_categories,name,' . $id,
        ]);

        try {
            $category = TransportTypeCategory::findOrFail($id);
            $category->update([
                'name' => $request->name,
            ]);
            flash()->success('Category updated successfully.');
            return redirect()->route('admin.transport_type_categories.index');
        } catch (Exception $e) {
            flash()->error('Failed to update category: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $category = TransportTypeCategory::findOrFail($request->id);
            $category->delete();
            return response()->json(['success' => true, 'message' => 'Category deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete category']);
        }
    }

    public function status(int $id): JsonResponse
    {
        try {
            $category = TransportTypeCategory::findOrFail($id);
            $category->status = !$category->status;
            $category->save();
            return response()->json(['success' => true, 'message' => 'Status updated successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }
}
