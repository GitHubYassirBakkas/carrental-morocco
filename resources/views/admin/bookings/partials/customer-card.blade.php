                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Customer</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Name</p>
                                <p class="text-white font-medium">{{ $booking->user->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Email</p>
                                <p class="text-orange-400 text-sm">{{ $booking->user->email ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
