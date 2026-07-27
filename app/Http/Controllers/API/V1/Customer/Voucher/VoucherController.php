<?php

namespace App\Http\Controllers\API\V1\Customer\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Customer\ApplyVoucherRequest;
use App\Http\Resources\V1\Voucher\VoucherCollection;
use App\Http\Resources\V1\Voucher\VoucherResource;
use App\Models\Voucher;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $type = $request->query('type');
            $query = Voucher::where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', now());
                });

            if ($type) {
                $query->where('type', $type);
            }

            if ($request->query('app_type')) {
                $query->where('app_type', $request->query('app_type'));
            }

            if ($request->query('owner_role')) {
                $query->where('owner_role', $request->query('owner_role'));
            }

            if ($request->query('vendor_id')) {
                $query->where('vendor_id', $request->query('vendor_id'));
            }

            if ($request->query('discount_type')) {
                $query->where('discount_type', $request->query('discount_type'));
            }

            $vouchers = $query->latest()->get();
            return $this->success(VoucherCollection::make($vouchers), 'Vouchers fetched successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to fetch vouchers: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $voucher = Voucher::find($id);
            if (!$voucher) {
                return $this->error([], 'Voucher not found', 404);
            }
            return $this->success(VoucherResource::make($voucher), 'Voucher fetched successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to fetch voucher: ' . $e->getMessage(), 500);
        }
    }

    public function apply(ApplyVoucherRequest $request)
    {
        try {
            $query = Voucher::where('code', $request->voucher_code)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', now());
                });

            if ($request->type) {
                $query->where('type', $request->type);
            }

            $voucher = $query->first();

            if (!$voucher) {
                return $this->error([], 'Invalid or expired voucher code for this service', 404);
            }

            return $this->success(VoucherResource::make($voucher), 'Voucher applied successfully');
        } catch (Exception $e) {
            return $this->error([], 'Failed to apply voucher: ' . $e->getMessage(), 500);
        }
    }
}
