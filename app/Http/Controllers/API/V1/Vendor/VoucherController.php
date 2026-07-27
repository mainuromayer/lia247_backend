<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Requests\API\V1\Vendor\Voucher\VoucherStoreRequest;
use App\Http\Resources\API\V1\VoucherResource;

class VoucherController extends Controller
{
    use ApiResponse;

    /**
     * List Vendor's Vouchers
     */
    public function index(Request $request)
    {
        $query = Voucher::where('is_active', true)
            ->where('vendor_id', Auth::id());

        if ($request->query('discount_type')) {
            $query->where('discount_type', $request->query('discount_type'));
        }

        $vouchers = $query->latest()->get();
        return $this->success(VoucherResource::collection($vouchers), 'Vendor vouchers retrieved');
    }

    /**
     * Create a Vendor Voucher
     */
    public function store(VoucherStoreRequest $request)
    {
        try {
            $voucher = new Voucher();
            $voucher->code = $request->code;
            $voucher->type = 'food';
            $voucher->app_type = 'food';
            $voucher->discount_type = $request->discount_type;
            $voucher->discount_amount = $request->discount_type === 'buy_one_get_one' ? 0 : $request->discount_amount;
            $voucher->min_order_amount = $request->min_order_amount;
            $voucher->valid_from = $request->valid_from;
            $voucher->valid_until = $request->valid_until;
            $voucher->is_active = true;
            $voucher->created_by_id = Auth::id();
            $voucher->owner_role = 'vendor';
            $voucher->vendor_id = Auth::id();
            $voucher->save();

            return $this->success(new VoucherResource($voucher), 'Vendor voucher created successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to create voucher');
        }
    }

    /**
     * Delete Voucher
     */
    public function destroy($id)
    {
        $voucher = Voucher::where('id', $id)->where('vendor_id', Auth::id())->first();
        if ($voucher && $voucher->delete()) {
            return $this->success([], 'Voucher deleted');
        }
        return $this->error([], 'Voucher not found or deletion failed');
    }
}
