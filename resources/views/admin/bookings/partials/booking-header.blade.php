        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('admin.bookings.index') }}" class="text-gray-400 hover:text-white transition-colors">Bookings</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Booking #{{ $booking->id }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h1 class="text-4xl font-bold text-white">Booking #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                    <x-admin.booking-status-badge :status="$booking->status" class="text-sm px-3 py-1.5" />
                </div>
                <p class="text-gray-400 text-sm">{{ $booking->car->full_name ?? 'Vehicle not assigned' }} • Created {{ $booking->created_at->diffForHumans() }}</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bookings.invoice', $booking) }}" 
                   class="px-4 py-2.5 bg-[#1f2937] hover:bg-[#374151] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Invoice
                </a>
                <a href="{{ route('admin.bookings.index') }}"
                   class="px-4 py-2.5 bg-[#1f2937] hover:bg-[#374151] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-300 text-sm">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Progress Tracker -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            @php
                $stages = ['pending','confirmed','active','completed'];
                $currentIndex = array_search($booking->status, $stages);
                $currentIndex = $currentIndex === false ? 0 : $currentIndex;
            @endphp

            <div class="flex items-center justify-between relative mb-6">
                @foreach($stages as $index => $stage)
                    @php
                        $isCompleted = $index < $currentIndex;
                        $isCurrent = $index === $currentIndex;
                    @endphp

                    <div class="flex flex-col items-center w-full relative">
                        @if(!$loop->first)
                            <div class="absolute top-6 -left-1/2 w-full h-1 {{ $isCompleted ? 'bg-orange-500' : 'bg-gray-700' }} z-0"></div>
                        @endif

                        <div class="w-12 h-12 flex items-center justify-center rounded-xl text-sm font-bold transition-all z-10
                            @class([
                                'bg-orange-500 text-black' => $isCompleted,
                                'bg-orange-500 text-black animate-pulse' => $isCurrent,
                                'bg-gray-700 text-gray-400' => !$isCompleted && !$isCurrent,
                            ])">
                            @if($isCompleted)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>

                        <span class="text-xs mt-2 font-medium {{ $isCurrent ? 'text-orange-400' : 'text-gray-400' }}">
                            {{ __('messages.statuses.'.$stage) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="w-full bg-gray-700 h-2 rounded-full overflow-hidden">
                <div class="bg-orange-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $booking->progress_percent ?? 0 }}%">
                </div>
            </div>
            <p class="text-xs text-right mt-2 text-gray-400">{{ $booking->progress_percent ?? 0 }}% Complete</p>
        </div>
