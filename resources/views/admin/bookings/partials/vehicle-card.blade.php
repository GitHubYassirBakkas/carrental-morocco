                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Vehicle</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Model</p>
                                <p class="text-white font-medium">{{ $booking->car->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Rental Price</p>
                                <p class="text-orange-400 font-bold text-lg">{{ number_format($booking->rental_price_per_day, 2) }} <span class="text-sm text-gray-400">MAD</span></p>
                            </div>
                        </div>
                    </div>
                </div>
