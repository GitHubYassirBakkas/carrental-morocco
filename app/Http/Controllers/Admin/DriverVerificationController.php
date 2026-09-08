<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DriverVerificationApprovedMail;
use App\Mail\DriverVerificationRejectedMail;
use App\Models\CustomerProfile;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DriverVerificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function verify(CustomerProfile $customerProfile): RedirectResponse
    {
        if (! $customerProfile->hasCompleteDriverProfile()) {
            return back()->withErrors('Driver profile is incomplete and cannot be verified.');
        }

        $previousStatus = $customerProfile->driver_verification_status;

        $customerProfile->forceFill([
            'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
            'driver_verification_rejection_reason' => null,
            'driver_verified_at' => now(),
            'driver_verified_by' => auth()->id(),
        ])->save();

        if ($previousStatus !== CustomerProfile::STATUS_VERIFIED) {
            $this->sendApprovedCommunication($customerProfile);
        }

        Log::info('Driver profile verified.', [
            'actor_id' => auth()->id(),
            'customer_profile_id' => $customerProfile->id,
            'user_id' => $customerProfile->user_id,
        ]);

        return back()->with('success', 'Driver profile verified.');
    }

    public function reject(Request $request, CustomerProfile $customerProfile): RedirectResponse
    {
        $validated = $request->validate([
            'driver_verification_rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $previousStatus = $customerProfile->driver_verification_status;

        $customerProfile->forceFill([
            'driver_verification_status' => CustomerProfile::STATUS_REJECTED,
            'driver_verification_rejection_reason' => $validated['driver_verification_rejection_reason'],
            'driver_verified_at' => null,
            'driver_verified_by' => null,
        ])->save();

        if (
            $previousStatus !== CustomerProfile::STATUS_REJECTED
            || $customerProfile->wasChanged('driver_verification_rejection_reason')
        ) {
            $this->sendRejectedCommunication($customerProfile, $validated['driver_verification_rejection_reason']);
        }

        Log::info('Driver profile rejected.', [
            'actor_id' => auth()->id(),
            'customer_profile_id' => $customerProfile->id,
            'user_id' => $customerProfile->user_id,
        ]);

        return back()->with('success', 'Driver profile rejected.');
    }

    private function sendApprovedCommunication(CustomerProfile $customerProfile): void
    {
        $customerProfile->loadMissing('user');
        $user = $customerProfile->user;

        if (! $user) {
            return;
        }

        try {
            $this->notificationService->create(
                $user->id,
                'driver_verification_approved',
                'Driver profile verified',
                'Your driver profile has been verified. You can now reserve vehicles.',
                ['cta_url' => route('cars.index')]
            );
        } catch (Throwable $e) {
            Log::warning('Driver verification approval notification failed.', [
                'customer_profile_id' => $customerProfile->id,
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
        }

        try {
            Mail::to($user->email)->send(new DriverVerificationApprovedMail($user));
        } catch (Throwable $e) {
            Log::warning('Driver verification approval email failed.', [
                'customer_profile_id' => $customerProfile->id,
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
        }
    }

    private function sendRejectedCommunication(CustomerProfile $customerProfile, string $reason): void
    {
        $customerProfile->loadMissing('user');
        $user = $customerProfile->user;

        if (! $user) {
            return;
        }

        try {
            $this->notificationService->create(
                $user->id,
                'driver_verification_rejected',
                'Driver verification needs attention',
                'Your driver profile requires an update before you can reserve vehicles. Reason: '.$reason,
                ['cta_url' => route('profile.driver.edit')]
            );
        } catch (Throwable $e) {
            Log::warning('Driver verification rejection notification failed.', [
                'customer_profile_id' => $customerProfile->id,
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
        }

        try {
            Mail::to($user->email)->send(new DriverVerificationRejectedMail($user, $reason));
        } catch (Throwable $e) {
            Log::warning('Driver verification rejection email failed.', [
                'customer_profile_id' => $customerProfile->id,
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
        }
    }
}
