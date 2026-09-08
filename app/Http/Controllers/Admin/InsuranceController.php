<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Insurance;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    /**
     * Display listing of insurances
     */
    public function index()
    {
        $insurances = Insurance::withCount('cars')
            ->orderBy('sort_order')
            ->orderBy('daily_rate')
            ->get();

        return view('admin.insurances.index', compact('insurances'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.insurances.create');
    }

    /**
     * Store new insurance
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:basic,standard,premium',
            'description' => 'required|string',
            'daily_rate' => 'required|numeric|min:0',
            'max_coverage' => 'required|numeric|min:0',
            'deductible' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle features
        if ($request->has('features')) {
            $validated['features'] = array_filter($request->features);
        }

        // Set defaults
        $validated['is_active'] = $request->has('is_active');
        $validated['excess_fee'] = $validated['deductible']; // Keep for compatibility
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Insurance::create($validated);

        return redirect()->route('admin.insurances.index')
            ->with('success', 'Insurance created successfully!');
    }

    /**
     * Show edit form
     */
    public function edit(Insurance $insurance)
    {
        return view('admin.insurances.edit', compact('insurance'));
    }

    /**
     * Update insurance
     */
    public function update(Request $request, Insurance $insurance)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:basic,standard,premium',
            'description' => 'required|string',
            'daily_rate' => 'required|numeric|min:0',
            'max_coverage' => 'required|numeric|min:0',
            'deductible' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle features
        if ($request->has('features')) {
            $validated['features'] = array_filter($request->features);
        }

        // Set values
        $validated['is_active'] = $request->has('is_active');
        $validated['excess_fee'] = $validated['deductible'];
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $insurance->update($validated);

        return redirect()->route('admin.insurances.index')
            ->with('success', 'Insurance updated successfully!');
    }

    /**
     * Delete insurance
     */
    public function destroy(Insurance $insurance)
    {
        // Check if insurance is used in any bookings
        if ($insurance->cars()->count() > 0) {
            return redirect()->route('admin.insurances.index')
                ->with('error', 'Cannot delete insurance that is assigned to cars!');
        }

        $insurance->delete();

        return redirect()->route('admin.insurances.index')
            ->with('success', 'Insurance deleted successfully!');
    }
}