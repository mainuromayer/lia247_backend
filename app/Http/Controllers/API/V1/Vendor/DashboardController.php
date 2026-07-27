<?php

namespace App\Http\Controllers\API\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\API\V1\Vendor\OrderResource;

class DashboardController extends Controller
{

    use ApiResponse;

    public function index()
    {
        $vendorId = auth()->id();

        // Query to get orders that contain at least one item from this vendor
        $vendorOrdersQuery = Order::whereHas('items.item', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        });

        $deliveredOrder = (clone $vendorOrdersQuery)->where('status', 'delivered')->count();
        $cancelOrder = (clone $vendorOrdersQuery)->whereIn('status', ['canceled', 'cancelled'])->count();
        $refundOrder = (clone $vendorOrdersQuery)->where('status', 'refunded')->count();

        $totalCustomer = (clone $vendorOrdersQuery)->distinct('user_id')->count('user_id');

        return $this->success([
            'delivered_order' => $deliveredOrder,
            'cancel_order' => $cancelOrder,
            'refunded_order' => $refundOrder,
            'total_customer' => $totalCustomer,
        ], 'Dashboard Data Retrive Successfully');
    }

    public function analyticData()
    {
        $vendorId = auth()->id();

        // Yearly Graph Analytics (Monthly Sell summation)
        $currentYear = date('Y');

        $monthlySells = OrderItem::whereHas('item', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })
            ->whereYear('created_at', $currentYear)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_price) as total_sell')
            )
            ->groupBy('month')
            ->pluck('total_sell', 'month')
            ->toArray();

        $monthlyGraphData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 1));
            $monthlyGraphData[] = [
                'month' => $monthName,
                'sell' => (float) ($monthlySells[$i] ?? 0)
            ];
        }

        // Category-wise analytics
        $categorySells = OrderItem::whereHas('item', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })
            ->join('items', 'order_items.item_id', '=', 'items.id')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category',
                DB::raw('SUM(order_items.total_price) as total_sell')
            )
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $categoryData = [];
        foreach ($categorySells as $cs) {
            $categoryData[] = [
                'category' => $cs->category,
                'sell' => (float) $cs->total_sell
            ];
        }

        return $this->success([
            'monthly_analytics_data' => $monthlyGraphData, // Monthly
            'category_analytics_data' => $categoryData // Category
        ], 'Dashboard Data Retrive Successfully');
    }

    public function recentOrders(Request $request)
    {
        $vendorId = auth()->id();
        $search = $request->input('search');
        $sort = $request->input('sort', 'latest');

        // Querying Orders instead of OrderItems to get unique orders
        $query = Order::with([
            'user',
            'items' => function ($q) use ($vendorId) {
                $q->whereHas('item', function ($iq) use ($vendorId) {
                    $iq->where('vendor_id', $vendorId);
                })->with('item');
            }
        ])
            ->whereHas('items.item', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            });

        // Search Filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', '%' . $search . '%')
                    ->orWhere('receiver_name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Sorting Filter
        if ($sort == 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $orders = $query->limit(10)->get();

        return $this->success(OrderResource::collection($orders), 'Recent Orders Data Retrive Successfully');
    }
}
