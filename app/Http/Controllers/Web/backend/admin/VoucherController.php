<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Web\Backend\Admin\Voucher\VoucherStoreRequest;
use App\Http\Requests\Web\Backend\Admin\Voucher\VoucherUpdateRequest;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $type = $request->query('type');
            $query = Voucher::query();
            
            if ($type) {
                $query->where('type', $type);
            }

            if ($request->owner_role) {
                $query->where('owner_role', $request->owner_role);
            }
            
            $data = $query->with('vendor')->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($data) {
                    $status = $data->is_active;
                    return '<div class="form-check form-switch mb-2">
                                <input class="form-check-input" onclick="showStatusChangeAlert(' . $data->id . ')" type="checkbox" ' . ($status ? 'checked' : '') . '>
                            </div>';
                })
                ->addColumn('discount', function ($data) {
                    if ($data->discount_type === 'percentage') {
                        return $data->discount_amount . '%';
                    } elseif ($data->discount_type === 'buy_one_get_one') {
                        return '<span class="badge bg-success">Buy 1 Get 1</span>';
                    }
                    return '$' . $data->discount_amount;
                })
                ->addColumn('valid_until', function ($data) {
                    return $data->valid_until ? $data->valid_until->format('Y-m-d h:i A') : 'N/A';
                })
                ->addColumn('type', function ($data) {
                    return ucfirst($data->type);
                })
                ->addColumn('owner', function ($data) {
                    if ($data->owner_role === 'vendor') {
                        return '<span class="badge bg-info">Vendor: ' . ($data->vendor->name ?? 'N/A') . '</span>';
                    }
                    return '<span class="badge bg-primary text-white">Admin</span>';
                })
                ->addColumn('action', function ($data) {
                    $editButton = ' <a class="btn btn-sm btn-info" href="' . route('admin.vouchers.edit', ['id' => $data->id]) . '">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>';
                    $deleteButton = ' <button type="button" onclick="deleteVoucher(' . $data->id . ')" class="btn btn-sm btn-danger">
                                        <i class="fa-regular fa-circle-xmark"></i>
                                     </button>';
                    return '<div>' . $editButton . ' ' . $deleteButton . '</div>';
                })
                ->rawColumns(['status', 'discount', 'action', 'valid_until', 'type', 'owner'])
                ->make(true);
        }

        return view('backend.layout.vouchers.index');
    }

    public function create(Request $request)
    {
        $type = $request->query('type');
        return view('backend.layout.vouchers.create', compact('type'));
    }

    public function store(VoucherStoreRequest $request)
    {
        try {
            $voucher = new Voucher();
            $voucher->code = $request->code;
            $voucher->type = $request->type ?? 'food';
            $voucher->discount_type = $request->discount_type;
            $voucher->discount_amount = $request->discount_type === 'buy_one_get_one' ? 0 : $request->discount_amount;
            $voucher->min_order_amount = $request->min_order_amount;
            $voucher->valid_from = $request->valid_from;
            $voucher->valid_until = $request->valid_until;
            $voucher->is_active = $request->is_active ?? true;
            $voucher->created_by_id = auth()->id();
            $voucher->owner_role = 'admin';
            $voucher->save();

            flash()->success('Voucher created successfully.');
            return redirect()->route('admin.vouchers.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $voucher = Voucher::findOrFail($id);
        return view('backend.layout.vouchers.edit', compact('voucher'));
    }

    public function update(VoucherUpdateRequest $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        try {
            $voucher->code = $request->code;
            $voucher->type = $request->type ?? 'food';
            $voucher->discount_type = $request->discount_type;
            $voucher->discount_amount = $request->discount_type === 'buy_one_get_one' ? 0 : $request->discount_amount;
            $voucher->min_order_amount = $request->min_order_amount;
            $voucher->valid_from = $request->valid_from;
            $voucher->valid_until = $request->valid_until;
            $voucher->is_active = $request->is_active ?? true;
            $voucher->created_by_id = auth()->id();
            $voucher->owner_role = 'admin';
            $voucher->save();

            flash()->success('Voucher updated successfully.');
            return redirect()->route('admin.vouchers.index');
        } catch (Exception $e) {
            flash()->error('Something went wrong!' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $voucher = Voucher::find($request->id);
        if ($voucher && $voucher->delete()) {
            return response()->json(['success' => true, 'message' => 'Voucher deleted successfully'], 200);
        }
        return response()->json(['success' => false, 'message' => 'Deletion failed.'], 500);
    }

    public function status(int $id): JsonResponse
    {
        $data = Voucher::findOrFail($id);
        $data->is_active = !$data->is_active;
        $data->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Updated Successfully.',
            'data' => $data,
        ]);
    }
}
