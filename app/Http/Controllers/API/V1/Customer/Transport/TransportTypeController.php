<?php

namespace App\Http\Controllers\API\V1\Customer\Transport;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\Transport\TransportTypeCategoryResource;
use App\Http\Resources\V1\Customer\Transport\TransportTypeResource;
use App\Models\TransportType;
use App\Models\TransportTypeCategory;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransportTypeController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            // filter by transportation type category
            $query = TransportType::with('category')->where('is_active', true);
            if ($request->has('transport_type_category_id')) {
                $query->where('transport_type_category_id', $request->transport_type_category_id);
            }
            $transportTypes = $query->get();

            return $this->success(TransportTypeResource::collection($transportTypes), 'Transport types retrieved successfully');
        } catch (Exception $e) {
            Log::error('Transport Types List Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve transport types' . $e->getMessage(), 500);
        }
    }

    public function getTransportTypeCategories()
    {
        try {
            $categories = TransportTypeCategory::get();
            return $this->success(TransportTypeCategoryResource::collection($categories), 'Transport type categories retrieved successfully');
        } catch (Exception $e) {
            Log::error('Transport Type Categories List Error: ' . $e->getMessage());
            return $this->error([], 'Failed to retrieve transport type categories' . $e->getMessage(), 500);
        }
    }
}
