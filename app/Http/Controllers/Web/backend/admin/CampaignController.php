<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CampaignController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Business::with('user')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('vendor_name', function ($data) {
                    return $data->user->name ?? 'N/A';
                })
                ->addColumn('status', function ($data) {
                    $status = $data->free_delivery;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="toggleFreeDelivery(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('type', function ($data) {
                    return ucfirst($data->free_delivery_type ?? 'N/A');
                })
                ->addColumn('action', function ($data) {
                    return '<a class="btn btn-sm btn-info" href="' . route('admin.vendor.edit', ['id' => $data->user_id]) . '">
                                <i class="fa-solid fa-gear"></i> Manage
                            </a>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.layout.campaigns.index');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $business = Business::findOrFail($id);
        $business->free_delivery = !$business->free_delivery;
        
        // Default to admin if no type is set when enabling
        if ($business->free_delivery && empty($business->free_delivery_type)) {
            $business->free_delivery_type = 'admin';
        }
        
        $business->save();

        return response()->json([
            'success' => true,
            'message' => 'Free Delivery Status Updated.',
            'data' => $business,
        ]);
    }
}
