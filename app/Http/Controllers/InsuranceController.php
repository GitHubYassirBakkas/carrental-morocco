<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Insurance;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    /**
     * Show insurance selection page
     */
    public function select(Car $car)
    {
        // Get insurances assigned to this car, or all active insurances
        $insurances = $car->available_insurances;

        return view('insurance.select', compact('car', 'insurances'));
    }

    /**
     * Store selected insurance and redirect to car details
     */
    public function store(Request $request, Car $car)
    {
        $request->validate([
            'insurance_id' => 'required|exists:insurances,id',
        ]);

        $insurance = Insurance::findOrFail($request->insurance_id);

        // Save to session
        session([
            'insurance_id'         => $insurance->id,
            'insurance_name'       => $insurance->name,
            'insurance_type'       => $insurance->type,
            'insurance_price'      => $insurance->daily_rate,
            'insurance_max_coverage' => $insurance->max_coverage,
            'insurance_deductible' => $insurance->deductible,
            'insurance_features'   => $insurance->features,
        ]);

        return redirect()->route('cars.details', $car)
            ->with('success', 'Insurance selected: ' . $insurance->name);
    }
}