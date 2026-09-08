<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Location;
use App\Models\Insurance;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $query = Car::query()
            ->where('is_available', true)
            ->with('location');

        /* 🔍 Search: brand or model */
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%$search%")
                  ->orWhere('model', 'like', "%$search%");
            });
        }

        /* 📍 Pick-up Location */
        if ($request->filled('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->location . '%');
            });
        }

        /* 💰 Price Range */
        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        /* 🚗 Car Category (type) */
        if ($request->filled('type')) {
            $query->whereIn('type', (array) $request->type);
        }

        /* ⚙️ Transmission */
        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        /* 📅 Exclude cars booked in this date range */
        if ($request->filled('pickup_date') && $request->filled('return_date')) {
            $pickup = $request->pickup_date;
            $return = $request->return_date;

            $query->whereDoesntHave('bookings', function ($q) use ($pickup, $return) {
                $q->whereIn('status', ['confirmed', 'pending'])
                  ->where(function ($q2) use ($pickup, $return) {
                      $q2->whereBetween('start_date', [$pickup, $return])
                         ->orWhereBetween('end_date', [$pickup, $return])
                         ->orWhere(function ($q3) use ($pickup, $return) {
                             $q3->where('start_date', '<=', $pickup)
                                ->where('end_date', '>=', $return);
                         });
                  });
            });
        }

        /* 📄 Pagination */
        $cars = $query
            ->orderBy('created_at', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('cars.index', [
            'cars'        => $cars,
            'locations'   => Location::where('is_active', true)->get(),
            'types'       => Car::select('type')->distinct()->pluck('type'),
            'brands'      => Car::select('brand')->distinct()->pluck('brand'),
            'minPrice'    => Car::min('price_per_day'),
            'maxPrice'    => Car::max('price_per_day'),
            'pickup_date' => $request->pickup_date,
            'return_date' => $request->return_date,
            'pickup_time' => $request->pickup_time,
            'return_time' => $request->return_time,
            'location'    => $request->location,
        ]);
    }

    /**
     * Search from home form — redirects to index with GET params
     */
    public function search(Request $request)
    {
         // ❌ block any city except meknes
    if ($request->location !== 'meknes') {
        return redirect()->back()->with('error', 'Only Meknès is available حاليا');
    }
        
        return redirect()->route('cars.index', array_filter([
            'location'    => $request->location,
            'pickup_date' => $request->pickup_date,
            'return_date' => $request->return_date,
            'pickup_time' => $request->pickup_time,
            'return_time' => $request->return_time,

            
        ]));
    }
    

    /**
     * 🚗 Car Details Page with Booking Calculator
     */
    public function details(Request $request, Car $car)
    {
        $car->load(['location', 'reviews']);

        $insurance = null;
        $insurancePrice = 0;

        if (session()->has('insurance_id')) {
            $insurance = Insurance::find(session('insurance_id'));
            $insurancePrice = $insurance?->daily_rate ?? 0;
        }

        $pickupDate = $request->get('pickup_date');
        $returnDate = $request->get('return_date');
        $pickupTime = $request->get('pickup_time');
        $returnTime = $request->get('return_time');

        $rentalDays = 1;
        $carTotal = $car->price_per_day;
        $total = $carTotal + $insurancePrice;

        if ($pickupDate && $returnDate) {
            $rentalDays = max(1, (int) ceil(
                (strtotime($returnDate) - strtotime($pickupDate)) / 86400
            ));

            $carTotal = $car->price_per_day * $rentalDays;
            $total = $carTotal + $insurancePrice;
        }

        // ✅ GET APPROVED REVIEWS
        $reviews = $car->reviews()
            ->where('is_approved', true)
            ->with('user')
            ->latest()
            ->get();

        // ✅ CALCULATE REVIEW STATS
        $reviewStats = [
            'count'        => $reviews->count(),
            'average'      => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0,
            'distribution' => [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ],
        ];

        $averageRating = $reviewStats['average'];
        $reviewCount   = $reviewStats['count'];

        $bookedRanges = $car->bookings()
            ->get(['start_date', 'end_date'])
            ->map(function ($b) {
                return [
                    'from' => $b->start_date,
                    'to'   => $b->end_date,
                ];
            });

        return view('cars.details', compact(
            'car',
            'insurance',
            'insurancePrice',
            'rentalDays',
            'carTotal',
            'total',
            'pickupDate',
            'returnDate',
            'pickupTime',
            'returnTime',
            'averageRating',
            'reviewCount',
            'reviews',
            'reviewStats',
            'bookedRanges'
        ));
    }
}