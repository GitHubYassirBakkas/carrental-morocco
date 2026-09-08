        <!-- Quick Actions -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">Quick Actions</h2>

            <div class="flex flex-wrap gap-3">
                @if($booking->status == 'pending')
                    <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-[#d97706] hover:bg-[#f59e0b] text-black font-semibold rounded-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirm Booking
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                        @csrf
                        <button class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel Booking
                        </button>
                    </form>
                @endif

              @if($booking->status == 'confirmed')
                @php($driverVerifiedForStart = $booking->user?->customerProfile?->isDriverVerified() ?? false)
                @if(! $driverVerifiedForStart)
                    <div class="px-6 py-3 bg-amber-500/20 border border-amber-500/30 text-amber-300 rounded-xl text-sm font-semibold">
                        Driver verification required before start
                    </div>
                @elseif($booking->checkinInspection)
                    <form method="POST" action="{{ route('admin.bookings.start', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#d4ab76] text-black font-semibold rounded-xl">
                            Start Rental
                        </button>
                    </form>
                @else
                    <div class="px-6 py-3 bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 rounded-xl text-sm font-semibold">
                        ⚠️ Check-in inspection required
                    </div>
                @endif
            @endif


             @if($booking->status == 'active')
                @if($booking->checkoutInspection)
                    <form method="POST" action="{{ route('admin.bookings.complete', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl">
                            Complete Rental
                        </button>
                    </form>
                @else
                    <div class="px-6 py-3 bg-red-500/20 border border-red-500/30 text-red-400 rounded-xl text-sm font-semibold">
                        ⚠️ Check-out inspection required
                    </div>
                @endif
            @endif

            </div>
        </div>
