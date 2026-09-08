                <!-- Timeline -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Timeline</h3>
                    
                    @if(isset($timeline) && count($timeline->events) > 0)
                    <div class="space-y-4">
                        @foreach($timeline->events as $event)
                        <div class="flex gap-3">
                            <div class="text-orange-400 text-sm">
                                @if($event->icon === 'calendar') 📅
                                @elseif($event->icon === 'credit-card') 💳
                                @elseif($event->icon === 'check-circle') ✓
                                @elseif($event->icon === 'x-circle') ✗
                                @elseif($event->icon === 'play-circle') ▶
                                @elseif($event->icon === 'stop-circle') ⏹
                                @elseif($event->icon === 'arrow-circle-left') ↩
                                @elseif($event->icon === 'shield') 🛡
                                @elseif($event->icon === 'shield-check') 🛡
                                @elseif($event->icon === 'clipboard-check') 📋
                                @else 📅
                                @endif
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">{{ $event->title }}</p>
                                <p class="text-xs text-gray-400">{{ $event->description }}</p>
                                <p class="text-xs text-gray-500">{{ $event->date->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-gray-500 text-sm">No timeline events available.</div>
                    @endif
                </div>
