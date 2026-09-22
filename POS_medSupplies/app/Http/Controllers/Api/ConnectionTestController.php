<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Barcode;

class ConnectionTestController extends Controller
{
    /**
     * Simple connection test endpoint
     * Returns basic info to verify the app can reach the server
     */
    public function test()
    {
        // Get the actual server IP address
        $serverIp = request()->server('SERVER_ADDR') ?: request()->ip();
        
        // If we're behind a proxy or NAT, try to get the real IP
        if ($serverIp === '127.0.0.1' || $serverIp === '::1') {
            // Try to get the actual network IP
            $hostname = gethostname();
            $localIp = gethostbyname($hostname);
            if ($localIp !== $hostname && $localIp !== '127.0.0.1') {
                $serverIp = $localIp;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Server is reachable!',
            'timestamp' => now()->toDateTimeString(),
            'server_ip' => $serverIp,
            'server_url' => request()->fullUrl(),
            'note' => 'Update scanner app API_BASE_URL to match server_ip above',
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Comprehensive connection test
     * Tests database, barcodes table, and returns diagnostic info
     */
    public function fullTest()
    {
        $results = [
            'success' => true,
            'timestamp' => now()->toDateTimeString(),
            'server_ip' => request()->ip(),
            'server_url' => request()->fullUrl(),
            'tests' => []
        ];

        // Test 1: Database Connection
        try {
            DB::connection()->getPdo();
            $results['tests']['database'] = [
                'status' => 'connected',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $results['tests']['database'] = [
                'status' => 'failed',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $results['success'] = false;
        }

        // Test 2: Barcodes Table
        try {
            $barcodeCount = Barcode::count();
            $sampleBarcodes = Barcode::limit(3)->get(['barcode_value', 'item_name', 'price']);
            $results['tests']['barcodes_table'] = [
                'status' => 'accessible',
                'total_barcodes' => $barcodeCount,
                'sample_barcodes' => $sampleBarcodes,
                'message' => "Found {$barcodeCount} barcodes in database"
            ];
        } catch (\Exception $e) {
            $results['tests']['barcodes_table'] = [
                'status' => 'failed',
                'message' => 'Cannot access barcodes table: ' . $e->getMessage()
            ];
            $results['success'] = false;
        }

        // Test 3: CORS Headers
        $results['tests']['cors'] = [
            'status' => 'configured',
            'message' => 'CORS headers are set'
        ];

        return response()->json($results)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Test barcode lookup with a specific value
     */
    public function testBarcodeLookup(Request $request)
    {
        $barcodeValue = $request->get('value', '');
        
        if (empty($barcodeValue)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a barcode value: ?value=YOUR_BARCODE'
            ], 400)->header('Access-Control-Allow-Origin', '*');
        }

        try {
            $barcode = Barcode::where('barcode_value', $barcodeValue)->first();
            
            if ($barcode) {
                return response()->json([
                    'success' => true,
                    'found' => true,
                    'barcode' => [
                        'barcode_value' => $barcode->barcode_value,
                        'item_name' => $barcode->item_name,
                        'price' => $barcode->price,
                        'price_type' => $barcode->price_type,
                        'unit' => $barcode->unit,
                        'expiration_date' => $barcode->expiration_date ? $barcode->expiration_date->format('Y-m-d') : null,
                    ]
                ])->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'Barcode not found in database',
                'searched_value' => $barcodeValue,
                'total_barcodes_in_db' => Barcode::count(),
            ])->header('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Health check endpoint for monitoring server stability
     * Returns server status, uptime info, and system health
     */
    public function healthCheck()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toDateTimeString(),
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'checks' => []
        ];

        // Check 1: Database Connection
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'failed',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // Check 2: Disk Space
        try {
            $freeBytes = disk_free_space(base_path());
            $totalBytes = disk_total_space(base_path());
            $freePercent = ($freeBytes / $totalBytes) * 100;
            
            $health['checks']['disk'] = [
                'status' => $freePercent > 10 ? 'ok' : 'warning',
                'free_space' => $this->formatBytes($freeBytes),
                'total_space' => $this->formatBytes($totalBytes),
                'free_percent' => round($freePercent, 2),
                'message' => $freePercent > 10 
                    ? 'Sufficient disk space available' 
                    : 'Low disk space warning'
            ];
            
            if ($freePercent <= 10) {
                $health['status'] = 'warning';
            }
        } catch (\Exception $e) {
            $health['checks']['disk'] = [
                'status' => 'unknown',
                'message' => 'Could not check disk space'
            ];
        }

        // Check 3: Storage Writable
        try {
            $storagePath = storage_path();
            $isWritable = is_writable($storagePath);
            $health['checks']['storage'] = [
                'status' => $isWritable ? 'ok' : 'failed',
                'writable' => $isWritable,
                'path' => $storagePath,
                'message' => $isWritable 
                    ? 'Storage directory is writable' 
                    : 'Storage directory is not writable'
            ];
            
            if (!$isWritable) {
                $health['status'] = 'unhealthy';
            }
        } catch (\Exception $e) {
            $health['checks']['storage'] = [
                'status' => 'unknown',
                'message' => 'Could not check storage permissions'
            ];
        }

        // Check 4: Cache
        try {
            $cacheDriver = config('cache.default');
            $health['checks']['cache'] = [
                'status' => 'ok',
                'driver' => $cacheDriver,
                'message' => 'Cache is configured'
            ];
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'warning',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : ($health['status'] === 'warning' ? 200 : 503);

        return response()->json($health, $statusCode)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

