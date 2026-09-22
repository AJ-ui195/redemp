<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Shift;
use App\Models\Sale;
use App\Models\SalePayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShiftController extends Controller
{
    private const MANILA_TZ = 'Asia/Manila';

    public function __construct()
    {
        if (! Schema::hasTable('cashier_shifts')) {
            Shift::ensureTableExists();
        }
    }

    /**
     * Format a datetime as Asia/Manila wall-clock (Y-m-d H:i:s).
     */
    private function formatManilaDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $carbon = $value instanceof Carbon
                ? $value->copy()
                : Carbon::parse($value);

            return $carbon->timezone(self::MANILA_TZ)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return is_string($value) ? $value : null;
        }
    }

    /**
     * Format a datetime for Manila display parts used by admin reports.
     *
     * @return array{datetime:?string,date:string,time:string,full:string}
     */
    private function formatManilaDisplayParts($value): array
    {
        if ($value === null || $value === '') {
            return [
                'datetime' => null,
                'date' => '-',
                'time' => '-',
                'full' => '-',
            ];
        }

        try {
            $carbon = ($value instanceof Carbon ? $value->copy() : Carbon::parse($value))
                ->timezone(self::MANILA_TZ);

            return [
                'datetime' => $carbon->format('Y-m-d H:i:s'),
                'date' => $carbon->format('M d, Y'),
                'time' => $carbon->format('h:i A'),
                'full' => $carbon->format('M d, Y h:i A'),
            ];
        } catch (\Throwable $e) {
            return [
                'datetime' => is_string($value) ? $value : null,
                'date' => '-',
                'time' => '-',
                'full' => is_string($value) ? $value : '-',
            ];
        }
    }

    /**
     * Get active shift for current user
     */
    public function getActive()
    {
        if (Schema::hasTable('cashier_shifts')) {
            return $this->getActiveCashierShift();
        }

        $shift = Shift::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        $shiftData = null;
        if ($shift) {
            // Load all sales for this shift (so cashier Today's Analytics & Daily Sales Report stay in sync)
            $sales = Sale::with('user')
                ->where('shift_id', $shift->id)
                ->orderBy('created_at', 'asc')
                ->get();

            $shift->total_sales = $sales->sum('amount');
            $shift->transaction_count = $sales->count();

            // Calculate payment methods breakdown
            $paymentMethods = [
                'cash' => $sales->where('payment_method', 'cash')->sum('amount'),
                'card' => $sales->where('payment_method', 'card')->sum('amount'),
                'gcash' => $sales->where('payment_method', 'gcash')->sum('amount'),
                'maya' => $sales->where('payment_method', 'maya')->sum('amount'),
            ];
            $shift->payment_methods = $paymentMethods;
            $shift->save();

            // Build transactions list for frontend (Today's Analytics & Daily Sales Report)
            $transactions = [];
            foreach ($sales as $sale) {
                $items = $sale->items;
                if (is_string($items)) {
                    $items = json_decode($items, true);
                    $items = is_array($items) ? $items : [];
                } elseif (!is_array($items)) {
                    $items = [];
                }
                $transactionItems = [];
                foreach ($items as $item) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    $price = (float) ($item['price'] ?? 0);
                    $total = isset($item['total']) ? (float) $item['total'] : ($qty * $price);
                    $transactionItems[] = [
                        'name' => $item['name'] ?? 'Unknown',
                        'quantity' => $qty,
                        'price' => $price,
                        'total' => $total,
                    ];
                }
                $transactions[] = [
                    'id' => $sale->id,
                    'receiptNumber' => $sale->receipt_number ?? 'N/A',
                    'items' => $transactionItems,
                    'subtotal' => (float) ($sale->subtotal ?? 0),
                    'discount' => (float) ($sale->discount ?? 0),
                    'total' => (float) ($sale->amount ?? 0),
                    'amount' => (float) ($sale->amount ?? 0),
                    'paymentMethod' => $sale->payment_method ?? 'cash',
                    'timestamp' => $sale->created_at ? $sale->created_at->toIso8601String() : null,
                    'cashier' => $sale->user ? $sale->user->name : null,
                ];
            }

            // Convert shift to array and use created_at as start_time
            $shiftData = $shift->toArray();
            $shiftData['transactions'] = $transactions;
            // Use created_at from database as the shift start time
            if ($shift->created_at) {
                try {
                    // Get raw database value - created_at is stored in database timezone
                    $rawCreatedAt = $shift->getRawOriginal('created_at');
                    
                    // Format as Asia/Manila time (YYYY-MM-DD HH:MM:SS)
                    $createdAtLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');
                    $shiftData['start_time'] = $createdAtLocal->format('Y-m-d H:i:s');
                    
                } catch (\Exception $e) {
                    // Fallback: use Carbon instance directly
                    $shiftData['start_time'] = $shift->created_at->format('Y-m-d H:i:s');
                }
            }
        }

        return response()->json([
            'success' => true,
            'shift' => $shiftData,
            'user' => Auth::user()
        ]);
    }

    private function getActiveCashierShift()
    {
        $shift = CashierShift::query()
            ->where('cashier_user_id', Auth::id())
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'shift' => null,
                'user' => Auth::user(),
            ]);
        }

        $sales = Sale::query()
            ->with(['saleItems.product', 'salePayments.paymentMethod', 'cashier'])
            ->where('cashier_shift_id', $shift->id)
            ->orderBy('created_at')
            ->get();

        $transactions = [];
        $paymentMethods = ['cash' => 0, 'card' => 0, 'gcash' => 0, 'maya' => 0];
        $totalSales = 0.0;

        foreach ($sales as $sale) {
            $amount = (float) $sale->amount;
            $totalSales += $amount;
            $method = optional(optional($sale->salePayments->first())->paymentMethod)->name ?? 'cash';
            if (! isset($paymentMethods[$method])) {
                $paymentMethods[$method] = 0;
            }
            $paymentMethods[$method] += $amount;

            $transactionItems = [];
            foreach ($sale->saleItems as $saleItem) {
                $qty = max(0, (int) $saleItem->quantity - (int) ($saleItem->voided_quantity ?? 0));
                $price = (float) $saleItem->unit_price;
                $transactionItems[] = [
                    'name' => optional($saleItem->product)->name ?? 'Unknown',
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $qty * $price,
                ];
            }

            $transactions[] = [
                'id' => $sale->id,
                'receiptNumber' => $sale->sale_number ?? 'N/A',
                'items' => $transactionItems,
                'subtotal' => $amount,
                'discount' => 0,
                'total' => $amount,
                'amount' => $amount,
                'paymentMethod' => $method,
                'timestamp' => $sale->created_at ? $sale->created_at->toIso8601String() : null,
                'cashier' => optional($sale->cashier)->name,
            ];
        }

        $shiftData = [
            'id' => $shift->id,
            'user_id' => $shift->cashier_user_id,
            'cashier_name' => $shift->cashier_name
                ?: optional($shift->cashier)->name
                ?: optional(Auth::user())->name,
            'opening_cash' => (float) $shift->opening_cash,
            'status' => 'active',
            'total_sales' => $totalSales,
            'transaction_count' => $sales->count(),
            'payment_methods' => $paymentMethods,
            'start_time' => $this->formatManilaDateTime($shift->started_at),
            'created_at' => optional($shift->created_at)->toIso8601String(),
            'transactions' => $transactions,
        ];

        return response()->json([
            'success' => true,
            'shift' => $shiftData,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Start a new shift
     */
    public function start(Request $request)
    {
        try {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
                'cashier_name' => 'nullable|string|max:255',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Shift start validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_map(function($errors) {
                    return implode(', ', $errors);
                }, $e->errors()))
            ], 400);
        }

        if (Schema::hasTable('cashier_shifts')) {
            $activeShift = CashierShift::query()
                ->where('cashier_user_id', Auth::id())
                ->whereNull('ended_at')
                ->first();

            if ($activeShift) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active shift. Please close it first.',
                ], 400);
            }

            $cashierName = trim((string) ($request->cashier_name ?? '')) ?: (Auth::user()->name ?? 'Cashier');

            $shift = CashierShift::create([
                'cashier_user_id' => Auth::id(),
                'cashier_name' => $cashierName,
                'opening_cash' => $request->opening_cash,
                // Store UTC instant; API responses format as Asia/Manila for display.
                'started_at' => now(),
            ]);

            $shiftData = [
                'id' => $shift->id,
                'user_id' => $shift->cashier_user_id,
                'cashier_name' => $shift->cashier_name ?: $cashierName,
                'opening_cash' => (float) $shift->opening_cash,
                'status' => 'active',
                'total_sales' => 0,
                'transaction_count' => 0,
                'payment_methods' => ['cash' => 0, 'card' => 0, 'gcash' => 0, 'maya' => 0],
                'start_time' => $this->formatManilaDateTime($shift->started_at),
                'transactions' => [],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Shift started successfully',
                'shift' => $shiftData,
                'user' => Auth::user(),
            ]);
        }

        // Check if user already has an active shift
        $activeShift = Shift::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        if ($activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active shift. Please close it first.'
            ], 400);
        }

        // Get current time in Asia/Manila and format as string to prevent timezone conversion
        $startTime = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');
        
        // Use DB::table()->insertGetId() to bypass Laravel's datetime casting
        // This prevents Laravel from converting timezone when creating
        $shiftId = DB::table('shifts')->insertGetId([
            'user_id' => Auth::id(),
            'cashier_name' => $request->cashier_name ?? Auth::user()->name,
            'start_time' => $startTime, // Store directly without timezone conversion
            'opening_cash' => $request->opening_cash,
            'expected_cash' => $request->opening_cash,
            'total_sales' => 0,
            'transaction_count' => 0,
            'payment_methods' => json_encode([
                'cash' => 0,
                'card' => 0,
                'gcash' => 0,
                'maya' => 0
            ]),
            'status' => 'active',
            'created_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
        ]);
        
        // Load the shift model for response
        $shift = Shift::find($shiftId);
        
        if (!$shift) {
            \Log::error('Failed to load shift after creation', ['shift_id' => $shiftId]);
            return response()->json([
                'success' => false,
                'message' => 'Shift created but failed to load. Please refresh.'
            ], 500);
        }

        // Convert shift to array and use created_at as start_time
        $shiftData = $shift->toArray();
        // Use created_at from database as the shift start time
        if ($shift->created_at) {
            try {
                // Get raw database value - created_at is stored in database timezone
                $rawCreatedAt = $shift->getRawOriginal('created_at');
                
                // Format as Asia/Manila time (YYYY-MM-DD HH:MM:SS)
                $createdAtLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');
                $shiftData['start_time'] = $createdAtLocal->format('Y-m-d H:i:s');
                
            } catch (\Exception $e) {
                // Fallback: use Carbon instance directly
                $shiftData['start_time'] = $shift->created_at->format('Y-m-d H:i:s');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Shift started successfully',
            'shift' => $shiftData,
            'user' => Auth::user()
        ]);
    }

    /**
     * End active shift
     */
    public function end(Request $request)
    {
        $request->validate([
            'shift_id' => 'nullable|integer',
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (Schema::hasTable('cashier_shifts')) {
            $shift = null;
            if ($request->filled('shift_id')) {
                $shift = CashierShift::query()
                    ->where('id', $request->shift_id)
                    ->where('cashier_user_id', Auth::id())
                    ->first();
            }
            if (! $shift) {
                $shift = CashierShift::query()
                    ->where('cashier_user_id', Auth::id())
                    ->whereNull('ended_at')
                    ->latest('started_at')
                    ->first();
            }

            if (! $shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shift found. Please start a shift first.',
                ], 404);
            }

            $shift->update([
                'closing_cash' => $request->closing_cash,
                'closing_notes' => $request->notes,
                // Store UTC instant; API responses format as Asia/Manila for display.
                'ended_at' => now(),
            ]);

            // Keep legacy shifts table in sync if it still has an open row for this cashier
            $this->closeLegacyActiveShiftForUser(Auth::id(), $request->closing_cash, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Shift ended successfully',
                'shift' => [
                    'id' => $shift->id,
                    'status' => 'closed',
                    'opening_cash' => (float) $shift->opening_cash,
                    'closing_cash' => (float) $shift->closing_cash,
                    'end_time' => $this->formatManilaDateTime($shift->ended_at),
                ],
            ]);
        }

        $shift = null;
        $userId = Auth::id();

        // First try to find shift by ID if provided
        if ($request->has('shift_id') && $request->shift_id) {
            $shiftId = $request->shift_id;
            
            // Try to find the shift by ID (regardless of status) - if it belongs to user, use it
            $shift = Shift::where('id', $shiftId)
                ->where('user_id', $userId)
                ->first();
            
            \Log::info('End shift attempt', [
                'shift_id' => $shiftId,
                'user_id' => $userId,
                'shift_found' => $shift ? true : false,
                'shift_status' => $shift ? $shift->status : null
            ]);
            }
        
        // If no shift found by ID (or no ID provided), try to find active shift
        if (!$shift) {
            $shift = Shift::where('user_id', $userId)
                ->where('status', 'active')
                ->first();
            
            \Log::info('Active shift lookup', [
                'user_id' => $userId,
                'active_shift_found' => $shift ? true : false,
                'shift_id' => $shift ? $shift->id : null
            ]);
        }

        // Final check if shift was found
        if (!$shift) {
            \Log::warning('No shift found to end', [
                'user_id' => $userId,
                'requested_shift_id' => $request->input('shift_id'),
                'has_shift_id' => $request->has('shift_id')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'No shift found. Please start a shift first.'
            ], 404);
        }

        // Get all sales for this shift
        $sales = Sale::where('shift_id', $shift->id)->get();
        
        // Total sales: Only cash sales (exclude E-Wallet and Check from total)
        $cashSales = $sales->where('payment_method', 'cash');
        $totalSales = $cashSales->sum('amount');
        $transactionCount = $cashSales->count();
        
        // Calculate total discounts: Only from cash sales
        $totalDiscounts = $cashSales->sum('discount');
        
        // Calculate payment methods breakdown - show all methods but only cash counts toward total
        $paymentMethods = [
            'cash' => $cashSales->sum('amount'),
            'card' => $sales->where('payment_method', 'card')->sum('amount'),
            'gcash' => $sales->where('payment_method', 'gcash')->sum('amount'),
            'maya' => $sales->where('payment_method', 'maya')->sum('amount'),
            'e_wallet' => $sales->where('payment_method', 'e_wallet')->sum('amount'),
            'check' => $sales->where('payment_method', 'check')->sum('amount'),
        ];
        
        // Calculate discounts per payment method
        $paymentMethodDiscounts = [
            'cash' => $cashSales->sum('discount'),
            'card' => $sales->where('payment_method', 'card')->sum('discount'),
            'gcash' => $sales->where('payment_method', 'gcash')->sum('discount'),
            'maya' => $sales->where('payment_method', 'maya')->sum('discount'),
            'e_wallet' => $sales->where('payment_method', 'e_wallet')->sum('discount'),
            'check' => $sales->where('payment_method', 'check')->sum('discount'),
        ];

        $expectedCash = $shift->opening_cash + $paymentMethods['cash'];
        $cashDifference = $request->closing_cash - $expectedCash;

        // Get current time in Asia/Manila and format as string to prevent timezone conversion
        // Store as string to bypass Laravel's datetime casting which converts to UTC
        $endTime = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');
        
        // CRITICAL: Preserve start_time from database to ensure it doesn't get changed
        // Use getRawOriginal to get the actual database value before any casting
        $preservedStartTime = $shift->getRawOriginal('start_time');
        
        // Use DB::table()->update() to bypass Laravel's datetime casting and store times directly
        // This prevents Laravel from converting timezone when updating
        DB::table('shifts')->where('id', $shift->id)->update([
            'end_time' => $endTime, // Store directly without timezone conversion
            'start_time' => $preservedStartTime, // Explicitly preserve start_time
            'closing_cash' => $request->closing_cash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $cashDifference,
            'total_sales' => $totalSales,
            'transaction_count' => $transactionCount,
            'payment_methods' => json_encode($paymentMethods),
            'status' => 'closed',
            'notes' => $request->notes,
            'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
        ]);
        
        // Refresh the model to get updated values
        $shift->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Shift ended successfully',
            'shift' => $shift,
            'summary' => [
                'total_sales' => $totalSales,
                'transaction_count' => $transactionCount,
                'expected_cash' => $expectedCash,
                'closing_cash' => $request->closing_cash,
                'cash_difference' => $cashDifference,
                'payment_methods' => $paymentMethods,
                'total_discounts' => $totalDiscounts,
                'payment_method_discounts' => $paymentMethodDiscounts,
            ]
        ]);
    }

    /**
     * Get shift report.
     */
    public function report(int|string $id): JsonResponse
    {
        if (Schema::hasTable('cashier_shifts')) {
            return $this->reportCashierShift($id);
        }

        return $this->reportLegacyShift($id);
    }

    private function reportCashierShift(int|string $id): JsonResponse
    {
        $shift = CashierShift::query()->with('cashier')->find($id);

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found',
            ], 404);
        }

        if ((int) $shift->cashier_user_id !== (int) Auth::id() && ! $this->currentUserIsAdmin()) {
            abort(403);
        }

        $formatted = $this->formatCashierShiftForAdmin($shift);
        $sales = Sale::query()
            ->with('user')
            ->where('cashier_shift_id', $shift->id)
            ->orderBy('sold_at')
            ->get()
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'shift' => $formatted,
            'sales' => $sales,
            'summary' => [
                'total_sales' => $formatted['total_sales'],
                'transaction_count' => $formatted['transaction_count'],
                'payment_methods' => $formatted['payment_methods'],
                'cash_difference' => $formatted['cash_difference'],
                'duration' => $formatted['duration_display'] ?? '0h 0m',
            ],
        ]);
    }

    private function reportLegacyShift(int|string $id): JsonResponse
    {
        $shift = Shift::with(['user', 'sales'])->find($id);

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found',
            ], 404);
        }

        if ((int) $shift->user_id !== (int) Auth::id() && ! $this->currentUserIsAdmin()) {
            abort(403);
        }

        $sales = Sale::query()
            ->where('shift_id', $shift->id)
            ->with('user')
            ->get()
            ->values()
            ->all();

        $shiftData = $shift->toArray();

        if ($shift->created_at) {
            try {
                $rawCreatedAt = $shift->getRawOriginal('created_at');
                $localStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');
                $shiftData['start_time'] = $localStartTime->format('Y-m-d H:i:s');
                $shiftData['start_time_full'] = $localStartTime->format('M d, Y h:i A');
            } catch (\Exception $e) {
                $shiftData['start_time'] = $shift->created_at->format('Y-m-d H:i:s');
                $shiftData['start_time_full'] = $shift->created_at->format('M d, Y h:i A');
            }
        }

        if ($shift->end_time) {
            try {
                $rawEndTime = $shift->getRawOriginal('end_time');
                $localEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawEndTime, 'Asia/Manila');
                $shiftData['end_time_full'] = $localEndTime->format('M d, Y h:i A');
            } catch (\Exception $e) {
                $shiftData['end_time_full'] = $shift->end_time->format('M d, Y h:i A');
            }
        }

        if ($shift->created_at) {
            try {
                $rawCreatedAt = $shift->getRawOriginal('created_at');
                $startTimeLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');

                if ($shift->end_time) {
                    $rawEndTime = $shift->getRawOriginal('end_time');
                    $endTimeLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawEndTime, 'Asia/Manila');
                    $durationMinutes = $startTimeLocal->diffInMinutes($endTimeLocal);
                } else {
                    $durationMinutes = $startTimeLocal->diffInMinutes(Carbon::now('Asia/Manila'));
                }

                $hours = (int) floor($durationMinutes / 60);
                $minutes = $durationMinutes % 60;
                $shiftData['duration_hours'] = $hours;
                $shiftData['duration_minutes'] = $minutes;
                $shiftData['duration_display'] = "{$hours}h {$minutes}m";
            } catch (\Exception $e) {
                $shiftData['duration_hours'] = 0;
                $shiftData['duration_minutes'] = 0;
                $shiftData['duration_display'] = '0h 0m';
            }
        }

        return response()->json([
            'success' => true,
            'shift' => $shiftData,
            'sales' => $sales,
            'summary' => [
                'total_sales' => $shift->total_sales,
                'transaction_count' => $shift->transaction_count,
                'payment_methods' => $shift->payment_methods,
                'cash_difference' => $shift->cash_difference,
                'duration' => $shiftData['duration_display'] ?? '0h 0m',
            ],
        ]);
    }

    /**
     * Get shift history
     */
    public function history(Request $request)
    {
        $query = Shift::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        $shifts = $query->paginate(10);

        return response()->json([
            'success' => true,
            'shifts' => $shifts
        ]);
    }

    /**
     * Get all shift reports for admin
     */
    public function adminReports(Request $request)
    {
        if (Schema::hasTable('cashier_shifts')) {
            return $this->adminReportsFromCashierShifts($request);
        }

        $query = Shift::with('user');

        // Apply period filter
        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Handle date filtering
        if ($startDate && $endDate) {
            // Use date range - parse dates and ensure proper time boundaries
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('start_time', [$start, $end]);
        } elseif ($startDate) {
            // Only start date provided - filter from start date to end of that day
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($startDate)->endOfDay();
            $query->whereBetween('start_time', [$start, $end]);
        } elseif ($endDate) {
            // Only end date provided - filter up to end of that day
            $end = Carbon::parse($endDate)->endOfDay();
            $query->where('start_time', '<=', $end);
        } else {
            // No date specified - use period defaults
            if ($period === 'daily') {
                $query->whereDate('start_time', Carbon::today());
            } elseif ($period === 'weekly') {
                $query->whereBetween('start_time', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
            } elseif ($period === 'biweekly') {
                $query->whereBetween('start_time', [
                    Carbon::now()->subDays(14)->startOfDay(),
                    Carbon::now()->endOfDay()
                ]);
            } elseif ($period === 'monthly') {
                $query->whereMonth('start_time', Carbon::now()->month)
                      ->whereYear('start_time', Carbon::now()->year);
            } elseif ($period === 'annual') {
                $query->whereYear('start_time', Carbon::now()->year);
            }
        }

        // Apply user filter
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Apply status filter
        // Default behavior: Show all shifts (active and closed) so ended shifts are visible
        // User can filter to see only "Active" or only "Closed" if needed
        if ($request->has('status') && $request->status !== '') {
            // If a specific status is selected (active or closed), filter by it
            $query->where('status', $request->status);
        }
        // If status is empty or not provided, show all shifts (no status filter)
        // This ensures that when a shift is ended, it will appear in the reports

        // Order by most recent first
        $query->orderBy('start_time', 'desc');

        // Get shifts (increased limit for better filtering)
        $shifts = $query->limit(500)->get();

        // Remove duplicates by shift ID to prevent double display
        // Use a more robust deduplication method that handles any edge cases
        $uniqueShifts = collect();
        $seenIds = [];
        
        foreach ($shifts as $shift) {
            $shiftId = $shift->id;
            if (!isset($seenIds[$shiftId])) {
                $seenIds[$shiftId] = true;
                $uniqueShifts->push($shift);
            }
        }
        
        $uniqueShifts = $uniqueShifts->values();
        
            // Format dates and times accurately on the server side - use created_at as start_time
        $formattedShifts = $uniqueShifts->map(function ($shift) {
            // Build array manually to avoid duplicate data
            // Use created_at as start_time for display
            $startTimeFormatted = null;
            $endTimeFormatted = null;
            
            // Use created_at as the start time
            if ($shift->created_at) {
                try {
                    // MySQL stores timestamp in server timezone (likely Asia/Manila)
                    // Raw value is already in correct timezone - use it directly
                    $rawCreatedAt = $shift->getRawOriginal('created_at');
                    $startTimeFormatted = $rawCreatedAt; // Already in Asia/Manila format
                } catch (\Exception $e) {
                    $startTimeFormatted = $shift->created_at->format('Y-m-d H:i:s');
                }
            }
            
            if ($shift->end_time) {
                try {
                    // MySQL stores timestamp in server timezone (likely Asia/Manila)
                    // Raw value is already in correct timezone - use it directly
                    $rawEndTime = $shift->getRawOriginal('end_time');
                    $endTimeFormatted = $rawEndTime; // Already in Asia/Manila format
                } catch (\Exception $e) {
                    $endTimeFormatted = $shift->end_time->format('Y-m-d H:i:s');
                }
            }
            
            // Use created_at as start_time for display
            $createdAtFormatted = null;
            if ($shift->created_at) {
                try {
                    $rawCreatedAt = $shift->getRawOriginal('created_at');
                    $createdAtFormatted = $rawCreatedAt; // Already in Asia/Manila format
                } catch (\Exception $e) {
                    $createdAtFormatted = $shift->created_at->format('Y-m-d H:i:s');
                }
            }
            
            $shiftArray = [
                'id' => $shift->id,
                'user_id' => $shift->user_id,
                'cashier_name' => $shift->cashier_name,
                'start_time' => $createdAtFormatted, // Use created_at as start_time (Asia/Manila time format)
                'end_time' => $endTimeFormatted, // Asia/Manila time format
                'opening_cash' => $shift->opening_cash,
                'expected_cash' => $shift->expected_cash,
                'total_sales' => $shift->total_sales,
                'transaction_count' => $shift->transaction_count,
                'cash_difference' => $shift->cash_difference,
                'status' => $shift->status,
                'payment_methods' => $shift->payment_methods,
                'notes' => $shift->notes,
                'created_at' => $shift->created_at ? $shift->created_at->toIso8601String() : null,
                'updated_at' => $shift->updated_at ? $shift->updated_at->toIso8601String() : null,
            ];
            
            // Add user data if available
            if ($shift->user) {
                $shiftArray['user'] = [
                    'id' => $shift->user->id,
                    'name' => $shift->user->name,
                ];
            }
            
            // Format start_time using created_at - MySQL stores timestamp in server timezone (likely Asia/Manila)
            if ($shift->created_at) {
                try {
                    // Get the raw database value - created_at is stored in database timezone
                    $rawCreatedAt = $shift->getRawOriginal('created_at');
                    
                    // Parse raw value as Asia/Manila (database stores in server timezone)
                    $localStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');
                    
                    $shiftArray['start_time'] = $localStartTime->format('Y-m-d H:i:s');
                    $shiftArray['start_time_formatted'] = $localStartTime->format('M d, Y');
                    $shiftArray['start_time_time'] = $localStartTime->format('h:i A');
                    $shiftArray['start_time_full'] = $localStartTime->format('M d, Y h:i A');
                } catch (\Exception $e) {
                    // Fallback: use Carbon instance directly
                    try {
                        $localStartTime = Carbon::parse($shift->created_at);
                        $shiftArray['start_time'] = $localStartTime->format('Y-m-d H:i:s');
                        $shiftArray['start_time_formatted'] = $localStartTime->format('M d, Y');
                        $shiftArray['start_time_time'] = $localStartTime->format('h:i A');
                        $shiftArray['start_time_full'] = $localStartTime->format('M d, Y h:i A');
                    } catch (\Exception $e2) {
                        // Last resort: format as-is
                        $shiftArray['start_time'] = $shift->created_at->format('Y-m-d H:i:s');
                        $shiftArray['start_time_formatted'] = $shift->created_at->format('M d, Y');
                        $shiftArray['start_time_time'] = $shift->created_at->format('h:i A');
                        $shiftArray['start_time_full'] = $shift->created_at->format('M d, Y h:i A');
                    }
                }
            } else {
                // Ensure these are always set even if created_at is null
                $shiftArray['start_time'] = null;
                $shiftArray['start_time_formatted'] = '-';
                $shiftArray['start_time_time'] = '-';
                $shiftArray['start_time_full'] = '-';
            }
            
            // Format end_time with accurate date and time
            // MySQL stores timestamp in server timezone (likely Asia/Manila) - use raw value directly
            if ($shift->end_time) {
                try {
                    // Get the raw database value - already in Asia/Manila timezone
                    $rawEndTime = $shift->getRawOriginal('end_time');
                    
                    // Parse raw value as Asia/Manila (database stores in server timezone)
                    $localEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $rawEndTime, 'Asia/Manila');
                    
                    $shiftArray['end_time_formatted'] = $localEndTime->format('M d, Y');
                    $shiftArray['end_time_time'] = $localEndTime->format('h:i A');
                    $shiftArray['end_time_full'] = $localEndTime->format('M d, Y h:i A');
                } catch (\Exception $e) {
                    // Fallback: use Carbon instance directly
                    try {
                        $localEndTime = Carbon::parse($shift->end_time);
                        $shiftArray['end_time_formatted'] = $localEndTime->format('M d, Y');
                        $shiftArray['end_time_time'] = $localEndTime->format('h:i A');
                        $shiftArray['end_time_full'] = $localEndTime->format('M d, Y h:i A');
                    } catch (\Exception $e2) {
                        // Last resort: format as-is
                        $shiftArray['end_time_formatted'] = $shift->end_time->format('M d, Y');
                        $shiftArray['end_time_time'] = $shift->end_time->format('h:i A');
                        $shiftArray['end_time_full'] = $shift->end_time->format('M d, Y h:i A');
                    }
                }
            } else {
                // Ensure these are always set even if end_time is null
                $shiftArray['end_time_formatted'] = '-';
                $shiftArray['end_time_time'] = '-';
                $shiftArray['end_time_full'] = '-';
            }
            
            // Calculate duration server-side using created_at as start time
            if ($shift->created_at) {
                try {
                    // MySQL stores timestamp in server timezone (likely Asia/Manila)
                    // Use created_at as the start time
                    $rawCreatedAt = $shift->getRawOriginal('created_at');
                    $startTimeLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawCreatedAt, 'Asia/Manila');
                    
                    if ($shift->end_time) {
                        $rawEndTime = $shift->getRawOriginal('end_time');
                        $endTimeLocal = Carbon::createFromFormat('Y-m-d H:i:s', $rawEndTime, 'Asia/Manila');
                        $durationMinutes = $startTimeLocal->diffInMinutes($endTimeLocal);
                    } else {
                        // Active shift - calculate from start to now (in Asia/Manila timezone)
                        $nowLocal = Carbon::now('Asia/Manila');
                        $durationMinutes = $startTimeLocal->diffInMinutes($nowLocal);
                    }
                    
                    $hours = floor($durationMinutes / 60);
                    $minutes = $durationMinutes % 60;
                    $shiftArray['duration_hours'] = $hours;
                    $shiftArray['duration_minutes'] = $minutes;
                    $shiftArray['duration_display'] = "{$hours}h {$minutes}m";
                } catch (\Exception $e) {
                    $shiftArray['duration_hours'] = 0;
                    $shiftArray['duration_minutes'] = 0;
                    $shiftArray['duration_display'] = '0h 0m';
                }
            } else {
                $shiftArray['duration_hours'] = 0;
                $shiftArray['duration_minutes'] = 0;
                $shiftArray['duration_display'] = '0h 0m';
            }
            
            return $shiftArray;
        });

        return response()->json([
            'success' => true,
            'shifts' => $formattedShifts
        ]);
    }

    /**
     * Clear active shift (for cleanup/debugging)
     */
    public function clearActive()
    {
        if (Schema::hasTable('cashier_shifts')) {
            $shift = CashierShift::query()
                ->where('cashier_user_id', Auth::id())
                ->whereNull('ended_at')
                ->latest('started_at')
                ->first();

            if ($shift) {
                $shift->update([
                    'ended_at' => now(),
                    'closing_notes' => 'Auto-closed (cleared by user)',
                    'closing_cash' => $shift->closing_cash ?? $shift->opening_cash,
                ]);
                $this->closeLegacyActiveShiftForUser(Auth::id(), $shift->closing_cash, 'Auto-closed (cleared by user)');

                return response()->json([
                    'success' => true,
                    'message' => 'Active shift cleared successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active shift found',
            ]);
        }

        $shift = Shift::where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        if ($shift) {
            // Get current time in Asia/Manila and format as string
            $endTime = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');
            
            $shift->update([
                'status' => 'closed',
                'end_time' => $endTime, // Store as formatted string in Asia/Manila timezone
                'notes' => 'Auto-closed (cleared by user)'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Active shift cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No active shift found'
        ]);
    }

    private function adminReportsFromCashierShifts(Request $request)
    {
        $query = CashierShift::query()->with('cashier');

        $period = $request->get('period', 'daily');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('started_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif ($startDate) {
            $query->whereBetween('started_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($startDate)->endOfDay(),
            ]);
        } elseif ($endDate) {
            $query->where('started_at', '<=', Carbon::parse($endDate)->endOfDay());
        } else {
            if ($period === 'daily') {
                $query->whereDate('started_at', Carbon::today());
            } elseif ($period === 'weekly') {
                $query->whereBetween('started_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            } elseif ($period === 'biweekly') {
                $query->whereBetween('started_at', [
                    Carbon::now()->subDays(14)->startOfDay(),
                    Carbon::now()->endOfDay(),
                ]);
            } elseif ($period === 'monthly') {
                $query->whereMonth('started_at', Carbon::now()->month)
                    ->whereYear('started_at', Carbon::now()->year);
            } elseif ($period === 'annual') {
                $query->whereYear('started_at', Carbon::now()->year);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('cashier_user_id', $request->user_id);
        }

        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'active') {
                $query->whereNull('ended_at');
            } elseif ($request->status === 'closed') {
                $query->whereNotNull('ended_at');
            }
        }

        $shifts = $query->orderByDesc('started_at')->limit(500)->get();

        $formattedShifts = $shifts->map(fn (CashierShift $shift) => $this->formatCashierShiftForAdmin($shift))->values();

        return response()->json([
            'success' => true,
            'shifts' => $formattedShifts,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCashierShiftForAdmin(CashierShift $shift): array
    {
        $sales = Sale::query()
            ->where('cashier_shift_id', $shift->id)
            ->with('salePayments.paymentMethod')
            ->get();

        $totalSales = Sale::sumAmount(Sale::query()->where('cashier_shift_id', $shift->id));
        $transactionCount = $sales->count();

        $paymentMethods = [
            'cash' => 0.0,
            'e_wallet' => 0.0,
            'check' => 0.0,
            'card' => 0.0,
        ];

        foreach ($sales as $sale) {
            foreach ($sale->salePayments as $payment) {
                $method = optional($payment->paymentMethod)->name ?? 'cash';
                if (! array_key_exists($method, $paymentMethods)) {
                    $paymentMethods[$method] = 0.0;
                }
                $paymentMethods[$method] += (float) $payment->amount;
            }
        }

        $status = $shift->ended_at ? 'closed' : 'active';
        $startParts = $this->formatManilaDisplayParts($shift->started_at);
        $endParts = $this->formatManilaDisplayParts($shift->ended_at);
        $start = $shift->started_at ? Carbon::parse($shift->started_at) : null;
        $end = $shift->ended_at ? Carbon::parse($shift->ended_at) : null;
        $durationEnd = $end ?: Carbon::now();
        $durationMinutes = $start ? $start->diffInMinutes($durationEnd) : 0;
        $hours = intdiv($durationMinutes, 60);
        $minutes = $durationMinutes % 60;

        $openingCash = (float) ($shift->opening_cash ?? 0);
        $closingCash = $shift->closing_cash !== null ? (float) $shift->closing_cash : null;
        $cashSales = (float) ($paymentMethods['cash'] ?? 0);
        $expectedCash = $openingCash + $cashSales;
        $cashDifference = $closingCash !== null ? ($closingCash - $expectedCash) : 0.0;

        $cashierName = trim((string) ($shift->cashier_name ?? ''))
            ?: (optional($shift->cashier)->name ?? 'N/A');

        return [
            'id' => $shift->id,
            'user_id' => $shift->cashier_user_id,
            'cashier_name' => $cashierName,
            'start_time' => $startParts['datetime'],
            'end_time' => $endParts['datetime'],
            'start_time_formatted' => $startParts['date'],
            'start_time_time' => $startParts['time'],
            'start_time_full' => $startParts['full'],
            'end_time_formatted' => $endParts['date'],
            'end_time_time' => $endParts['time'],
            'end_time_full' => $endParts['full'],
            'opening_cash' => $openingCash,
            'expected_cash' => $expectedCash,
            'closing_cash' => $closingCash,
            'total_sales' => $totalSales,
            'transaction_count' => $transactionCount,
            'cash_difference' => $cashDifference,
            'status' => $status,
            'payment_methods' => $paymentMethods,
            'notes' => $shift->closing_notes,
            'duration_hours' => $hours,
            'duration_minutes' => $minutes,
            'duration_display' => "{$hours}h {$minutes}m",
            'created_at' => optional($shift->created_at)?->toIso8601String(),
            'updated_at' => optional($shift->updated_at)?->toIso8601String(),
            'user' => $shift->cashier ? [
                'id' => $shift->cashier->id,
                'name' => $shift->cashier->name,
            ] : null,
        ];
    }

    private function closeLegacyActiveShiftForUser(int $userId, $closingCash = null, $notes = null): void
    {
        if (! Schema::hasTable('shifts')) {
            return;
        }

        $payload = [
            'status' => 'closed',
            'end_time' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
        ];

        if ($closingCash !== null && Schema::hasColumn('shifts', 'closing_cash')) {
            $payload['closing_cash'] = $closingCash;
        }
        if ($notes !== null && Schema::hasColumn('shifts', 'notes')) {
            $payload['notes'] = $notes;
        }

        DB::table('shifts')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update($payload);
    }

    private function currentUserIsAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }

        return strtolower((string) ($user->role ?? '')) === 'admin';
    }
}
