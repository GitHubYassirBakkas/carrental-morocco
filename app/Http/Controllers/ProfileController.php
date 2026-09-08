<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show My Account page
     */
    public function index()
    {
        return redirect()->route('profile.edit');
    }

    /**
     * Update user profile
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('customerProfile');

        $totalBookings = $user->bookings()->count();
        $completedRentals = $user->bookings()
            ->where('status', 'completed')
            ->count();
        $wishlistCount = $user->wishlists()->count();
        $totalSpent = $user->total_spent;
        $memberSince = $user->created_at;

        return view('profile.edit', [
            'user' => $user,
            'totalBookings' => $totalBookings,
            'completedRentals' => $completedRentals,
            'wishlistCount' => $wishlistCount,
            'totalSpent' => $totalSpent,
            'memberSince' => $memberSince,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:10240'],
        ]);

        $userData = $request->only([
            'name',
            'email',
            'phone',
            'address',
            'city',
            'country',
            'postal_code',
        ]);

        if ($request->email !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->fill($userData);
        $user->save();

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::delete('public/'.$user->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->update(['profile_photo_path' => $path]);
        }

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    public function editDriver(Request $request): View
    {
        $user = $request->user()->load('customerProfile');

        return view('profile.driver', [
            'user' => $user,
            'profile' => $user->customerProfile,
        ]);
    }

    public function updateDriver(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->customerProfile;
        $licenseExpiryRules = ['nullable', 'date'];

        if ($request->filled('driving_license_issue_date')) {
            $licenseExpiryRules[] = 'after_or_equal:driving_license_issue_date';
        }

        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'driving_license_number' => ['nullable', 'string', 'max:80'],
            'driving_license_country' => ['nullable', 'string', 'max:100'],
            'driving_license_issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'driving_license_expiry_date' => $licenseExpiryRules,
            'driving_license_front' => $this->documentValidationRules('nullable'),
            'driving_license_back' => $this->documentValidationRules('nullable'),
            'identity_front' => $this->documentValidationRules('nullable'),
            'identity_back' => $this->documentValidationRules('nullable'),
        ]);

        $driverData = [
            'date_of_birth' => $request->has('date_of_birth')
                ? ($validated['date_of_birth'] ?? null)
                : $profile?->date_of_birth?->toDateString(),
            'driving_license_number' => $request->has('driving_license_number')
                ? $this->nullableString($validated['driving_license_number'] ?? null)
                : $profile?->driving_license_number,
            'driving_license_country' => $request->has('driving_license_country')
                ? $this->nullableString($validated['driving_license_country'] ?? null)
                : $profile?->driving_license_country,
            'driving_license_issue_date' => $request->has('driving_license_issue_date')
                ? ($validated['driving_license_issue_date'] ?? null)
                : $profile?->driving_license_issue_date?->toDateString(),
            'driving_license_expiry_date' => $request->has('driving_license_expiry_date')
                ? ($validated['driving_license_expiry_date'] ?? null)
                : $profile?->driving_license_expiry_date?->toDateString(),
        ];

        $storedPrivatePaths = [];
        $oldPrivatePaths = [];

        try {
            DB::transaction(function () use ($request, $user, &$profile, $driverData, &$storedPrivatePaths, &$oldPrivatePaths) {
                if (! $profile) {
                    $profile = $user->customerProfile()->create($driverData);
                }

                foreach (CustomerProfile::DOCUMENT_FIELDS as $input => $pathField) {
                    if (! $request->hasFile($input)) {
                        continue;
                    }

                    if ($profile->{$pathField}) {
                        $oldPrivatePaths[] = $profile->{$pathField};
                    }

                    $storedPrivatePaths[$pathField] = $this->storeCustomerDocument(
                        $request,
                        $input,
                        $user->id,
                        $this->documentDirectory($input)
                    );
                }

                $profile->fill(array_merge($driverData, $storedPrivatePaths));
                $requiresAgencyReview = $profile->isDirty(array_keys($driverData))
                    || count($storedPrivatePaths) > 0
                    || ! $profile->isDriverVerified();

                if ($profile->hasCompleteDriverProfile()) {
                    if ($requiresAgencyReview) {
                        $profile->forceFill([
                            'driver_verification_status' => CustomerProfile::STATUS_PENDING,
                            'driver_verification_rejection_reason' => null,
                            'driver_verification_submitted_at' => now(),
                            'driver_verified_at' => null,
                            'driver_verified_by' => null,
                        ]);
                    }
                } else {
                    $profile->forceFill([
                        'driver_verification_status' => CustomerProfile::STATUS_INCOMPLETE,
                        'driver_verified_at' => null,
                        'driver_verified_by' => null,
                    ]);
                }

                $profile->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values($storedPrivatePaths));

            throw $exception;
        }

        Storage::disk('local')->delete($oldPrivatePaths);

        return redirect()->route('profile.driver.edit')->with('status', 'driver-profile-updated');
    }

    /**
     * Delete the user's profile photo.
     */
    public function deletePhoto(): RedirectResponse
    {
        $user = Auth::user();

        if ($user->profile_photo_path) {
            Storage::delete('public/'.$user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'current_password'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'userDeletion');
        }

        $user = $request->user();

        if ($user->hasBusinessHistory()) {
            return back()->withErrors([
                'password' => 'Your account has booking, payment, review, or support history and cannot be deleted. Please contact support to deactivate it.',
            ], 'userDeletion');
        }

        if ($user->profile_photo_path) {
            Storage::delete('public/'.$user->profile_photo_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'account-deleted');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Show payment history for the authenticated user.
     */
    public function paymentHistory(Request $request): View
    {
        $user = Auth::user();

        $paymentsQuery = $user->payments()
            ->with(['invoice.booking.car'])
            ->latest('paid_at');

        if ($request->filled('status')) {
            $paymentsQuery->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('invoice.booking', function ($query) use ($search) {
                        $query->where('id', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $paymentsQuery->paginate(10);

        $totalPayments = $user->payments()->count();
        $totalSpent = $user->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->sum('amount');
        $paidPayments = $user->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->count();
        $pendingPayments = $user->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        return view('profile.payment-history', compact(
            'payments',
            'totalPayments',
            'totalSpent',
            'paidPayments',
            'pendingPayments'
        ));
    }

    private function documentValidationRules(string $requiredRule): array
    {
        return [
            $requiredRule,
            'file',
            'mimes:jpeg,jpg,png,webp,pdf',
            'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
            'max:5120',
        ];
    }

    private function storeCustomerDocument(Request $request, string $input, int $userId, string $directory): string
    {
        $file = $request->file($input);
        $extension = strtolower($file->extension());
        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs("private/customer-documents/{$userId}/{$directory}", $filename, 'local');
    }

    private function documentDirectory(string $input): string
    {
        return match ($input) {
            'driving_license_front' => 'driving-license/front',
            'driving_license_back' => 'driving-license/back',
            'identity_front' => 'identity/front',
            'identity_back' => 'identity/back',
        };
    }

    private function nullableString(?string $value): ?string
    {
        return filled($value) ? $value : null;
    }
}
