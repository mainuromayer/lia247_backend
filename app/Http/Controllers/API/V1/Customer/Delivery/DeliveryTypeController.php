<?php

namespace App\Http\Controllers\API\V1\Customer\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOption;
use App\Http\Resources\V1\Customer\Delivery\DeliveryOptionResource;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class DeliveryTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $deliveryOptions = DeliveryOption::where('is_active', true)->get();

            return $this->success(DeliveryOptionResource::collection($deliveryOptions), 'Delivery options retrieved successfully');
        } catch (Exception $exception) {
            Log::error('Delivery Options List Error: ' . $exception->getMessage());
            return $this->error([], 'Failed to retrieve delivery options', 500);
        }
    }
}
