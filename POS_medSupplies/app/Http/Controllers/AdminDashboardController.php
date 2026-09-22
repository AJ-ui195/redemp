<?php

namespace App\Http\Controllers;

use App\Models\FreeSampleRequest;
use App\Models\ItemList;
use App\Models\Product;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\InventoryLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\ItemEditLog;
use App\Support\ItemInventoryLinker;
use App\Models\Shift;
use App\Models\VoidRequest;
use App\Models\RequestStatus;
use App\Models\NotificationRead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /** Default timezone for sales date boundaries (match cashier/store location) */
    private const SALES_TIMEZONE = 'Asia/Manila';

    /**
     * Get start and end of today in Asia/Manila, as UTC timestamps for DB queries.
     * This ensures admin "today" matches the same calendar day the cashier sees.
     */
    private function getTodayStartEndUTC(): array
    {
        $today = Carbon::now(self::SALES_TIMEZONE);
        $startOfDay = $today->copy()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();
        return [
            $startOfDay->utc(),
            $endOfDay->utc(),
        ];
    }

    public function index()
    {
        $overview = $this->getDashboardOverviewData();
        $financial = $this->getDashboardFinancialData();
        $monitoring = $this->getDashboardMonitoringData($overview['stockLevels']);
        $todayReport = $this->getTodaySoldItemsReportData();

        return view('admin.index', array_merge($overview, $financial, $monitoring, $todayReport));
    }

    /**
     * @return array<string, mixed>
     */
    private function getDashboardOverviewData(): array
    {
        $todaySales = $this->calculateSales('today');
        $weeklySales = $this->calculateSales('week');
        $monthlySales = $this->calculateSales('month');
        $dailySales = $this->getDailySales(7);
        $topProducts = $this->getTopSellingProducts(10);
        $stockLevels = $this->getStockLevels();

        $lowStockCount = Product::query()
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', 10)
            ->count();

        $expiringCount = Product::query()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>', Carbon::now())
            ->where('expiration_date', '<=', Carbon::now()->addDays(30))
            ->count();

        $activeUsers = User::count();
        $users = User::orderBy('created_at', 'desc')->get();
        $notificationCount = $lowStockCount + $expiringCount;
        $pendingPOs = Schema::hasTable('purchase_orders')
            ? PurchaseOrder::where('status', 'pending')->count()
            : 0;

        $stockValue = (float) Product::query()->sum(DB::raw('selling_price * quantity'));
        $totalItems = (float) Product::query()->sum('quantity');

        $allSampleRequests = collect();
        if (Schema::hasTable('free_sample_requests')) {
            $allSampleRequests = FreeSampleRequest::query()
                ->with(['requestedBy', 'items.product', 'requestStatus'])
                ->latest()
                ->get();
        }

        $sampleRequestStats = [
            'total' => $allSampleRequests->count(),
            'pending' => $allSampleRequests->filter(fn ($r) => $r->status === 'pending')->count(),
            'approved' => $allSampleRequests->filter(fn ($r) => $r->status === 'approved')->count(),
            'rejected' => $allSampleRequests->filter(fn ($r) => $r->status === 'rejected')->count(),
        ];

        return compact(
            'todaySales', 'weeklySales', 'monthlySales', 'dailySales', 'topProducts',
            'stockLevels', 'lowStockCount', 'expiringCount', 'activeUsers', 'users',
            'notificationCount', 'pendingPOs', 'stockValue', 'totalItems',
            'allSampleRequests', 'sampleRequestStats'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getDashboardFinancialData(): array
    {
        $totalRevenue = $this->calculateRevenue('month');
        $totalExpenses = $this->calculateExpenses('month');
        $netProfit = $totalRevenue - $totalExpenses;

        $lastMonthRevenue = $this->calculateRevenue('last_month');
        $lastMonthExpenses = $this->calculateExpenses('last_month');
        $lastMonthProfit = $lastMonthRevenue - $lastMonthExpenses;

        $revenueChange = $lastMonthRevenue > 0
            ? (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0.0;
        $expensesChange = $lastMonthExpenses > 0
            ? (($totalExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100
            : 0.0;
        $profitChange = $lastMonthProfit != 0
            ? (($netProfit - $lastMonthProfit) / abs($lastMonthProfit)) * 100
            : 0.0;

        $monthlyFinancials = $this->getMonthlyFinancials(6);

        return compact(
            'totalRevenue', 'totalExpenses', 'netProfit',
            'revenueChange', 'expensesChange', 'profitChange', 'monthlyFinancials'
        );
    }

    /**
     * @param array<string, mixed> $stockLevels
     * @return array<string, mixed>
     */
    private function getDashboardMonitoringData(array $stockLevels): array
    {
        if (Schema::hasTable('cashier_shifts')) {
            $activeCashiers = DB::table('cashier_shifts')
                ->whereNull('ended_at')
                ->distinct('cashier_user_id')
                ->count('cashier_user_id');
        } elseif (Schema::hasTable('shifts')) {
            $activeCashiers = Shift::where('status', 'active')
                ->distinct('user_id')
                ->count('user_id');
        } else {
            $activeCashiers = 0;
        }

        [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
        $todayTransactions = Sale::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $pendingRefunds = 0;
        if (Schema::hasColumn('sales', 'status')) {
            $pendingRefunds = Sale::where('status', 'refunded')->count();
        }

        try {
            [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
            $todaySalesAmount = Sale::sumAmount(
                Sale::completed()->whereBetween('created_at', [$todayStart, $todayEnd])
            );
        } catch (\Exception $e) {
            \Log::error('Error calculating today sales amount: ' . $e->getMessage());
            $todaySalesAmount = 0.0;
        }

        $averageTransaction = $todayTransactions > 0 ? $todaySalesAmount / $todayTransactions : 0.0;
        $totalProducts = Product::count();
        $outOfStockCount = $stockLevels['counts']['out_of_stock'] ?? 0;
        $expiringSoon = Product::query()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>', Carbon::now())
            ->where('expiration_date', '<=', Carbon::now()->addDays(30))
            ->count();

        $itemEditLogs = collect();
        try {
            if (Schema::hasTable('item_edit_logs')) {
                $itemEditLogs = ItemEditLog::latest()->limit(25)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Error loading item edit logs: ' . $e->getMessage());
        }

        return compact(
            'activeCashiers', 'todayTransactions', 'pendingRefunds', 'averageTransaction',
            'totalProducts', 'outOfStockCount', 'expiringSoon', 'itemEditLogs'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getTodaySoldItemsReportData(): array
    {
        $todaySoldItems = $this->getTodaySoldItems();
        $todaySoldItemsTotal = $this->calculateTodaySoldItemsTotal();
        return compact('todaySoldItems', 'todaySoldItemsTotal');
    }

    private function calculateTodaySoldItemsTotal(): float
    {
        try {
            [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
            $todaySalesForTotal = Sale::completed()
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error fetching today\'s sales: ' . $e->getMessage());
            return 0.0;
        }

        $total = 0.0;
        /** @var \Illuminate\Support\Collection<int, Sale> $todaySalesForTotal */
        foreach ($todaySalesForTotal as $sale) {
            $total += $this->getSaleAmountExcludingVoided($sale);
        }
        return (float) $total;
    }

    private function getSaleAmountExcludingVoided(Sale $sale): float
    {
        try {
            return (float) ($sale->amount ?? 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function calculateSales($period)
    {
        $query = Sale::completed();

        switch ($period) {
            case 'today':
                [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
                $query->whereBetween('created_at', [$todayStart, $todayEnd]);
                break;
            case 'week':
                $tz = Carbon::now(self::SALES_TIMEZONE);
                $query->whereBetween('created_at', [
                    $tz->copy()->startOfWeek()->utc(),
                    $tz->copy()->endOfWeek()->utc()
                ]);
                break;
            case 'month':
                $tz = Carbon::now(self::SALES_TIMEZONE);
                $query->whereBetween('created_at', [
                    $tz->copy()->startOfMonth()->utc(),
                    $tz->copy()->endOfMonth()->utc()
                ]);
                break;
            default:
                return 0;
        }

        return Sale::sumAmount($query);
    }

    private function getDailySales($days = 7)
    {
        // Get daily sales for the last N days
        // Exclude voided sales - amount field is already adjusted for approved voided items
        $sales = [];
        $labels = [];
        
        // Use Asia/Manila timezone for all date calculations
        $timezone = 'Asia/Manila';
        
        for ($i = $days - 1; $i >= 0; $i--) {
            // Get date in Asia/Manila timezone
            $date = Carbon::now($timezone)->subDays($i);
            
            // Get start and end of day in Asia/Manila timezone
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            // Convert to UTC for database query (database stores in UTC)
            $startOfDayUTC = $startOfDay->utc();
            $endOfDayUTC = $endOfDay->utc();
            
            // Only count completed sales (excludes voided sales)
            // Amount field is already adjusted when void requests are approved
            // Query using UTC timestamps to match database storage
            $daySales = Sale::sumAmount(
                Sale::completed()->whereBetween('created_at', [$startOfDayUTC, $endOfDayUTC])
            );
            
            $sales[] = (float) $daySales;
            // Format day label using Asia/Manila timezone
            $labels[] = $date->format('D'); // Mon, Tue, etc.
        }
        
        return [
            'labels' => $labels,
            'data' => $sales
        ];
    }

    private function calculateRevenue($period)
    {
        $query = Sale::completed();
        
        switch ($period) {
            case 'today':
                [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
                $query->whereBetween('created_at', [$todayStart, $todayEnd]);
                break;
            case 'week':
                $tz = Carbon::now(self::SALES_TIMEZONE);
                $query->whereBetween('created_at', [
                    $tz->copy()->startOfWeek()->utc(),
                    $tz->copy()->endOfWeek()->utc()
                ]);
                break;
            case 'month':
                $tz = Carbon::now(self::SALES_TIMEZONE);
                $query->whereBetween('created_at', [
                    $tz->copy()->startOfMonth()->utc(),
                    $tz->copy()->endOfMonth()->utc()
                ]);
                break;
            case 'last_month':
                $tz = Carbon::now(self::SALES_TIMEZONE)->subMonth();
                $query->whereBetween('created_at', [
                    $tz->copy()->startOfMonth()->utc(),
                    $tz->copy()->endOfMonth()->utc()
                ]);
                break;
            default:
                return 0;
        }
        
        return Sale::sumAmount($query);
    }

    private function calculateExpenses($period)
    {
        if (! Schema::hasTable('purchase_orders')) {
            return 0;
        }

        // Calculate expenses based on purchase orders that have been received
        // Use actual_delivery_date if available, otherwise use order_date, fallback to created_at
        $query = PurchaseOrder::whereIn('status', ['received', 'approved']);
        
        switch ($period) {
            case 'today':
                $query->where(function($q) {
                    $q->whereDate('actual_delivery_date', Carbon::today())
                      ->orWhere(function($q2) {
                          $q2->whereNull('actual_delivery_date')
                             ->whereDate('order_date', Carbon::today());
                      })
                      ->orWhere(function($q3) {
                          $q3->whereNull('actual_delivery_date')
                             ->whereNull('order_date')
                             ->whereDate('created_at', Carbon::today());
                      });
                });
                break;
            case 'week':
                $query->where(function($q) {
                    $q->whereBetween('actual_delivery_date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                      ->orWhere(function($q2) {
                          $q2->whereNull('actual_delivery_date')
                             ->whereBetween('order_date', [
                                 Carbon::now()->startOfWeek(),
                                 Carbon::now()->endOfWeek()
                             ]);
                      })
                      ->orWhere(function($q3) {
                          $q3->whereNull('actual_delivery_date')
                             ->whereNull('order_date')
                             ->whereBetween('created_at', [
                                 Carbon::now()->startOfWeek(),
                                 Carbon::now()->endOfWeek()
                             ]);
                      });
                });
                break;
            case 'month':
                $query->where(function($q) {
                    $q->whereMonth('actual_delivery_date', Carbon::now()->month)
                      ->whereYear('actual_delivery_date', Carbon::now()->year)
                      ->orWhere(function($q2) {
                          $q2->whereNull('actual_delivery_date')
                             ->whereMonth('order_date', Carbon::now()->month)
                             ->whereYear('order_date', Carbon::now()->year);
                      })
                      ->orWhere(function($q3) {
                          $q3->whereNull('actual_delivery_date')
                             ->whereNull('order_date')
                             ->whereMonth('created_at', Carbon::now()->month)
                             ->whereYear('created_at', Carbon::now()->year);
                      });
                });
                break;
            case 'last_month':
                $lastMonth = Carbon::now()->subMonth();
                $query->where(function($q) use ($lastMonth) {
                    $q->whereMonth('actual_delivery_date', $lastMonth->month)
                      ->whereYear('actual_delivery_date', $lastMonth->year)
                      ->orWhere(function($q2) use ($lastMonth) {
                          $q2->whereNull('actual_delivery_date')
                             ->whereMonth('order_date', $lastMonth->month)
                             ->whereYear('order_date', $lastMonth->year);
                      })
                      ->orWhere(function($q3) use ($lastMonth) {
                          $q3->whereNull('actual_delivery_date')
                             ->whereNull('order_date')
                             ->whereMonth('created_at', $lastMonth->month)
                             ->whereYear('created_at', $lastMonth->year);
                      });
                });
                break;
            default:
                return 0;
        }
        
        // Sum total_amount from purchase orders
        $total = $query->sum('total_amount');
        return $total ? (float) $total : 0;
    }

    private function getTopSellingProducts($limit = 10)
    {
        if (Schema::hasTable('sale_items')) {
            $rows = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->when(
                    Schema::hasColumn('sales', 'sale_status_id'),
                    function ($q) {
                        $completedId = DB::table('sale_statuses')->where('name', 'completed')->value('id');
                        $q->where('sales.sale_status_id', $completedId);
                    },
                    fn ($q) => $q->where('sales.status', 'completed')
                )
                ->select(
                    'products.id as product_id',
                    'products.name',
                    DB::raw('SUM(sale_items.quantity - COALESCE(sale_items.voided_quantity, 0)) as total_quantity'),
                    DB::raw('SUM((sale_items.quantity - COALESCE(sale_items.voided_quantity, 0)) * sale_items.unit_price) as total_revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();

            $labels = [];
            $quantities = [];
            $revenues = [];
            $topProducts = [];

            foreach ($rows as $row) {
                $labels[] = $row->name;
                $quantities[] = (int) $row->total_quantity;
                $revenues[] = (float) $row->total_revenue;
                $topProducts[] = [
                    'product_id' => $row->product_id,
                    'name' => $row->name,
                    'total_quantity' => (int) $row->total_quantity,
                    'total_revenue' => (float) $row->total_revenue,
                ];
            }

            return [
                'labels' => $labels,
                'quantities' => $quantities,
                'revenues' => $revenues,
                'products' => $topProducts,
            ];
        }

        // Legacy JSON items column
        $sales = Sale::completed()
            ->whereNotNull('items')
            ->get();

        $productStats = [];

        foreach ($sales as $sale) {
            $items = $sale->items ?? [];

            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $productName = $item['name'] ?? 'Unknown Product';
                $quantity = (int) ($item['quantity'] ?? 0);
                $total = (float) ($item['total'] ?? 0);

                $key = $productId ?: $productName;

                if (! isset($productStats[$key])) {
                    $productStats[$key] = [
                        'product_id' => $productId,
                        'name' => $productName,
                        'total_quantity' => 0,
                        'total_revenue' => 0,
                    ];
                }

                $productStats[$key]['total_quantity'] += $quantity;
                $productStats[$key]['total_revenue'] += $total;
            }
        }

        usort($productStats, fn ($a, $b) => $b['total_quantity'] - $a['total_quantity']);
        $topProducts = array_slice($productStats, 0, $limit);

        $labels = [];
        $quantities = [];
        $revenues = [];

        foreach ($topProducts as $product) {
            $labels[] = $product['name'];
            $quantities[] = $product['total_quantity'];
            $revenues[] = $product['total_revenue'];
        }

        return [
            'labels' => $labels,
            'quantities' => $quantities,
            'revenues' => $revenues,
            'products' => $topProducts,
        ];
    }

    private function getStockLevels()
    {
        $allItems = Product::query()->select('id', 'quantity')->get();

        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;

        foreach ($allItems as $item) {
            $quantity = (float) ($item->quantity ?? 0);

            if ($quantity <= 0) {
                $outOfStock++;
            } elseif ($quantity <= 10) {
                $lowStock++;
            } else {
                $inStock++;
            }
        }

        $total = $inStock + $lowStock + $outOfStock;

        return [
            'labels' => ['In Stock', 'Low Stock', 'Out of Stock'],
            'data' => [$inStock, $lowStock, $outOfStock],
            'counts' => [
                'in_stock' => $inStock,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
                'total' => $total
            ],
            'percentages' => $total > 0 ? [
                round(($inStock / $total) * 100, 1),
                round(($lowStock / $total) * 100, 1),
                round(($outOfStock / $total) * 100, 1)
            ] : [0, 0, 0]
        ];
    }

    private function getTodaySoldItems()
    {
        [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();

        if (Schema::hasTable('sale_items')) {
            $rows = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->when(
                    Schema::hasColumn('sales', 'sale_status_id'),
                    function ($q) {
                        $completedId = DB::table('sale_statuses')->where('name', 'completed')->value('id');
                        $q->where('sales.sale_status_id', $completedId);
                    },
                    fn ($q) => $q->where('sales.status', 'completed')
                )
                ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
                ->orderByDesc('sales.created_at')
                ->select(
                    'products.name',
                    'sale_items.quantity',
                    'sale_items.voided_quantity',
                    'sale_items.unit_price',
                    'sales.sale_number',
                    'sales.created_at'
                )
                ->get();

            $items = [];
            foreach ($rows as $row) {
                $qty = max(0, (int) $row->quantity - (int) ($row->voided_quantity ?? 0));
                if ($qty <= 0) {
                    continue;
                }

                $localTime = Carbon::parse($row->created_at)->setTimezone('Asia/Manila');
                $items[] = [
                    'name' => $row->name,
                    'quantity' => $qty,
                    'unit_price' => (float) $row->unit_price,
                    'total' => $qty * (float) $row->unit_price,
                    'discount' => 0.0,
                    'discount_display' => '-',
                    'receipt_number' => $row->sale_number ?? 'N/A',
                    'time' => $localTime->format('h:i A'),
                    'time_24' => $localTime->format('H:i:s'),
                ];
            }

            return $items;
        }

        $todaySales = Sale::completed()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->orderBy('created_at', 'desc')
            ->get();

        $items = [];

        foreach ($todaySales as $sale) {
            $saleItems = $sale->items ?? [];
            if (! is_array($saleItems)) {
                continue;
            }

            foreach ($saleItems as $item) {
                $localTime = Carbon::parse($sale->created_at)->setTimezone('Asia/Manila');
                $items[] = [
                    'name' => $item['name'] ?? 'Unknown Product',
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'unit_price' => (float) ($item['price'] ?? 0),
                    'total' => (float) (($item['quantity'] ?? 0) * ($item['price'] ?? 0)),
                    'discount' => 0.0,
                    'discount_display' => '-',
                    'receipt_number' => $sale->sale_number ?? $sale->receipt_number ?? 'N/A',
                    'time' => $localTime->format('h:i A'),
                    'time_24' => $localTime->format('H:i:s'),
                ];
            }
        }

        return $items;
    }

    private function getMonthlyFinancials($months = 6)
    {
        $labels = [];
        $revenues = [];
        $expenses = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            // Get revenue for this month
            $monthRevenue = Sale::sumAmount(Sale::completed()->whereBetween('created_at', [$monthStart, $monthEnd]));

            $monthExpenses = 0;
            if (Schema::hasTable('purchase_orders')) {
                $monthExpenses = PurchaseOrder::whereIn('status', ['received', 'approved'])
                    ->where(function ($q) use ($monthStart, $monthEnd) {
                        $q->whereBetween('actual_delivery_date', [$monthStart, $monthEnd])
                          ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereBetween('order_date', [$monthStart, $monthEnd]);
                          })
                          ->orWhere(function ($q3) use ($monthStart, $monthEnd) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereBetween('created_at', [$monthStart, $monthEnd]);
                          });
                    })
                    ->sum('total_amount');
            }

            $labels[] = $date->format('M');
            $revenues[] = (float) $monthRevenue;
            $expenses[] = (float) ($monthExpenses ?? 0);
        }
        
        return [
            'labels' => $labels,
            'revenues' => $revenues,
            'expenses' => $expenses
        ];
    }

    /**
     * Get filtered financial summary (API endpoint)
     */
    public function getFinancialSummary(Request $request)
    {
        try {
            $period = $request->get('period', 'monthly');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            
            // Calculate revenue and expenses for the selected period
            $revenue = $this->calculateRevenueForPeriod($period, $startDate, $endDate);
            $expenses = $this->calculateExpensesForPeriod($period, $startDate, $endDate);
            $profit = $revenue - $expenses;
            
            // Calculate previous period for comparison
            $previousPeriod = $this->getPreviousPeriod($period, $startDate, $endDate);
            $previousRevenue = $this->calculateRevenueForPeriod($previousPeriod['period'], $previousPeriod['start_date'], $previousPeriod['end_date']);
            $previousExpenses = $this->calculateExpensesForPeriod($previousPeriod['period'], $previousPeriod['start_date'], $previousPeriod['end_date']);
            $previousProfit = $previousRevenue - $previousExpenses;
            
            // Calculate percentage changes
            $revenueChange = $previousRevenue > 0 
                ? (($revenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;
            $expensesChange = $previousExpenses > 0 
                ? (($expenses - $previousExpenses) / $previousExpenses) * 100 
                : 0;
            $profitChange = $previousProfit != 0 
                ? (($profit - $previousProfit) / abs($previousProfit)) * 100 
                : 0;
            
            // Get monthly financials for chart based on period
            $chartData = $this->getChartDataForPeriod($period, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'revenue' => (float) $revenue,
                'expenses' => (float) $expenses,
                'profit' => (float) $profit,
                'revenueChange' => (float) $revenueChange,
                'expensesChange' => (float) $expensesChange,
                'profitChange' => (float) $profitChange,
                'chartData' => $chartData
            ]);
        } catch (\Exception $e) {
            \Log::error('Financial Summary Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error calculating financial summary: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateRevenueForPeriod($period, $startDate = null, $endDate = null)
    {
        $query = Sale::completed();
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        } else {
            switch ($period) {
                case 'daily':
                    [$todayStart, $todayEnd] = $this->getTodayStartEndUTC();
                    $query->whereBetween('created_at', [$todayStart, $todayEnd]);
                    break;
                case 'weekly':
                    $tz = Carbon::now(self::SALES_TIMEZONE);
                    $query->whereBetween('created_at', [
                        $tz->copy()->startOfWeek()->utc(),
                        $tz->copy()->endOfWeek()->utc()
                    ]);
                    break;
                case 'biweekly':
                    $tz = Carbon::now(self::SALES_TIMEZONE);
                    $query->whereBetween('created_at', [
                        $tz->copy()->subDays(14)->startOfDay()->utc(),
                        $tz->utc()
                    ]);
                    break;
                case 'monthly':
                    $tz = Carbon::now(self::SALES_TIMEZONE);
                    $query->whereBetween('created_at', [
                        $tz->copy()->startOfMonth()->utc(),
                        $tz->copy()->endOfMonth()->utc()
                    ]);
                    break;
                case 'annual':
                    $tz = Carbon::now(self::SALES_TIMEZONE);
                    $query->whereBetween('created_at', [
                        $tz->copy()->startOfYear()->utc(),
                        $tz->copy()->endOfYear()->utc()
                    ]);
                    break;
            }
        }
        
        return Sale::sumAmount($query);
    }

    private function calculateExpensesForPeriod($period, $startDate = null, $endDate = null)
    {
        if (! Schema::hasTable('purchase_orders')) {
            return 0;
        }

        $query = PurchaseOrder::whereIn('status', ['received', 'approved']);
        
        if ($startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('actual_delivery_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->whereNull('actual_delivery_date')
                         ->whereBetween('order_date', [$startDate, $endDate]);
                  })
                  ->orWhere(function($q3) use ($startDate, $endDate) {
                      $q3->whereNull('actual_delivery_date')
                         ->whereNull('order_date')
                         ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                  });
            });
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('actual_delivery_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($startDate, $endDate) {
                      $q2->whereNull('actual_delivery_date')
                         ->whereBetween('order_date', [$startDate, $endDate]);
                  })
                  ->orWhere(function($q3) use ($startDate, $endDate) {
                      $q3->whereNull('actual_delivery_date')
                         ->whereNull('order_date')
                         ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
                  });
            });
        } else {
            switch ($period) {
                case 'daily':
                    $query->where(function($q) {
                        $q->whereDate('actual_delivery_date', Carbon::today())
                          ->orWhere(function($q2) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereDate('order_date', Carbon::today());
                          })
                          ->orWhere(function($q3) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereDate('created_at', Carbon::today());
                          });
                    });
                    break;
                case 'weekly':
                    $query->where(function($q) {
                        $q->whereBetween('actual_delivery_date', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                          ->orWhere(function($q2) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereBetween('order_date', [
                                     Carbon::now()->startOfWeek(),
                                     Carbon::now()->endOfWeek()
                                 ]);
                          })
                          ->orWhere(function($q3) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereBetween('created_at', [
                                     Carbon::now()->startOfWeek(),
                                     Carbon::now()->endOfWeek()
                                 ]);
                          });
                    });
                    break;
                case 'biweekly':
                    $query->where(function($q) {
                        $q->whereBetween('actual_delivery_date', [
                            Carbon::now()->subDays(14),
                            Carbon::now()
                        ])
                          ->orWhere(function($q2) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereBetween('order_date', [
                                     Carbon::now()->subDays(14),
                                     Carbon::now()
                                 ]);
                          })
                          ->orWhere(function($q3) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereBetween('created_at', [
                                     Carbon::now()->subDays(14),
                                     Carbon::now()
                                 ]);
                          });
                    });
                    break;
                case 'monthly':
                    $query->where(function($q) {
                        $q->whereMonth('actual_delivery_date', Carbon::now()->month)
                          ->whereYear('actual_delivery_date', Carbon::now()->year)
                          ->orWhere(function($q2) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereMonth('order_date', Carbon::now()->month)
                                 ->whereYear('order_date', Carbon::now()->year);
                          })
                          ->orWhere(function($q3) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereMonth('created_at', Carbon::now()->month)
                                 ->whereYear('created_at', Carbon::now()->year);
                          });
                    });
                    break;
                case 'annual':
                    $query->where(function($q) {
                        $q->whereYear('actual_delivery_date', Carbon::now()->year)
                          ->orWhere(function($q2) {
                              $q2->whereNull('actual_delivery_date')
                                 ->whereYear('order_date', Carbon::now()->year);
                          })
                          ->orWhere(function($q3) {
                              $q3->whereNull('actual_delivery_date')
                                 ->whereNull('order_date')
                                 ->whereYear('created_at', Carbon::now()->year);
                          });
                    });
                    break;
            }
        }
        
        return (float) ($query->sum('total_amount') ?? 0);
    }

    private function getPreviousPeriod($period, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            // Custom date range - calculate previous period with same duration
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $days = $start->diffInDays($end);
            
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($days);
            
            return [
                'period' => 'custom',
                'start_date' => $prevStart->toDateString(),
                'end_date' => $prevEnd->toDateString()
            ];
        }
        
        switch ($period) {
            case 'daily':
                return [
                    'period' => 'daily',
                    'start_date' => Carbon::yesterday()->toDateString(),
                    'end_date' => Carbon::yesterday()->toDateString()
                ];
            case 'weekly':
                $lastWeek = Carbon::now()->subWeek();
                return [
                    'period' => 'weekly',
                    'start_date' => $lastWeek->startOfWeek()->toDateString(),
                    'end_date' => $lastWeek->endOfWeek()->toDateString()
                ];
            case 'biweekly':
                return [
                    'period' => 'biweekly',
                    'start_date' => Carbon::now()->subDays(28)->toDateString(),
                    'end_date' => Carbon::now()->subDays(15)->toDateString()
                ];
            case 'monthly':
                $lastMonth = Carbon::now()->subMonth();
                return [
                    'period' => 'monthly',
                    'start_date' => $lastMonth->startOfMonth()->toDateString(),
                    'end_date' => $lastMonth->endOfMonth()->toDateString()
                ];
            case 'annual':
                $lastYear = Carbon::now()->subYear();
                return [
                    'period' => 'annual',
                    'start_date' => $lastYear->startOfYear()->toDateString(),
                    'end_date' => $lastYear->endOfYear()->toDateString()
                ];
            default:
                return [
                    'period' => 'monthly',
                    'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                    'end_date' => Carbon::now()->subMonth()->endOfMonth()->toDateString()
                ];
        }
    }

    private function getChartDataForPeriod($period, $startDate = null, $endDate = null)
    {
        try {
            if ($period === 'daily' && $startDate) {
                // For daily, show hourly breakdown or just the day
                return [
                    'labels' => [Carbon::parse($startDate)->format('M d, Y')],
                    'revenues' => [$this->calculateRevenueForPeriod('daily', $startDate, $endDate)],
                    'expenses' => [$this->calculateExpensesForPeriod('daily', $startDate, $endDate)]
                ];
            } elseif ($period === 'weekly' || ($startDate && $endDate && Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) <= 7)) {
            // Show daily breakdown for week
            $labels = [];
            $revenues = [];
            $expenses = [];
            $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfWeek();
            $end = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfWeek();
            
            $current = $start->copy();
            while ($current <= $end) {
                $labels[] = $current->format('D');
                $revenues[] = $this->calculateRevenueForPeriod('daily', $current->toDateString(), $current->toDateString());
                $expenses[] = $this->calculateExpensesForPeriod('daily', $current->toDateString(), $current->toDateString());
                $current->addDay();
            }
            
            return ['labels' => $labels, 'revenues' => $revenues, 'expenses' => $expenses];
        } elseif ($period === 'monthly' || ($startDate && $endDate && Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) <= 31)) {
            // Show daily breakdown for month
            $labels = [];
            $revenues = [];
            $expenses = [];
            $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
            $end = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
            
            $current = $start->copy();
            while ($current <= $end) {
                $labels[] = $current->format('M d');
                $revenues[] = $this->calculateRevenueForPeriod('daily', $current->toDateString(), $current->toDateString());
                $expenses[] = $this->calculateExpensesForPeriod('daily', $current->toDateString(), $current->toDateString());
                $current->addDay();
            }
            
            return ['labels' => $labels, 'revenues' => $revenues, 'expenses' => $expenses];
        } else {
            // For longer periods, show monthly breakdown
            return $this->getMonthlyFinancials(6);
        }
        } catch (\Exception $e) {
            \Log::error('Chart Data Error: ' . $e->getMessage());
            // Return default empty data on error
            return [
                'labels' => [],
                'revenues' => [],
                'expenses' => []
            ];
        }
    }

    /**
     * Get notifications for admin dashboard
     */
    public function getNotifications()
    {
        try {
            $userId = Auth::id();
            $notifications = [];
            $unreadCount = 0;
            
            // 1. Low Stock Items
            try {
                $lowStockItems = Product::query()
                    ->where('quantity', '<=', 10)
                    ->where('quantity', '>', 0)
                    ->orderBy('quantity', 'asc')
                    ->limit(5)
                    ->get();
                
                foreach ($lowStockItems as $item) {
                    $notificationKey = "low_stock_{$item->id}";
                    $isRead = NotificationRead::isRead($userId, 'low_stock', $notificationKey);
                    
                    if (!$isRead) {
                        $unreadCount++;
                    }
                    
                    $notifications[] = [
                        'id' => $notificationKey,
                        'notification_type' => 'low_stock',
                        'notification_key' => $notificationKey,
                        'type' => 'warning',
                        'message' => "Low stock alert: {$item->name} (Qty: {$item->quantity})",
                        'time' => $this->formatTimeAgo($item->updated_at),
                        'icon' => 'exclamation-triangle',
                        'link' => null,
                        'read' => $isRead
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading low stock items: ' . $e->getMessage());
            }
            
            // 2. Pending Purchase Orders
            try {
                $pendingPOs = PurchaseOrder::where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($pendingPOs as $po) {
                    $poNumber = $po->po_number ?? 'N/A';
                    $totalAmount = $po->total_amount ?? 0;
                    $notificationKey = "pending_po_{$po->id}";
                    $isRead = NotificationRead::isRead($userId, 'pending_po', $notificationKey);
                    
                    if (!$isRead) {
                        $unreadCount++;
                    }
                    
                    $notifications[] = [
                        'id' => $notificationKey,
                        'notification_type' => 'pending_po',
                        'notification_key' => $notificationKey,
                        'type' => 'info',
                        'message' => "Pending PO: {$poNumber} - ₱" . number_format($totalAmount, 2),
                        'time' => $this->formatTimeAgo($po->created_at),
                        'icon' => 'file-earmark-text',
                        'link' => null,
                        'read' => $isRead
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading pending POs: ' . $e->getMessage());
            }
            
            // 3. Pending Free Sample Requests
            try {
                if (Schema::hasTable('free_sample_requests')) {
                    $pendingSamples = FreeSampleRequest::query()
                        ->pending()
                        ->with(['requestedBy', 'items.product', 'requestStatus'])
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();

                    foreach ($pendingSamples as $sample) {
                        $userName = $sample->requestedBy?->name ?? 'Unknown';
                        $itemName = optional($sample->items->first()?->product)->name ?? 'Unknown Item';
                        $notificationKey = "pending_sample_{$sample->id}";
                        $isRead = NotificationRead::isRead($userId, 'pending_sample', $notificationKey);

                        if (! $isRead) {
                            $unreadCount++;
                        }

                        $notifications[] = [
                            'id' => $notificationKey,
                            'notification_type' => 'pending_sample',
                            'notification_key' => $notificationKey,
                            'type' => 'info',
                            'message' => "Pending sample request from {$userName}: {$itemName}",
                            'time' => $this->formatTimeAgo($sample->created_at),
                            'icon' => 'gift',
                            'link' => null,
                            'read' => $isRead,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading pending samples: ' . $e->getMessage());
            }
            
            // 4. Recent Completed Shifts
            try {
                $recentShifts = Shift::where('status', 'closed')
                    ->with('user')
                    ->orderBy('end_time', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($recentShifts as $shift) {
                    $userName = $shift->user ? $shift->user->name : ($shift->cashier_name ?? 'Unknown');
                    $totalSales = $shift->total_sales ?? 0;
                    $endTime = $shift->end_time ?? $shift->updated_at;
                    $notificationKey = "completed_shift_{$shift->id}";
                    $isRead = NotificationRead::isRead($userId, 'completed_shift', $notificationKey);
                    
                    if (!$isRead) {
                        $unreadCount++;
                    }
                    
                    $notifications[] = [
                        'id' => $notificationKey,
                        'notification_type' => 'completed_shift',
                        'notification_key' => $notificationKey,
                        'type' => 'success',
                        'message' => "Shift completed: {$userName} - Total: ₱" . number_format($totalSales, 2),
                        'time' => $this->formatTimeAgo($endTime),
                        'icon' => 'check-circle',
                        'link' => null,
                        'read' => $isRead
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading completed shifts: ' . $e->getMessage());
            }
            
            // 5. Items Expiring Soon (from inventory table)
            try {
                $expiringItems = Inventory::whereNotNull('expiry_date')
                    ->where('expiry_date', '>=', Carbon::now())
                    ->where('expiry_date', '<=', Carbon::now()->addDays(30))
                    ->where('status', 'completed')
                    ->with('item')
                    ->orderBy('expiry_date', 'asc')
                    ->limit(5)
                    ->get()
                    ->unique('item_id');
                
                foreach ($expiringItems as $inventory) {
                    if ($inventory->item) {
                        $daysUntilExpiry = Carbon::parse($inventory->expiry_date)->diffInDays(Carbon::now());
                        $itemName = $inventory->item->item ?? 'Unknown Item';
                        $notificationKey = "expiring_item_{$inventory->item_id}";
                        $isRead = NotificationRead::isRead($userId, 'expiring_item', $notificationKey);
                        
                        if (!$isRead) {
                            $unreadCount++;
                        }
                        
                        $notifications[] = [
                            'id' => $notificationKey,
                            'notification_type' => 'expiring_item',
                            'notification_key' => $notificationKey,
                            'type' => 'danger',
                            'message' => "Expiring soon: {$itemName} (Expires in {$daysUntilExpiry} days)",
                            'time' => $this->formatTimeAgo($inventory->created_at),
                            'icon' => 'x-circle',
                            'link' => null,
                            'read' => $isRead
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading expiring items: ' . $e->getMessage());
            }
            
            // Filter out read notifications and limit to 10 most recent unread
            $unreadNotifications = array_filter($notifications, function($notif) {
                return !$notif['read'];
            });
            $unreadNotifications = array_slice($unreadNotifications, 0, 10);
            
            return response()->json([
                'success' => true,
                'notifications' => array_values($unreadNotifications),
                'count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            \Log::error('Notifications Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'notifications' => [],
                'count' => 0,
                'message' => 'Error loading notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a notification as read
     */
    public function markNotificationAsRead(Request $request)
    {
        try {
            $userId = Auth::id();
            $type = $request->input('notification_type');
            $key = $request->input('notification_key');
            
            if (!$type || !$key) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification type and key are required'
                ], 400);
            }
            
            NotificationRead::markAsRead($userId, $type, $key);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            \Log::error('Mark Notification Read Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error marking notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // Get all current notifications and mark them as read
            $notifications = $this->getAllNotificationKeys();
            
            foreach ($notifications as $type => $keys) {
                foreach ($keys as $key) {
                    NotificationRead::markAsRead($userId, $type, $key);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            \Log::error('Mark All Notifications Read Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error marking all notifications as read'
            ], 500);
        }
    }

    /**
     * Get all notification keys for current notifications
     */
    private function getAllNotificationKeys()
    {
        $notifications = [];
        
        // Low Stock Items
        $lowStockItems = Product::query()
            ->where('quantity', '<=', 10)
            ->where('quantity', '>', 0)
            ->limit(5)
            ->pluck('id')
            ->map(fn ($id) => "low_stock_{$id}")
            ->toArray();
        if (! empty($lowStockItems)) {
            $notifications['low_stock'] = $lowStockItems;
        }
        
        // Pending POs
        $pendingPOs = PurchaseOrder::where('status', 'pending')
            ->limit(5)
            ->pluck('id')
            ->map(fn($id) => "pending_po_{$id}")
            ->toArray();
        if (!empty($pendingPOs)) {
            $notifications['pending_po'] = $pendingPOs;
        }
        
        // Pending Samples
        if (Schema::hasTable('free_sample_requests')) {
            $pendingSamples = FreeSampleRequest::query()
                ->pending()
                ->limit(5)
                ->pluck('id')
                ->map(fn ($id) => "pending_sample_{$id}")
                ->toArray();
            if (! empty($pendingSamples)) {
                $notifications['pending_sample'] = $pendingSamples;
            }
        }
        
        // Completed Shifts
        $completedShifts = Shift::where('status', 'closed')
            ->limit(5)
            ->pluck('id')
            ->map(fn($id) => "completed_shift_{$id}")
            ->toArray();
        if (!empty($completedShifts)) {
            $notifications['completed_shift'] = $completedShifts;
        }
        
        // Expiring Items
        $expiringItems = Inventory::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('status', 'completed')
            ->limit(5)
            ->pluck('item_id')
            ->unique()
            ->map(fn($id) => "expiring_item_{$id}")
            ->toArray();
        if (!empty($expiringItems)) {
            $notifications['expiring_item'] = $expiringItems;
        }
        
        return $notifications;
    }

    /**
     * Search item edit logs by email, item name, or date.
     */
    public function getItemEditLogs(Request $request)
    {
        try {
            $search = trim((string) $request->get('search', ''));
            $query = ItemEditLog::query()->latest();

            if ($search !== '') {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($q) use ($like, $search) {
                    $q->where('user_email', 'like', $like)
                        ->orWhere('item_name', 'like', $like)
                        ->orWhereRaw(
                            "DATE_FORMAT(DATE_ADD(created_at, INTERVAL 8 HOUR), '%b %d, %Y %h:%i %p') LIKE ?",
                            [$like]
                        )
                        ->orWhereRaw(
                            "DATE_FORMAT(DATE_ADD(created_at, INTERVAL 8 HOUR), '%Y-%m-%d') LIKE ?",
                            [$like]
                        )
                        ->orWhereRaw(
                            "DATE_FORMAT(DATE_ADD(created_at, INTERVAL 8 HOUR), '%M %d, %Y') LIKE ?",
                            [$like]
                        );

                    if ($this->looksLikeDateSearch($search)) {
                        try {
                            $parsed = Carbon::parse($search, self::SALES_TIMEZONE);
                            $q->orWhereBetween('created_at', [
                                $parsed->copy()->startOfDay()->utc(),
                                $parsed->copy()->endOfDay()->utc(),
                            ]);
                        } catch (\Exception $e) {
                            // Ignore values that are not valid dates.
                        }
                    }
                });
            }

            $logs = $query->limit(50)
                ->get()
                ->map(fn (ItemEditLog $log) => $log->toDisplayArray())
                ->values();

            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Error searching item edit logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'logs' => [],
                'message' => 'Error loading item edit logs',
            ], 500);
        }
    }

    private function looksLikeDateSearch(string $search): bool
    {
        if (preg_match('/\d/', $search) !== 1) {
            return false;
        }

        return (bool) preg_match(
            '/\d{4}|\d{1,2}[\/\-]\d{1,2}|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec/i',
            $search
        );
    }

    /**
     * Get activity logs for admin dashboard
     */
    public function getActivityLogs()
    {
        try {
            $activities = [];
            
            // 1. Recent Sales
            try {
                $recentSalesQuery = Sale::with(['user', 'shift'])
                    ->completed()
                    ->orderBy('created_at', 'desc')
                    ->limit(10);

                // Legacy only: restrict to closed shifts when using the old shifts table
                if (Schema::hasTable('shifts') && Schema::hasColumn('sales', 'shift_id') && ! Schema::hasColumn('sales', 'cashier_shift_id')) {
                    $recentSalesQuery->whereHas('shift', function ($query) {
                        $query->where('status', 'closed');
                    });
                }

                $recentSales = $recentSalesQuery->get();

                foreach ($recentSales as $sale) {
                    $userName = $sale->user ? $sale->user->name : 'Unknown';
                    $activities[] = [
                        'user' => $userName,
                        'action' => 'Completed sale: ₱'.number_format($sale->amount ?? 0, 2),
                        'time' => $this->formatTimeAgo($sale->created_at),
                        'timestamp' => $sale->created_at,
                        'type' => 'sale',
                        'sale_id' => $sale->id,
                        'user_id' => $sale->cashier_user_id ?? $sale->user_id,
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading sales activities: '.$e->getMessage());
            }
            
            // 2. Inventory Transactions
            try {
                $inventoryActivities = Inventory::with(['user', 'item'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                
                foreach ($inventoryActivities as $inventory) {
                    $userName = $inventory->user ? $inventory->user->name : 'Unknown';
                    $itemName = $inventory->item ? $inventory->item->item : 'Unknown Item';
                    $actionType = ucfirst(str_replace('_', ' ', $inventory->transaction_type));
                    $quantity = abs((float) $inventory->quantity);
                    
                    $activities[] = [
                        'user' => $userName,
                        'action' => "{$actionType}: {$itemName} (Qty: {$quantity})",
                        'time' => $this->formatTimeAgo($inventory->created_at),
                        'timestamp' => $inventory->created_at,
                        'type' => 'inventory'
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading inventory activities: ' . $e->getMessage());
            }
            
            // 3. Purchase Orders
            try {
                $recentPOs = PurchaseOrder::with('creator')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                
                foreach ($recentPOs as $po) {
                    $userName = $po->creator ? $po->creator->name : 'Unknown';
                    $poNumber = $po->po_number ?? 'N/A';
                    $status = ucfirst($po->status);
                    $activities[] = [
                        'user' => $userName,
                        'action' => "{$status} PO: {$poNumber}",
                        'time' => $this->formatTimeAgo($po->created_at),
                        'timestamp' => $po->created_at,
                        'type' => 'purchase_order'
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading PO activities: ' . $e->getMessage());
            }
            
            // 4. Free Sample Requests
            try {
                if (Schema::hasTable('free_sample_requests')) {
                    $recentSamples = FreeSampleRequest::query()
                        ->with(['requestedBy', 'items.product', 'requestStatus'])
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get();

                    foreach ($recentSamples as $sample) {
                        $userName = $sample->requestedBy?->name ?? 'Unknown';
                        $itemName = optional($sample->items->first()?->product)->name ?? 'Unknown Item';
                        $status = ucfirst((string) $sample->status);
                        $activities[] = [
                            'user' => $userName,
                            'action' => "{$status} sample request: {$itemName}",
                            'time' => $this->formatTimeAgo($sample->created_at),
                            'timestamp' => $sample->created_at,
                            'type' => 'free_sample',
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading sample activities: ' . $e->getMessage());
            }
            
            // 5. Shift Activities
            try {
                $recentShifts = Shift::with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                
                foreach ($recentShifts as $shift) {
                    $userName = $shift->user ? $shift->user->name : ($shift->cashier_name ?? 'Unknown');
                    $status = ucfirst($shift->status);
                    $action = $status === 'Active' ? 'Started shift' : 'Closed shift';
                    $activities[] = [
                        'user' => $userName,
                        'action' => "{$action} - Total: ₱" . number_format($shift->total_sales ?? 0, 2),
                        'time' => $this->formatTimeAgo($shift->created_at),
                        'timestamp' => $shift->created_at,
                        'type' => 'shift'
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error loading shift activities: ' . $e->getMessage());
            }
            
            // Sort all activities by timestamp (most recent first)
            usort($activities, function($a, $b) {
                $timeA = $a['timestamp'] instanceof Carbon 
                    ? $a['timestamp']->timestamp 
                    : strtotime($a['timestamp']);
                $timeB = $b['timestamp'] instanceof Carbon 
                    ? $b['timestamp']->timestamp 
                    : strtotime($b['timestamp']);
                return $timeB - $timeA;
            });
            
            // Limit to 15 most recent activities
            $activities = array_slice($activities, 0, 15);
            
            return response()->json([
                'success' => true,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            \Log::error('Activity Logs Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'activities' => [],
                'message' => 'Error loading activity logs'
            ], 500);
        }
    }

    /**
     * Get sale items for a specific user from closed shifts
     */
    public function getUserSaleItems(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            // Get all sales for this user
            $salesQuery = Sale::with(['shift', 'user', 'saleItems.product'])
                ->completed();

            if (Schema::hasColumn('sales', 'cashier_user_id')) {
                $salesQuery->where('cashier_user_id', $userId);
            } else {
                $salesQuery->where('user_id', $userId);
            }

            if (Schema::hasTable('shifts') && Schema::hasColumn('sales', 'shift_id') && ! Schema::hasColumn('sales', 'cashier_shift_id')) {
                $salesQuery->whereHas('shift', function ($query) {
                    $query->where('status', 'closed');
                });
            }

            $sales = $salesQuery
                ->whereBetween('created_at', $this->getTodayStartEndUTC())
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Aggregate all items from all sales
            $allItems = [];
            $totalSales = 0;
            
            foreach ($sales as $sale) {
                if (Schema::hasTable('sale_items')) {
                    foreach ($sale->saleItems as $saleItem) {
                        $quantity = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
                        if ($quantity <= 0) {
                            continue;
                        }

                        $price = (float) $saleItem->unit_price;
                        $itemTotal = $quantity * $price;
                        $name = optional($saleItem->product)->name ?? 'Unknown Product';

                        $existingItemIndex = null;
                        foreach ($allItems as $idx => $existingItem) {
                            if ($existingItem['name'] === $name) {
                                $existingItemIndex = $idx;
                                break;
                            }
                        }

                        if ($existingItemIndex !== null) {
                            $allItems[$existingItemIndex]['quantity'] += $quantity;
                            $allItems[$existingItemIndex]['total'] += $itemTotal;
                        } else {
                            $allItems[] = [
                                'name' => $name,
                                'quantity' => $quantity,
                                'price' => $price,
                                'total' => $itemTotal,
                                'unit' => 'pcs',
                            ];
                        }

                        $totalSales += $itemTotal;
                    }

                    continue;
                }

                $saleItems = $sale->items;
                if (is_string($saleItems)) {
                    $items = json_decode($saleItems, true);
                    $items = is_array($items) ? $items : [];
                } elseif (is_array($saleItems)) {
                    $items = $saleItems;
                } else {
                    $items = [];
                }
                
                // Get void status for this sale
                $voidRequest = VoidRequest::where('sale_id', $sale->id)
                    ->whereIn('status', ['pending', 'approved', 'rejected'])
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $voidStatus = $voidRequest ? $voidRequest->status : null;
                $voidedItems = $voidRequest && $voidRequest->voided_items 
                    ? (is_array($voidRequest->voided_items) ? $voidRequest->voided_items : []) 
                    : [];
                $voidedItemIndices = array_map('intval', $voidedItems);
                
                foreach ($items as $itemIndex => $item) {
                    // Skip voided items if void status is approved
                    if ($voidStatus === 'approved' && in_array($itemIndex, $voidedItemIndices)) {
                        continue;
                    }
                    
                    $quantity = floatval($item['quantity'] ?? 0);
                    $price = floatval($item['price'] ?? 0);
                    $itemTotal = $quantity * $price;
                    
                    // Check if item already exists in allItems (same product name)
                    $existingItemIndex = null;
                    foreach ($allItems as $idx => $existingItem) {
                        if ($existingItem['name'] === ($item['name'] ?? 'Unknown Product')) {
                            $existingItemIndex = $idx;
                            break;
                        }
                    }
                    
                    if ($existingItemIndex !== null) {
                        // Aggregate with existing item
                        $allItems[$existingItemIndex]['quantity'] += $quantity;
                        $allItems[$existingItemIndex]['total'] += $itemTotal;
                    } else {
                        // Add new item
                        $allItems[] = [
                            'name' => $item['name'] ?? 'Unknown Product',
                            'quantity' => $quantity,
                            'price' => $price,
                            'total' => $itemTotal,
                            'unit' => $item['unit'] ?? 'pcs'
                        ];
                    }
                    
                    $totalSales += $itemTotal;
                }
            }
            
            // Sort items by total (descending)
            usort($allItems, function($a, $b) {
                return $b['total'] <=> $a['total'];
            });
            
            $user = User::find($userId);
            $userName = $user ? $user->name : 'Unknown';
            
            return response()->json([
                'success' => true,
                'user_name' => $userName,
                'items' => $allItems,
                'total_sales' => $totalSales,
                'total_items' => count($allItems),
                'transaction_count' => $sales->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching user sale items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sale items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all sales with date filtering for admin reports
     */
    public function getAllSales(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::today('Asia/Manila')->toDateString());
            $endDate = $request->input('end_date', Carbon::today('Asia/Manila')->toDateString());
            $cashierId = $request->input('cashier_id', null);

            $startDateTime = Carbon::parse($startDate, 'Asia/Manila')->startOfDay();
            $endDateTime = Carbon::parse($endDate, 'Asia/Manila')->endOfDay();
            $startDateTimeUTC = $startDateTime->utc();
            $endDateTimeUTC = $endDateTime->utc();

            $usesNewSchema = Schema::hasTable('sale_items') && Schema::hasColumn('sales', 'sale_status_id');

            $with = $usesNewSchema
                ? ['user', 'shift', 'saleItems.product', 'salePayments.paymentMethod']
                : ['user', 'shift'];

            $query = Sale::with($with)
                ->completed()
                ->whereBetween('created_at', [
                    $startDateTimeUTC,
                    $endDateTimeUTC,
                ]);

            if ($cashierId) {
                if (Schema::hasColumn('sales', 'cashier_user_id')) {
                    $query->where('cashier_user_id', $cashierId);
                } else {
                    $query->where('user_id', $cashierId);
                }
            }

            $sales = $query->orderBy('created_at', 'desc')->get();

            $salesData = [];
            $totalSubtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;
            $totalAmount = 0;
            $totalItems = 0;

            foreach ($sales as $sale) {
                [$voidStatus, $voidedItemIndices] = $this->resolveSaleVoidDisplay($sale);

                if ($usesNewSchema) {
                    $itemsWithIndex = [];
                    $validItems = [];
                    $itemQuantity = 0;
                    $saleSubtotal = 0.0;

                    foreach ($sale->saleItems->values() as $itemIndex => $saleItem) {
                        $remaining = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
                        $isVoided = $remaining <= 0;
                        $name = optional($saleItem->product)->name ?? 'Unknown Product';
                        $price = (float) $saleItem->unit_price;
                        $qtyForDisplay = $isVoided ? (int) $saleItem->quantity : $remaining;

                        $itemsWithIndex[] = [
                            'item_index' => $itemIndex,
                            'sale_item_id' => $saleItem->id,
                            'product_id' => $saleItem->product_id,
                            'name' => $name,
                            'quantity' => $qtyForDisplay,
                            'price' => $price,
                            'total' => $qtyForDisplay * $price,
                            'voided' => $isVoided,
                        ];

                        if ($isVoided) {
                            continue;
                        }

                        $validItems[] = $itemsWithIndex[count($itemsWithIndex) - 1];
                        $itemQuantity += $remaining;
                        $saleSubtotal += $remaining * $price;
                    }

                    $saleTax = 0.0;
                    $saleDiscount = 0.0;
                    $saleAmount = $saleSubtotal;
                    $paymentMethod = optional(optional($sale->salePayments->first())->paymentMethod)->name ?? 'cash';
                    $receiptNumber = $sale->sale_number ?? $sale->receipt_number;
                    $cashierIdValue = $sale->cashier_user_id ?? $sale->user_id;
                } else {
                    $voidRequest = VoidRequest::where('sale_id', $sale->id)
                        ->whereIn('status', ['pending', 'approved', 'rejected'])
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $voidStatus = $voidRequest ? $voidRequest->status : null;
                    $voidedItems = $voidRequest && $voidRequest->voided_items
                        ? (is_array($voidRequest->voided_items) ? $voidRequest->voided_items : [])
                        : [];
                    $voidedItemIndices = array_map('intval', $voidedItems);

                    $saleItems = $sale->items;
                    if (is_string($saleItems)) {
                        $items = json_decode($saleItems, true);
                        $items = is_array($items) ? $items : [];
                    } elseif (is_array($saleItems)) {
                        $items = $saleItems;
                    } else {
                        $items = [];
                    }

                    $itemsWithIndex = [];
                    foreach ($items as $itemIndex => $item) {
                        $item['item_index'] = $itemIndex;
                        $itemsWithIndex[] = $item;
                    }

                    $validItems = [];
                    $itemQuantity = 0;
                    foreach ($items as $itemIndex => $item) {
                        if ($voidStatus === 'approved' && in_array($itemIndex, $voidedItemIndices, true)) {
                            continue;
                        }
                        $validItems[] = $item;
                        $itemQuantity += floatval($item['quantity'] ?? 0);
                    }

                    $saleSubtotal = 0;
                    foreach ($validItems as $item) {
                        $itemQty = floatval($item['quantity'] ?? 0);
                        $itemPrice = floatval($item['price'] ?? 0);
                        $saleSubtotal += $itemQty * $itemPrice;
                    }

                    $saleTax = 0;
                    $saleDiscount = 0;
                    if (count($validItems) > 0 && count($items) > 0) {
                        $originalSubtotal = $sale->subtotal ?? 0;
                        $originalTax = $sale->tax ?? 0;
                        $originalDiscount = $sale->discount ?? 0;

                        if ($originalSubtotal > 0) {
                            $ratio = $saleSubtotal / $originalSubtotal;
                            $saleTax = $originalTax * $ratio;
                            $saleDiscount = $originalDiscount * $ratio;
                        }
                    }

                    $saleAmount = $saleSubtotal + $saleTax - $saleDiscount;
                    $paymentMethod = $sale->payment_method ?? 'cash';
                    $receiptNumber = $sale->receipt_number;
                    $cashierIdValue = $sale->user_id;
                }

                $createdAt = Carbon::parse($sale->created_at);
                $localTime = $createdAt->setTimezone('Asia/Manila');

                $salesData[] = [
                    'id' => $sale->id,
                    'receipt_number' => $receiptNumber,
                    'date_time' => $localTime->format('Y-m-d H:i:s'),
                    'date' => $localTime->format('Y-m-d'),
                    'time' => $localTime->format('H:i:s'),
                    'date_formatted' => $localTime->format('M d, Y'),
                    'time_formatted' => $localTime->format('h:i A'),
                    'date_time_formatted' => $localTime->format('M d, Y h:i A'),
                    'cashier_name' => $sale->user ? $sale->user->name : 'Unknown',
                    'cashier_id' => $cashierIdValue,
                    'items' => $itemsWithIndex,
                    'voided_items' => $voidedItemIndices,
                    'item_count' => count($validItems),
                    'quantity' => $itemQuantity,
                    'subtotal' => floatval($saleSubtotal),
                    'tax' => floatval($saleTax),
                    'discount' => floatval($saleDiscount),
                    'total_amount' => floatval($saleAmount),
                    'payment_method' => $paymentMethod,
                    'status' => $sale->status,
                    'void_status' => $voidStatus,
                ];

                if ($voidStatus !== 'approved' || count($validItems) > 0) {
                    $totalSubtotal += floatval($saleSubtotal);
                    $totalTax += floatval($saleTax);
                    $totalDiscount += floatval($saleDiscount);
                    $totalAmount += floatval($saleAmount);
                    $totalItems += $itemQuantity;
                }
            }

            $transactionCount = count($salesData);
            $averageTransaction = $transactionCount > 0 ? $totalAmount / $transactionCount : 0;

            return response()->json([
                'success' => true,
                'sales' => $salesData,
                'summary' => [
                    'total_subtotal' => $totalSubtotal,
                    'total_tax' => $totalTax,
                    'total_discount' => $totalDiscount,
                    'total_amount' => $totalAmount,
                    'total_transactions' => $transactionCount,
                    'total_items_sold' => $totalItems,
                    'average_transaction' => $averageTransaction,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching all sales: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching sales: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{0: ?string, 1: array<int, int>}
     */
    private function resolveSaleVoidDisplay(Sale $sale): array
    {
        if (Schema::hasTable('sale_items') && Schema::hasColumn('sales', 'sale_status_id')) {
            $sale->loadMissing('saleItems');

            $voidedIndices = $sale->saleItems->values()
                ->filter(fn (SaleItem $item) => (int) ($item->voided_quantity ?? 0) >= (int) $item->quantity)
                ->keys()
                ->map(fn ($k) => (int) $k)
                ->values()
                ->all();

            $hasPending = false;
            if (Schema::hasTable('void_requests') && Schema::hasColumn('void_requests', 'request_status_id')) {
                $pendingId = RequestStatus::query()->where('name', 'pending')->value('id');
                $hasPending = $pendingId
                    ? VoidRequest::query()
                        ->where('sale_id', $sale->id)
                        ->where('request_status_id', $pendingId)
                        ->exists()
                    : false;
            }

            if ($hasPending) {
                return ['pending', $voidedIndices];
            }

            if (! empty($voidedIndices)) {
                return ['approved', $voidedIndices];
            }

            return [null, []];
        }

        $voidRequest = VoidRequest::where('sale_id', $sale->id)
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->first();

        $voidStatus = $voidRequest ? $voidRequest->status : null;
        $voidedItems = $voidRequest && $voidRequest->voided_items
            ? (is_array($voidRequest->voided_items) ? $voidRequest->voided_items : [])
            : [];

        return [$voidStatus, array_map('intval', $voidedItems)];
    }

    /**
     * Format time ago for notifications
     */
    private function formatTimeAgo($datetime)
    {
        if (!$datetime) {
            return 'Unknown';
        }
        
        $carbon = Carbon::parse($datetime);
        $diff = $carbon->diffForHumans();
        
        return $diff;
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|in:admin,inventory,cashier',
                'password' => 'required|min:6',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => \Hash::make($validated['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Store User Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'role' => 'required|in:admin,inventory,cashier',
                'password' => 'nullable|min:6',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            
            if (!empty($validated['password'])) {
                $user->password = \Hash::make($validated['password']);
            }
            
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update User Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user)
    {
        try {
            // Prevent deleting the currently logged-in user
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Delete User Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's sales value for dashboard refresh
     */
    public function getTodaySales()
    {
        $todaySales = $this->calculateSales('today');
        return response()->json([
            'success' => true,
            'todaySales' => $todaySales,
            'formatted' => '₱' . number_format($todaySales, 2)
        ]);
    }

    /**
     * Get daily sales data for Sales Analytics chart refresh
     */
    public function getDailySalesData()
    {
        try {
            $dailySales = $this->getDailySales(7);
            return response()->json([
                'success' => true,
                'dailySales' => $dailySales
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting daily sales data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading daily sales data',
                'dailySales' => [
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'data' => [0, 0, 0, 0, 0, 0, 0]
                ]
            ], 500);
        }
    }
}
