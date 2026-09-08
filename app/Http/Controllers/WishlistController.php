<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::where('user_id', Auth::id())
            ->with('car.location')
            ->latest()
            ->get();

        return view('wishlist.index', compact('wishlists'));
    }

    public function store(Car $car)
    {
        if (! Auth::check()) {
            return back()->with('error', 'Please login to add cars to wishlist.');
        }

        $existing = Wishlist::where('user_id', Auth::id())
            ->where('car_id', $car->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'This car is already in your wishlist.');
        }

        Wishlist::create([
            'user_id' => Auth::id(),
            'car_id' => $car->id,
        ]);

        return back()->with('success', 'Car added to wishlist.');
    }

    public function destroy(Car $car)
    {
        if (! Auth::check()) {
            return back()->with('error', 'Please login to manage your wishlist.');
        }

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('car_id', $car->id)
            ->first();

        if (! $wishlist) {
            return back()->with('error', 'Car not found in your wishlist.');
        }

        $wishlist->delete();

        return back()->with('success', 'Car removed from wishlist.');
    }
}
