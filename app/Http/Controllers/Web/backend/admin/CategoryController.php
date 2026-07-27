<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::where('is_admin_created', true)->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    $url = $data->image ? asset($data->image) : asset('backend/assets/images/default-category.png');
                    return '<img src="' . $url . '" alt="category image" class="rounded-circle" width="50" height="50">';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->status === 'active';
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('action', function ($data) {
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.categories.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';
                    $deleteButton = ' <button type="button" onclick="deleteCategory(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                    return '<div>' . $editButton . ' ' . $deleteButton . '</div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }

        return view('backend.layout.category.index');
    }

    public function create()
    {
        return view('backend.layout.category.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:204800',
        ]);

        try {
            $category = new Category();
            $category->user_id = auth()->id();
            $category->name = $request->name;
            
            if ($request->hasFile('image')) {
                $category->image = Helper::fileUpload($request->file('image'), 'category');
            }

            $category->status = 'active';
            $category->is_admin_created = true;
            $category->save();

            flash()->success('Category created successfully.');
            return redirect()->route('admin.categories.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('backend.layout.category.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:204800',
        ]);

        try {
            $category->user_id = auth()->id();
            $category->name = $request->name;

            if ($request->hasFile('image')) {
                if ($category->image) {
                    Helper::deleteFile($category->image);
                }
                $category->image = Helper::fileUpload($request->file('image'), 'category');
            }
            $category->save();

            flash()->success('Category updated successfully.');
            return redirect()->route('admin.categories.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $category = Category::find($request->id);
        if ($category && $category->delete()) {
            return response()->json(['success' => true, 'message' => 'Category deleted successfully'], 200);
        }
        return response()->json(['success' => false, 'message' => 'Deletion failed.'], 500);
    }

    public function status(int $id): JsonResponse
    {
        $data = Category::findOrFail($id);
        $data->status = $data->status === 'active' ? 'inactive' : 'active';
        $data->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
            'data' => $data,
        ]);
    }
}
