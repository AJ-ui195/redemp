<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers (admin)
     */
    public function index(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            $customers = Customer::orderBy('registered_name')->get();
            return response()->json([
                'success' => true,
                'customers' => $customers
            ]);
        }
        
        $customers = Customer::orderBy('registered_name')->paginate(20);
        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer (admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'registered_name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:50'],
            'business_address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $customer = Customer::create($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer
            ]);
        }

        return back()->with('status', 'Customer created successfully.');
    }

    /**
     * Update the specified customer (admin)
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'registered_name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:50'],
            'business_address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $customer->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer->fresh()
            ]);
        }

        return back()->with('status', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer (admin)
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully.'
            ]);
        }

        return back()->with('status', 'Customer deleted successfully.');
    }

    /**
     * Search customers (for cashier)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            if (empty(trim((string) $query))) {
                $customers = Customer::query()
                    ->where('is_active', true)
                    ->orderBy('registered_name')
                    ->limit(200)
                    ->get()
                    ->map(function ($customer) {
                        return [
                            'id' => $customer->id,
                            'registered_name' => $customer->registered_name ?? '',
                            'tin' => $customer->tin ?? null,
                            'business_address' => $customer->business_address ?? null,
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'customers' => $customers,
                ]);
            }

            $customers = Customer::where('is_active', true)
                ->where(function($q) use ($query) {
                    $q->where('registered_name', 'LIKE', "%{$query}%")
                      ->orWhere('tin', 'LIKE', "%{$query}%")
                      ->orWhere('business_address', 'LIKE', "%{$query}%");
                })
                ->orderBy('registered_name')
                ->limit(20)
                ->get()
                ->map(function($customer) {
                    return [
                        'id' => $customer->id,
                        'registered_name' => $customer->registered_name ?? '',
                        'tin' => $customer->tin ?? null,
                        'business_address' => $customer->business_address ?? null,
                    ];
                });

            return response()->json([
                'success' => true,
                'customers' => $customers
            ]);
        } catch (\Exception $e) {
            \Log::error('Error searching customers: ' . $e->getMessage(), [
                'query' => $request->get('q'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error searching customers: ' . $e->getMessage(),
                'customers' => []
            ], 500);
        }
    }

    /**
     * Get customer by ID (for admin and cashier)
     */
    public function show(Customer $customer)
    {
        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'registered_name' => $customer->registered_name,
                'tin' => $customer->tin,
                'business_address' => $customer->business_address,
                'is_active' => $customer->is_active,
            ]
        ]);
    }
}
