                <!-- Damages -->
                @if($booking->damages->count() > 0)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Vehicle Damages ({{ $booking->damages->count() }})</h3>

                    <div class="relative w-full max-w-2xl mx-auto mb-4">
                        <img src="/images/car-top.png" class="w-full opacity-70 rounded-xl">
                        
                        @foreach($booking->damages as $damage)
                            <span class="absolute bg-red-600 text-white text-xs px-2 py-1 rounded shadow-lg font-bold"
                                  style="top: {{ $damage->pos_y ?? 50 }}%; left: {{ $damage->pos_x ?? 50 }}%">
                                {{ strtoupper($damage->part) }}
                            </span>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($booking->damages as $damage)
                            <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                                <div class="flex justify-between mb-2">
                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">
                                        {{ strtoupper($damage->stage) }}
                                    </span>
                                    <span class="text-red-400 font-bold text-sm">{{ number_format($damage->estimated_cost ?? 0, 0) }} MAD</span>
                                </div>
                                <p class="text-white font-medium text-sm">{{ ucfirst($damage->part) }} - {{ ucfirst($damage->type) }}</p>
                                <p class="text-gray-400 text-xs mt-1">{{ $damage->description ?? 'No description' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
