<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display all locations
     */
    public function index()
    {
        $locations = Location::latest()->paginate(15);

        $stats = [
            'total' => Location::count(),
            'active' => Location::where('is_active', true)->count(),
            'inactive' => Location::where('is_active', false)->count(),
        ];

        return view('admin.locations.index', compact('locations', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.locations.create');
    }

    /**
     * Store new location
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
        ]);

        Location::create($data);

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location added successfully!');
    }

    /**
     * Show edit form
     */
    public function edit(Location $location)
    {
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update location
     */
    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
        ]);

        $location->update($data);

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location updated successfully!');
    }

    /**
     * Delete location
     */
    public function destroy(Location $location)
    {
        // Check if location has cars
        if ($location->cars()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete location with assigned cars.']);
        }

        // Check if location has bookings
        if ($location->bookings()->count() > 0 || $location->dropoffBookings()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete location with existing bookings.']);
        }

        $location->delete();

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location deleted successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Location $location)
    {
        $location->update([
            'is_active' => ! $location->is_active,
        ]);

        $status = $location->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Location {$status} successfully!");
    }
}
