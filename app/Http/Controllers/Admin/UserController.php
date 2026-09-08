<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const SUPPORTED_ROLES = ['user', 'admin'];

    /**
     * Display listing of users
     */
    public function index(Request $request)
    {
        $query = User::withCount(['bookings', 'reviews']);

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
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
            'customerProfile.verifiedBy',
            'bookings' => fn ($q) => $q->latest()->limit(10),
            'reviews' => fn ($q) => $q->latest()->limit(5),
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
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(self::SUPPORTED_ROLES)],
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Update password only if provided
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function () use ($user, $validated) {
            $lockedUser = User::whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldRole = $lockedUser->role;
            $newRole = $validated['role'];

            if ($oldRole === 'admin' && $newRole !== 'admin') {
                if ((int) $lockedUser->id === (int) auth()->id()) {
                    throw ValidationException::withMessages([
                        'role' => 'You cannot remove your own administrator access.',
                    ]);
                }

                if (! $lockedUser->is_banned && $this->activeAdminIdsForUpdate()->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'The last active administrator cannot lose admin access.',
                    ]);
                }
            }

            $lockedUser->name = $validated['name'];
            $lockedUser->email = $validated['email'];
            $lockedUser->phone = $validated['phone'] ?? null;
            $lockedUser->role = $newRole;

            if (array_key_exists('password', $validated)) {
                $lockedUser->password = $validated['password'];
            }

            $lockedUser->save();

            if ($oldRole !== $newRole) {
                Log::info('Admin user role changed.', [
                    'actor_id' => auth()->id(),
                    'user_id' => $lockedUser->id,
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ]);
            }
        });

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

        $user->is_banned = true;
        $user->save();

        return back()->with('success', 'User banned successfully!');
    }

    /**
     * Unban user
     */
    public function unban(User $user)
    {
        $user->is_banned = false;
        $user->save();

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

    private function activeAdminIdsForUpdate()
    {
        return User::where('role', 'admin')
            ->where('is_banned', false)
            ->lockForUpdate()
            ->pluck('id');
    }
}
