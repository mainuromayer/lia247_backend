<?php

namespace App\Http\Controllers\Web\backend\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Voucher;
use App\Services\CommissionService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    /**
     * Order list with DataTables (server-side).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Order::with(['user', 'vendor', 'deliveryMan', 'items'])
                ->latest();

            // Filter by status if provided
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('order_no', fn($row) => $row->order_no ?? '—')
                ->addColumn('customer', fn($row) => $row->user->name ?? 'N/A')
                ->addColumn('vendor', fn($row) => $row->vendor->name ?? 'N/A')
                ->addColumn('delivery_man', fn($row) => $row->deliveryMan ? $row->deliveryMan->name : '<span class="text-muted">Unassigned</span>')
                ->addColumn('items_summary', function ($row) {
                    return $row->items->map(function ($item) {
                        return $item->item_name . ' ($' . number_format($item->price, 2) . ' x ' . $item->quantity . ')';
                    })->implode('<br>');
                })
                ->addColumn('total', fn($row) => '$' . number_format($row->total_amount, 2))
                ->addColumn('payment_gateway', fn($row) => ucfirst($row->payment_gateway ?? '—'))
                ->addColumn('status', function ($row) {
                    $badges = [
                        'pending'    => 'bg-warning text-dark',
                        'accepted'   => 'bg-info',
                        'preparing'  => 'bg-primary',
                        'ready'      => 'bg-secondary',
                        'picked'     => 'bg-dark',
                        'delivered'  => 'bg-success',
                        'completed'  => 'bg-success',
                        'canceled'   => 'bg-danger',
                    ];
                    $badge = $badges[$row->status] ?? 'bg-secondary';
                    $label = $row->status === 'accepted' ? 'Distribution' : ucfirst($row->status);
                    return '<span class="badge ' . $badge . '">' . $label . '</span>';
                })
                ->addColumn('created_at', fn($row) => $row->created_at->format('d M Y, h:i A'))
                ->addColumn('action', fn($row) => '
                    <a href="' . route('admin.orders.show', $row->id) . '" class="btn btn-sm btn-info">
                        <i class="fa-solid fa-eye"></i> View
                    </a>
                ')
                ->rawColumns(['status', 'action', 'delivery_man', 'items_summary'])
                ->make(true);
        }

        return view('backend.layout.orders.index');
    }

    /**
     * Order detail view.
     */
    public function show($id)
    {
        $order = Order::with([
            'user',
            'vendor',
            'deliveryMan',
            'items.item',
            'items.variant',
        ])->findOrFail($id);

        return view('backend.layout.orders.show', compact('order'));
    }

    /**
     * Update order status from admin panel.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,preparing,ready,picked,delivered,completed,canceled',
        ]);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;

        DB::beginTransaction();
        try {
            $order->status = $request->status;

            // Distribution logic if confirmed (accepted)
            if ($request->status === 'accepted' && $oldStatus !== 'accepted') {
                $foodPrice = $order->subtotal;
                $deliveryFee = $order->delivery_fee;
                $discount = $order->discount_amount ?? 0;
                
                $voucherOwner = 'admin';
                if ($order->voucher_id) {
                    $voucher = Voucher::find($order->voucher_id);
                    $voucherOwner = $voucher ? $voucher->owner_role : 'admin';
                }

                // Calculate Split
                $split = CommissionService::calculateFoodSplit($foodPrice, $deliveryFee, $discount, $voucherOwner);

                $order->commission_amount = $split['admin_amount'];
                
                // Wallet Distribution (Direct Credit to Vendor)
                WalletService::credit($order->vendor_id, $split['vendor_amount'], 'order_payment', $order, 'Payment for order #' . $order->order_no);
            }

            $order->save();
            DB::commit();

            $statusLabel = $request->status === 'accepted' ? 'Distribution' : ucfirst($request->status);
            return redirect()->back()->with('success', 'Order status updated to ' . $statusLabel);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}
