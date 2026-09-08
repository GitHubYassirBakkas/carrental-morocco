<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Review;
use App\Services\PublicSiteDataService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(PublicSiteDataService $publicSiteData): View
    {
        $featuredCars = Car::available()
            ->select([
                'id',
                'brand',
                'model',
                'year',
                'type',
                'transmission',
                'seats',
                'price_per_day',
                'image',
                'created_at',
            ])
            ->latest()
            ->limit(6)
            ->get();

        $representativeCarIds = Car::available()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->selectRaw('MAX(id) as id')
            ->groupBy('type');

        $carTypes = Car::available()
            ->select([
                'id',
                'type',
                'image',
                'created_at',
            ])
            ->whereIn('id', $representativeCarIds)
            ->orderBy('type')
            ->get()
            ->map(fn (Car $car) => [
                'type' => $car->type,
                'title' => $this->formatTypeTitle($car->type),
                'image_url' => $car->image_url,
            ]);

        $testimonials = Review::approved()
            ->select([
                'id',
                'user_id',
                'car_id',
                'rating',
                'comment',
                'created_at',
            ])
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->whereHas('user')
            ->whereHas('car')
            ->with([
                'user:id,name,profile_photo_path',
                'car:id,brand,model,year',
            ])
            ->latest()
            ->limit(6)
            ->get();

        return view('home.index', array_merge(
            compact('featuredCars', 'carTypes', 'testimonials'),
            $publicSiteData->summary(),
        ));
    }

    private function formatTypeTitle(string $type): string
    {
        return match ($type) {
            'Family / Van' => 'Family / Van',
            'SUV' => 'SUV Cars',
            default => $type.' Cars',
        };
    }
}
