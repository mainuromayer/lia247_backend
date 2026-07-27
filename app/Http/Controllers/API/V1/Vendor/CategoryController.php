<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vendor\Category\CategoryStoreRequest;
use App\Http\Requests\V1\Vendor\Category\CategoryUpdateRequest;
use App\Http\Resources\V1\Category\CategoryCollection;
use App\Http\Resources\V1\Category\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $user = Auth::user();
            $categories = Category::where('user_id', $user->id)
                ->orWhere('is_admin_created', true)
                ->get();
            return $this->success(new CategoryCollection($categories), 'Categories fetched successfully', 200);
        } catch (Exception $e) {
            return $this->error([], 'Failed to fetch categories'. $e->getMessage(), 500);
        }
    }

    public function store(CategoryStoreRequest $request)
    {
        try {
            $user = Auth::user();
            $category = new Category();
            $category->user_id = $request->user_id ?? $user->id;
            $category->name = $request->name;
            $category->status = 'active';
            $category->save();
            return $this->success(new CategoryResource($category), 'Category created successfully', 201);
        } catch (Exception $e) {
            return $this->error([], 'Failed to create category'. $e->getMessage(), 500);
        }
    }

    public function update(CategoryUpdateRequest $request, $slug)
    {
        try {
            $category = Category::where('slug', $slug)->first();
            if (!$category) {
                return $this->error([], 'Category not found', 404);
            }
            $category->name = $request->name;
            $category->save();
            return $this->success(new CategoryResource($category), 'Category updated successfully', 200);
        } catch (Exception $e) {
            return $this->error([], 'Failed to update category'. $e->getMessage(), 500);
        }
    }

    public function destroy($slug)
    {
        try {
            $category = Category::where('slug', $slug)->first();
            if (!$category) {
                return $this->error([], 'Category not found', 404);
            }
            $category->delete();
            return $this->success([], 'Category deleted successfully', 200);
        } catch (Exception $e) {
            return $this->error([], 'Failed to delete category'. $e->getMessage(), 500);
        }
    }
}
