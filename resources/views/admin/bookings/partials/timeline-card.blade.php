                <!-- Timeline -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Timeline</h3>
                    
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="text-orange-400 text-sm">📅</div>
                            <div>
                                <p class="text-white text-sm font-medium">Booking Created</p>
                                <p class="text-xs text-gray-400">{{ $booking->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        @if($booking->checkinInspection)
                        <div class="flex gap-3">
                            <div class="text-emerald-400 text-sm">✓</div>
                            <div>
                                <p class="text-white text-sm font-medium">Check-in Done</p>
                                <p class="text-xs text-gray-400">{{ $booking->checkinInspection->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endif

                        @if($booking->checkoutInspection)
                        <div class="flex gap-3">
                            <div class="text-emerald-400 text-sm">✓</div>
                            <div>
                                <p class="text-white text-sm font-medium">Check-out Done</p>
                                <p class="text-xs text-gray-400">{{ $booking->checkoutInspection->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
