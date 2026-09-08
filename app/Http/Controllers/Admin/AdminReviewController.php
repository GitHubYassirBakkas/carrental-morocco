<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    /**
     * Display all reviews
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'car', 'booking'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('car', function($q) use ($search) {
                    $q->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%");
                })
                ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        $reviews = $query->paginate(20);

        // Stats
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('is_approved', false)->count(),
            'approved' => Review::where('is_approved', true)->count(),
            'average_rating' => round(Review::where('is_approved', true)->avg('rating'), 1),
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Show review details
     */
    public function show(Review $review)
    {
        $review->load(['user', 'car', 'booking']);
        
        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve review
     */
    public function approve(Review $review)
    {
        $review->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Review approved successfully!');
    }

    /**
     * Reject review
     */
    public function reject(Review $review)
    {
        $review->update([
            'is_approved' => false,
            'approved_at' => null,
        ]);

        return back()->with('success', 'Review rejected.');
    }

    /**
     * Delete review
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review deleted successfully!');
    }

    /**
     * Add admin response
     */
    public function respond(Request $request, Review $review)
    {
        $data = $request->validate([
            'response' => 'required|string|max:500',
        ]);

        $review->update([
            'response' => $data['response'],
            'response_date' => now(),
        ]);

        return back()->with('success', 'Response added successfully!');
    }
}