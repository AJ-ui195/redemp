<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get VAT rate
     */
    public function getVatRate()
    {
        $vatRate = Setting::get('vat_rate', 12);
        
        return response()->json([
            'success' => true,
            'vat_rate' => $vatRate
        ]);
    }

    /**
     * Update VAT rate
     */
    public function updateVatRate(Request $request)
    {
        $request->validate([
            'vat_rate' => 'required|numeric|min:0|max:100'
        ]);

        Setting::set('vat_rate', $request->vat_rate, 'number', 'VAT rate percentage');

        return response()->json([
            'success' => true,
            'message' => 'VAT rate updated successfully',
            'vat_rate' => $request->vat_rate
        ]);
    }

    /**
     * Get all tax settings
     */
    public function getTaxSettings()
    {
        return response()->json([
            'success' => true,
            'vat_rate' => Setting::get('vat_rate', 12),
            'max_discount' => Setting::get('max_discount', 20),
        ]);
    }

    /**
     * Update tax settings
     */
    public function updateTaxSettings(Request $request)
    {
        $request->validate([
            'vat_rate' => 'required|numeric|min:0|max:100',
            'max_discount' => 'nullable|numeric|min:0|max:100',
        ]);

        Setting::set('vat_rate', $request->vat_rate, 'number', 'VAT rate percentage');
        
        if ($request->has('max_discount')) {
            Setting::set('max_discount', $request->max_discount, 'number', 'Maximum discount percentage');
        }

        return response()->json([
            'success' => true,
            'message' => 'Tax settings updated successfully',
            'vat_rate' => $request->vat_rate,
            'max_discount' => $request->max_discount ?? Setting::get('max_discount', 20),
        ]);
    }
}
