<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Insurance;
use App\Models\Location;
use App\Services\Pricing\BookingPricingService;
use App\Services\PublicSiteDataService;
use App\Services\RentalBusinessRules;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function __construct(
        private readonly BookingPricingService $pricingService,
        private readonly RentalBusinessRules $rentalRules
    ) {}

    public function show(Request $request, Car $car)
    {
        return $this->details($request, $car);
    }

    public function index(Request $request, PublicSiteDataService $publicSiteData)
    {
        $query = Car::query()
            ->where('is_available', true)
            ->with('location');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%$search%")
                    ->orWhere('model', 'like', "%$search%");
            });
        }

        if ($request->filled('location')) {
            $location = $request->input('location');

            if (is_numeric($location)) {
                $query->where('location_id', (int) $location);
            } else {
                $query->whereHas('location', function ($q) use ($location) {
                    $q->where('name', 'like', '%'.$location.'%')
                        ->orWhere('city', 'like', '%'.$location.'%');
                });
            }
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->string('brand')->toString());
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        if ($request->filled('type')) {
            $query->whereIn('type', (array) $request->type);
        }

        if ($request->filled('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        if ($request->filled('pickup_date') && $request->filled('return_date')) {
            $pickup = $request->pickup_date;
            $return = $request->return_date;

            $query->whereDoesntHave('bookings', function ($q) use ($pickup, $return) {
                $q->activeOrReserved()
                    ->overlapping($pickup, $return);
            });
        }

        $cars = $query
            ->orderBy('created_at', 'desc')
            ->paginate(9)
            ->withQueryString();

        return view('cars.index', [
            'cars' => $cars,
            'availableCarsCount' => $publicSiteData->availableCarsCount(),
            'locations' => $publicSiteData->activeLocations(),
            'types' => Car::available()
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type'),
            'brands' => $publicSiteData->availableBrands(),
            'minPrice' => Car::min('price_per_day'),
            'maxPrice' => Car::max('price_per_day'),
            'pickup_date' => $request->pickup_date,
            'return_date' => $request->return_date,
            'pickup_time' => $request->pickup_time,
            'return_time' => $request->return_time,
            'location' => $request->location,
        ]);
    }

    public function search(Request $request)
    {
        $location = $request->input('location');

        $activeLocation = Location::active()
            ->when(is_numeric($location), fn ($query) => $query->whereKey((int) $location))
            ->when(! is_numeric($location), function ($query) use ($location) {
                $query->where(function ($query) use ($location) {
                    $query->where('city', 'like', '%'.$location.'%')
                        ->orWhere('name', 'like', '%'.$location.'%');
                });
            })
            ->first();

        if (! $activeLocation) {
            return redirect()->back()->with('error', __('messages.invalid_city'));
        }

        return redirect()->route('cars.index', array_filter([
            'location' => $activeLocation->id,
            'pickup_date' => $request->pickup_date,
            'return_date' => $request->return_date,
            'pickup_time' => $request->pickup_time,
            'return_time' => $request->return_time,
        ]));
    }

    public function details(Request $request, Car $car)
    {
        $car->load(['location', 'reviews']);

        $insurance = null;
        $insurancePrice = 0;

        if (session()->has('insurance_id')) {
            $insurance = Insurance::find(session('insurance_id'));
            $insurancePrice = $insurance?->fixed_price ?? 0;
        }

        $pickupDate = $request->get('pickup_date');
        $returnDate = $request->get('return_date');
        $pickupTime = $request->get('pickup_time');
        $returnTime = $request->get('return_time');

        $rentalDays = 1;
        $carTotal = $this->pricingService->calculateRentalAmount((float) $car->price_per_day, $rentalDays);
        $total = $this->pricingService->breakdown($carTotal, (float) $insurancePrice)['total_amount'];

        if ($pickupDate && $returnDate) {
            $rentalDays = $this->pricingService->calculateRentalDays($pickupDate, $returnDate);
            $carTotal = $this->pricingService->calculateRentalAmount((float) $car->price_per_day, $rentalDays);
            $total = $this->pricingService->breakdown($carTotal, (float) $insurancePrice)['total_amount'];
        }

        $reviews = $car->reviews()
            ->where('is_approved', true)
            ->with('user')
            ->latest()
            ->get();

        $reviewStats = [
            'count' => $reviews->count(),
            'average' => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0,
            'distribution' => [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ],
        ];

        $averageRating = $reviewStats['average'];
        $reviewCount = $reviewStats['count'];

        $bookedRanges = $car->bookings()
            ->activeOrReserved()
            ->get(['start_date', 'end_date'])
            ->map(function ($b) {
                return [
                    'from' => $b->start_date,
                    'to' => $b->end_date,
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
        ) + [
            'bookingMinDays' => $this->rentalRules->bookingMinDays(),
            'bookingMaxDays' => $this->rentalRules->bookingMaxDays(),
            'maxAdvanceBookingDays' => $this->rentalRules->maxAdvanceBookingDays(),
            'datepickerMaxDate' => now()->copy()
                ->startOfDay()
                ->addDays($this->rentalRules->maxAdvanceBookingDays() + $this->rentalRules->bookingMaxDays())
                ->toDateString(),
            'maxAdvancePickupDate' => $this->rentalRules->maxAdvancePickupDate()->toDateString(),
            'effectiveMinimumDriverAge' => $this->rentalRules->effectiveMinimumDriverAge($car),
            'globalCancellationPolicy' => [
                'full_refund_hours' => (int) setting('refund_cancellation_window_hours', 48),
                'partial_refund_hours' => (int) setting('refund_partial_refund_cutoff_hours', 24),
                'partial_refund_percentage' => (float) setting('refund_partial_percentage', 50),
            ],
        ]);
    }
}
