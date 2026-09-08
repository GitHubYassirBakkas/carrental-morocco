                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Customer</h3>
                        @php($profile = $booking->user?->customerProfile)
                        @php($driverStatus = $profile?->driver_verification_status ?? \App\Models\CustomerProfile::STATUS_INCOMPLETE)
                        @php($driverStatusLabels = [
                            \App\Models\CustomerProfile::STATUS_INCOMPLETE => 'INCOMPLETE',
                            \App\Models\CustomerProfile::STATUS_PENDING => 'PENDING REVIEW',
                            \App\Models\CustomerProfile::STATUS_VERIFIED => 'VERIFIED',
                            \App\Models\CustomerProfile::STATUS_REJECTED => 'REJECTED',
                        ])
                        @php($driverStatusClasses = [
                            \App\Models\CustomerProfile::STATUS_INCOMPLETE => 'bg-amber-500/10 text-amber-300 border-amber-500/30',
                            \App\Models\CustomerProfile::STATUS_PENDING => 'bg-blue-500/10 text-blue-300 border-blue-500/30',
                            \App\Models\CustomerProfile::STATUS_VERIFIED => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30',
                            \App\Models\CustomerProfile::STATUS_REJECTED => 'bg-red-500/10 text-red-300 border-red-500/30',
                        ])
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Name</p>
                                <p class="text-white font-medium">{{ $booking->user->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Email</p>
                                <p class="text-orange-400 text-sm">{{ $booking->user->email ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Phone</p>
                                <p class="text-white text-sm">{{ $booking->user->phone ?? '-' }}</p>
                            </div>

                            <div class="pt-4 mt-4 border-t border-gray-800">
                                <h4 class="text-xs font-semibold text-gray-400 uppercase mb-3">Driver Readiness</h4>
                                <div class="mb-4">
                                    <p class="text-xs text-gray-500 mb-2">Driver Verification</p>
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ $driverStatusClasses[$driverStatus] ?? $driverStatusClasses[\App\Models\CustomerProfile::STATUS_INCOMPLETE] }}">
                                        {{ $driverStatusLabels[$driverStatus] ?? 'INCOMPLETE' }}
                                    </span>
                                </div>
                                @if(! ($profile?->isDriverVerified() ?? false))
                                    <div class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-200">
                                        Driver verification is required before this rental can be started.
                                    </div>
                                @endif
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Age</p>
                                        <p class="text-white">{{ $profile?->age ? $profile->age . ' years' : '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">License Expiry</p>
                                        <p class="text-white">{{ $profile?->driving_license_expiry_date?->format('M d, Y') ?? '-' }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-xs text-gray-500 mb-1">License Number</p>
                                        <p class="text-white break-words">{{ $profile?->driving_license_number ?? '-' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 mt-3">
                                    @foreach([
                                        'License Front' => 'driving_license_front_path',
                                        'License Back' => 'driving_license_back_path',
                                        'CNIE Front' => 'identity_front_path',
                                        'CNIE Back' => 'identity_back_path',
                                    ] as $label => $field)
                                        <span class="text-xs rounded-lg px-2 py-1 border {{ $profile?->{$field} ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30' : 'bg-red-500/10 text-red-300 border-red-500/30' }}">
                                            {{ $label }}: {{ $profile?->{$field} ? 'Uploaded' : 'Missing' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
