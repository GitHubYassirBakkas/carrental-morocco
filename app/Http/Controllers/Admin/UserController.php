<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display listing of users
     */
    public function index(Request $request)
    {
        $query = User::withCount(['bookings', 'reviews']);

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Filter by role
        if ($request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('is_banned')) {
            $query->where('is_banned', $request->is_banned);
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show user details
     */
    public function show(User $user)
    {
        $user->load([
            'bookings' => fn($q) => $q->latest()->limit(10),
            'reviews' => fn($q) => $q->latest()->limit(5),
        ]);

        // User statistics
        $stats = [
            'total_bookings' => $user->bookings()->count(),
            'active_bookings' => $user->bookings()->where('status', Booking::STATUS_ACTIVE)->count(),
            'completed_bookings' => $user->bookings()->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled_bookings' => $user->bookings()->where('status', Booking::STATUS_CANCELLED)->count(),
            'total_spent' => $user->bookings()->where('status', Booking::STATUS_COMPLETED)->sum('total_amount'),
            'total_reviews' => $user->reviews()->count(),
            'avg_rating' => $user->reviews()->avg('rating'),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:user,admin',
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Update password only if provided
        if ($request->password) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Ban user
     */
    public function ban(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot ban admin users!');
        }

        $user->update(['is_banned' => true]);

        return back()->with('success', 'User banned successfully!');
    }

    /**
     * Unban user
     */
    public function unban(User $user)
    {
        $user->update(['is_banned' => false]);

        return back()->with('success', 'User unbanned successfully!');
    }

    /**
     * Delete user
     */
    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot delete admin users!');
        }

        if ($user->hasBusinessHistory()) {
            return back()->with('error', 'Cannot delete users with booking, payment, review, coupon, or support history. Ban the user instead.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }
}
