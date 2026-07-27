<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Web\Backend\Admin\Settlement\SettlementStoreRequest;

class SettlementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Settlement::with('user')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user', function ($row) {
                    return $row->user->name . ' (' . $row->user->user_type . ')';
                })
                ->addColumn('status', function ($row) {
                    $badges = [
                        'pending' => 'bg-warning',
                        'processing' => 'bg-info',
                        'completed' => 'bg-success',
                        'failed' => 'bg-danger'
                    ];
                    $badge = $badges[$row->status] ?? 'bg-secondary';
                    return '<span class="badge ' . $badge . '">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    if ($row->status == 'pending') {
                        return '<button onclick="processSettlement(' . $row->id . ')" class="btn btn-sm btn-primary">Process</button>';
                    }
                    return '-';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('backend.layout.settlements.index');
    }

    /**
     * Process a settlement (Move pending to balance)
     */
    public function process($id)
    {
        DB::beginTransaction();
        try {
            $settlement = Settlement::findOrFail($id);
            if ($settlement->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Settlement is already processed or failed.']);
            }

            // Perform settlement in wallet
            WalletService::settle($settlement->user_id, $settlement->amount);

            $settlement->status = 'completed';
            $settlement->settled_at = now();
            $settlement->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Settlement completed successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * View users with pending balances to create a settlement
     */
    public function pendingUsers()
    {
        $wallets = Wallet::where('pending_balance', '>', 0)->with('user')->get();
        return view('backend.layout.settlements.pending_users', compact('wallets'));
    }

    /**
     * Create a new settlement record for a user
     */
    public function create(SettlementStoreRequest $request)
    {
        try {
            $wallet = Wallet::where('user_id', $request->user_id)->firstOrFail();
            $settlement = new Settlement();
            $settlement->user_id = $request->user_id;
            $settlement->amount = $request->amount;
            $settlement->status = 'pending';
            $settlement->save();

            flash()->success('Settlement record created.');
            return redirect()->route('admin.settlements.index');
        } catch (Exception $e) {
            flash()->error('Failed to create settlement.');
            return back();
        }
    }
}
