<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Customer\AdResource;
use App\Models\Ad;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;

class AdController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $ads = Ad::where('is_active', true)->get();
            return $this->success(AdResource::collection($ads), 'Ads retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve ads: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $ad = Ad::where('id', $id)->where('is_active', true)->first();
            if (!$ad) {
                return $this->error([], 'Ad not found');
            }
            $ad->click_count += 1;
            $ad->save();
            return $this->success(new AdResource($ad), 'Ad retrieved successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to retrieve ad: ' . $e->getMessage());
        }
    }
}
