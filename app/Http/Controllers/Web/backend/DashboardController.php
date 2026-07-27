<?php

namespace App\Http\Controllers\Web\backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Relation;
use App\Models\Spark;
use App\Models\Vip;
use App\Models\User;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function dashboard()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please login first');
            }

            $now = now();
            $isAdmin = $user->is_admin ?? false;



            // Get Greeting Message
            $greeting = $this->getGreeting();

            // API Response
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard data loaded',
                    'data' => [
                        'user_name' => $user->name ?? 'User',
                        'user_avatar' => $user->avatar ?? null,
                        'user_email' => $user->email ?? 'No email',
                        'is_admin' => $isAdmin,
                        'greeting' => $greeting,
                    ]
                ]);
            }

            // Get Statistics (Countable numbers)
            $stats = [
                'total_customers' => User::where('user_type', 'customer')->count(),
                'total_vendors'   => User::where('user_type', 'vendor')->count(),
                'total_delivery'  => User::where('user_type', 'delivery_man')->count(),
                'total_riders'    => User::where('user_type', 'rider')->count(),
                'total_revenue'   => Order::whereIn('status', ['delivered', 'completed'])->sum('total_amount'),
                'total_orders'    => Order::count(),
                'today_orders'    => Order::whereDate('created_at', Carbon::today())->count(),
                'today_revenue'   => Order::whereDate('created_at', Carbon::today())->whereIn('status', ['delivered', 'completed'])->sum('total_amount'),
            ];

            // Order Summary by status
            $orderSummary = Order::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
            
            // Ensure all statuses exist even if 0
            $statuses = ['pending', 'accepted', 'preparing', 'ready', 'picked', 'delivered', 'completed', 'canceled'];
            foreach($statuses as $s) {
                if(!isset($orderSummary[$s])) $orderSummary[$s] = 0;
            }

            // Recent Transactions
            $recentTransactions = WalletTransaction::with(['wallet.user'])
                ->latest()
                ->limit(10)
                ->get();

            // CHART DATA PREPARATION
            
            // 1. Monthly Revenue (Last 12 Months)
            $monthlyRevenue = Order::whereIn('status', ['delivered', 'completed'])
                ->whereYear('created_at', date('Y'))
                ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_amount) as amount'))
                ->groupBy('month')
                ->pluck('amount', 'month')->toArray();
            
            $monthlyChart = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthlyChart[] = ['label' => date('M', mktime(0, 0, 0, $i, 1)), 'value' => (float)($monthlyRevenue[$i] ?? 0)];
            }

            // 2. Weekly Revenue (Last 7 Days)
            $weeklyChart = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $amount = Order::whereIn('status', ['delivered', 'completed'])
                    ->whereDate('created_at', $date->toDateString())
                    ->sum('total_amount');
                $weeklyChart[] = ['label' => $date->format('D'), 'value' => (float)$amount];
            }

            // 3. Yearly Revenue (Last 5 Years)
            $yearlyChart = [];
            $currentYear = (int)date('Y');
            for ($i = 4; $i >= 0; $i--) {
                $year = $currentYear - $i;
                $amount = Order::whereIn('status', ['delivered', 'completed'])
                    ->whereYear('created_at', $year)
                    ->sum('total_amount');
                $yearlyChart[] = ['label' => (string)$year, 'value' => (float)$amount];
            }

            $allChartData = [
                'weekly' => $weeklyChart,
                'monthly' => $monthlyChart,
                'yearly' => $yearlyChart
            ];

            // Recent Orders
            $recentOrders = Order::with(['user', 'vendor'])
                ->latest()
                ->limit(10)
                ->get();

            // Web View Response
            return view('backend.dashboard', [
                'user' => $user,
                'isAdmin' => $isAdmin,
                'greeting' => $greeting,
                'stats' => $stats,
                'orderSummary' => $orderSummary,
                'recentTransactions' => $recentTransactions,
                'recentOrders' => $recentOrders,
                'allChartData' => json_encode($allChartData),
            ]);

        } catch (Exception $e) {
            Log::error('Dashboard Error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('login')->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Get greeting message based on current time
     * 
     * @return array
     */
    private function getGreeting()
    {
        try {
            $hour = now()->hour;

            if ($hour < 12) {
                return [
                    'message' => 'Good Morning',
                    'type' => 'morning'
                ];
            }

            if ($hour < 17) {
                return [
                    'message' => 'Good Afternoon',
                    'type' => 'afternoon'
                ];
            }

            return [
                'message' => 'Good Evening',
                'type' => 'evening'
            ];
        } catch (Exception $e) {
            return [
                'message' => 'Welcome',
                'type' => 'default'
            ];
        }
    }
    
}