<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminCarController extends Controller
{
    public function index()
    {
        $cars = Car::latest()->paginate(10);
        return view('admin.cars.index', compact('cars'));
    }

    public function create()
    {
        $locations = Location::all();
        return view('admin.cars.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer',
            'type' => 'required|string',
            'transmission' => 'required|in:Automatic,Manual',
            'fuel_type' => 'required|in:Petrol,Diesel,Hybrid,Electric',
            'seats' => 'required|integer',
            'doors' => 'required|integer',
            'luggage' => 'required|integer',
            'price_per_day' => 'required|numeric',
            'image' => 'required|image|max:10240',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|max:10240', // 10MB لكل صورة

            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'is_available' => 'nullable|boolean',
            'location_id' => 'required|exists:locations,id',

            'insurances' => 'nullable|array',
            'insurances.*' => 'exists:insurances,id',
            'default_insurance' => 'nullable|exists:insurances,id',
        ]);

        // Force checkbox default
        $data['is_available'] = $request->boolean('is_available');

        // Main image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('cars', 'public');
            $data['image'] = basename($path);
        }

        // Gallery images
        $gallery = [];
        if ($request->hasFile('gallery')) {
        $gallery = [];

        foreach ($request->file('gallery') as $file) {
            $path = $file->store('cars', 'public');
            $gallery[] = basename($path);
        }

        $data['gallery'] = $gallery;
}


        if (isset($data['features'])) {
            $data['features'] = array_values($data['features']);
        }

       $car = Car::create($data);

        // ✅ Attach insurances to car
    if ($request->has('insurances') && count($request->insurances) > 0) {
        $insurances = [];
        
        foreach ($request->insurances as $insuranceId) {
            $insurances[$insuranceId] = [
                'is_default' => ($insuranceId == $request->default_insurance),
                'price_per_day' => 0
            ];
        }
        
        $car->insurances()->attach($insurances);
        
        \Log::info('✅ Insurances attached to car', [
            'car_id' => $car->id,
            'insurances' => $insurances
        ]);
    }

    
        return redirect()->route('admin.cars.index')->with('success', 'Car created successfully.');
    }


    
    public function edit(Car $car)
    {
        $locations = Location::all();
        return view('admin.cars.edit', compact('car', 'locations'));
    }

    public function update(Request $request, Car $car)
    {
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer',
            'type' => 'required|string',
            'transmission' => 'required|in:Automatic,Manual',
            'fuel_type' => 'required|in:Petrol,Diesel,Hybrid,Electric',
            'seats' => 'required|integer',
            'doors' => 'required|integer',
            'luggage' => 'required|integer',
            'price_per_day' => 'required|numeric',
            'image' => 'nullable|image|max:10240',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|max:10240',

            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'is_available' => 'nullable|boolean',
            'location_id' => 'required|exists:locations,id',

            'insurances' => 'nullable|array',
            'insurances.*' => 'exists:insurances,id',
            'default_insurance' => 'nullable|exists:insurances,id',
            'deposit_amount'      => 'nullable|numeric|min:0',
            'minimum_age'         => 'nullable|integer|min:18|max:30',
            'fuel_policy'         => 'nullable|string|max:100',
            'cancellation_policy' => 'nullable|string|max:255',
            'required_documents'  => 'nullable|array',
        ]);

        $data['is_available'] = $request->boolean('is_available');

        // Replace main image
        if ($request->hasFile('image')) {
            if ($car->image) {
                Storage::disk('public')->delete('cars/' . $car->image);
            }

            $path = $request->file('image')->store('cars', 'public');
            $data['image'] = basename($path);
        }

        // Add gallery images
        $gallery = $car->gallery ?? [];

           // Upload gallery images
$existingGallery = $car->gallery ?? [];

if ($request->hasFile('gallery')) {
    foreach ($request->file('gallery') as $file) {
        $path = $file->store('cars', 'public');
        $existingGallery[] = basename($path);
    }

    $data['gallery'] = $existingGallery;
}



        if (isset($data['features'])) {
            $data['features'] = array_values($data['features']);
        }

        $car->update($data);

         // ✅ Sync insurances
    if ($request->has('insurances') && count($request->insurances) > 0) {
        $insurances = [];
        
        foreach ($request->insurances as $insuranceId) {
            $insurances[$insuranceId] = [
                'is_default' => ($insuranceId == $request->default_insurance)
            ];
        }
        
        $syncData = [];
foreach ($request->insurances ?? [] as $insuranceId) {
    $syncData[$insuranceId] = [
        'is_default'    => ($request->default_insurance == $insuranceId) ? 1 : 0,
        'price_per_day' => 0, // ← زيد هاد السطر
    ];
}
$car->insurances()->sync($syncData);
        
        \Log::info('✅ Insurances synced for car', [
            'car_id' => $car->id,
            'insurances' => $insurances
        ]);
    } else {
        // If no insurances selected, detach all
        $car->insurances()->detach();
    }

   

        return redirect()->route('admin.cars.index')->with('success', 'Car updated successfully.');
    }

    public function destroy(Car $car)
    {
        if ($car->image) {
            Storage::disk('public')->delete('cars/' . $car->image);
        }

        if (is_array($car->gallery)) {
            foreach ($car->gallery as $img) {
                Storage::disk('public')->delete('cars/' . $img);
            }
        }

        $car->delete();

        return redirect()->route('admin.cars.index')->with('success', 'Car deleted successfully.');
    }
}
