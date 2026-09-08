                <!-- Period & Locations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Period -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Period</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Pickup</p>
                                <p class="text-white font-medium">{{ optional($booking->start_date)->format('M d, Y - H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Return</p>
                                <p class="text-white font-medium">{{ optional($booking->end_date)->format('M d, Y - H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Locations -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Locations</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Pickup</p>
                                <p class="text-white font-medium">{{ $booking->pickupLocation->name ?? 'Location #'.$booking->pickup_location_id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Dropoff</p>
                                <p class="text-white font-medium">{{ $booking->dropoffLocation->name ?? 'Location #'.$booking->dropoff_location_id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
