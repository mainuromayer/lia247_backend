<?php

namespace App\Http\Controllers\API\V1\Customer\Category;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Category\CategoryCollection;
use App\Models\Category;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use ApiResponse;

    public function fixedCategories()
    {
        try {
            $categories = Category::where('is_admin_created', true)->get();
            return $this->success(new CategoryCollection($categories), 'Categories fetched successfully');
        } catch (Exception $exception) {
            Log::error('Category fetch failed: ' . $exception->getMessage());
            return $this->error([], 'Failed to fetch categories: ' . $exception->getMessage(), 500);
        }
    }
}