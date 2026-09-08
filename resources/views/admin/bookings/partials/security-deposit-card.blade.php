{{-- ═══ SECURITY DEPOSIT MANAGEMENT ═══ --}}
@if($booking->security_deposit_intent_id)
@php
    $securityDepositEffectiveState = $booking->getSecurityDepositEffectiveState();
    $securityDepositAmount = $booking->security_deposit_amount ?: ($booking->car->security_deposit_amount ?? 0);
    $securityDepositCapturedAmount = $booking->security_deposit_charged_amount ?? 0;
    $securityDepositPenaltyAmount = $booking->security_deposit_penalty_amount ?? 0;
    $securityDepositCaptureId = $booking->getAttribute('security_deposit_payment_intent_id') ?: $booking->security_deposit_intent_id;
    $securityDepositRefundId = $booking->security_deposit_refund_id ?? null;
    $securityDepositCapturedBy = $booking->securityDepositCapturedBy?->name;
    $securityDepositRefundedBy = $booking->securityDepositRefundedBy?->name;
    $securityDepositProcessedBy = $booking->securityDepositProcessedBy?->name;
    $securityDepositPenaltyReason = $booking->security_deposit_penalty_reason ?? null;
    $securityDepositRefundError = $booking->security_deposit_refund_error_message ?? null;
    $recordedSecurityDepositRefundedAmount = $booking->security_deposit_refunded_amount ?? null;
    $securityDepositRefundedAmount = $recordedSecurityDepositRefundedAmount ?? (
        in_array($securityDepositEffectiveState, ['refunded', 'partially_refunded'], true)
            ? max(0, $securityDepositCapturedAmount - $securityDepositPenaltyAmount)
            : 0
    );
    $securityDepositStateLabels = [
        'held' => 'Held',
        'refunded' => 'Refunded',
        'captured' => 'Captured',
        'refund_pending' => 'Refund Pending',
        'partially_refunded' => 'Partially Refunded',
        'pending' => 'Pending',
    ];
    $securityDepositRefundable = $booking->isSecurityDepositRefundable();
    $securityDepositRefundPending = $securityDepositEffectiveState === 'refund_pending';
    $securityDepositPendingRefundAmount = max(0, $securityDepositCapturedAmount - $securityDepositPenaltyAmount - $securityDepositRefundedAmount);
@endphp
<div id="security-deposit-management" class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 p-6 mt-6">

    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        Security Deposit Management
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Security Deposit Amount</p>
            <p class="text-white font-bold text-xl">{{ number_format($securityDepositAmount, 0) }} MAD</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Captured Amount</p>
            <p class="text-red-400 font-bold text-xl">{{ number_format($securityDepositCapturedAmount, 0) }} MAD</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Penalty Kept</p>
            <p class="text-amber-400 font-bold text-xl">{{ number_format($securityDepositPenaltyAmount, 0) }} MAD</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Refunded Amount</p>
            <p class="text-emerald-400 font-bold text-xl">{{ number_format($securityDepositRefundedAmount, 0) }} MAD</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Status</p>
            @php
                $depColors = [
                    'held'     => 'text-blue-400',
                    'refunded' => 'text-emerald-400',
                    'captured' => 'text-orange-400',
                    'refund_pending' => 'text-red-400',
                    'partially_refunded' => 'text-yellow-400',
                    'pending'  => 'text-yellow-400',
                ];
            @endphp
            <p id="security-deposit-status-text" class="font-bold text-xl {{ $depColors[$securityDepositEffectiveState] ?? 'text-gray-400' }}">
                {{ $securityDepositStateLabels[$securityDepositEffectiveState] ?? ucfirst(str_replace('_', ' ', $securityDepositEffectiveState)) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-900 rounded-xl p-4">
            <p class="text-gray-500 text-xs mb-1">Capture ID</p>
            <p class="text-gray-200 text-sm font-mono break-all">{{ $securityDepositCaptureId ?: '-' }}</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4">
            <p class="text-gray-500 text-xs mb-1">Refund ID</p>
            <p class="text-gray-200 text-sm font-mono break-all">{{ $securityDepositRefundId ?: '-' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-gray-900 rounded-xl p-4">
            <p class="text-gray-500 text-xs mb-1">Captured by</p>
            <p class="text-gray-200 text-sm">{{ $securityDepositCapturedBy ?: '-' }}</p>
            <p class="text-gray-500 text-xs mt-3 mb-1">Processed by</p>
            <p class="text-gray-200 text-sm">{{ $securityDepositProcessedBy ?: '-' }}</p>
            <p class="text-gray-500 text-xs mt-3 mb-1">Captured at</p>
            <p class="text-gray-200 text-sm">{{ $booking->security_deposit_captured_at?->format('Y-m-d H:i') ?: '-' }}</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4">
            <p class="text-gray-500 text-xs mb-1">Refunded by</p>
            <p class="text-gray-200 text-sm">{{ $securityDepositRefundedBy ?: '-' }}</p>
            <p class="text-gray-500 text-xs mt-3 mb-1">Refunded at</p>
            <p class="text-gray-200 text-sm">{{ $booking->security_deposit_refunded_at?->format('Y-m-d H:i') ?: '-' }}</p>
        </div>
    </div>

    <div class="bg-gray-900 rounded-xl p-4 mb-6">
        <p class="text-gray-500 text-xs mb-1">Penalty reason</p>
        <p class="text-gray-200 text-sm">{{ $securityDepositPenaltyReason ?: '-' }}</p>
    </div>

    <div id="security-deposit-feedback" class="hidden mb-4"></div>

    <div id="security-deposit-status-block" class="mb-4">
        @if($booking->isSecurityDepositActionable())
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-blue-300 text-sm font-semibold">
                    Security deposit held. Capturable: {{ number_format($booking->security_deposit_capturable_amount, 0) }} MAD.
                </p>
            </div>
        @elseif($securityDepositRefundPending)
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-red-300 text-sm font-semibold">Refund failed. Retry refund.</p>
                    @if($securityDepositRefundError)
                        <p class="text-red-200/80 text-xs mt-1 break-words">{{ $securityDepositRefundError }}</p>
                    @endif
                </div>
            </div>
        @elseif($securityDepositEffectiveState === 'refunded')
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 text-sm font-semibold">Security deposit refunded</p>
            </div>
        @elseif($securityDepositRefundable)
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-300 text-sm font-semibold">
                    Security deposit captured. Refundable: {{ number_format($securityDepositCapturedAmount - $securityDepositRefundedAmount, 0) }} MAD.
                </p>
            </div>
        @elseif($securityDepositEffectiveState === 'partially_refunded')
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-yellow-300 text-sm font-semibold">
                    Security deposit partially refunded. Penalty kept: {{ number_format($securityDepositPenaltyAmount, 0) }} MAD. Refunded: {{ number_format($securityDepositRefundedAmount, 0) }} MAD.
                </p>
            </div>
        @endif
    </div>

    @if($booking->isSecurityDepositActionable() || $securityDepositRefundable || $securityDepositRefundPending)
        <div id="security-deposit-actions" class="grid grid-cols-1 {{ $booking->isSecurityDepositActionable() ? 'md:grid-cols-2' : '' }} gap-4">

            @if($securityDepositRefundPending)
                <form method="POST" action="{{ route('admin.security-deposit.retry-refund', $booking) }}">
                    @csrf
                    <button type="submit"
                            class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-xl border border-red-500/30 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0119 5m0 0h-5m5 0v5"/>
                        </svg>
                        Retry Refund
                    </button>
                    <p class="text-xs text-gray-500 mt-2 text-center">Pending refund: {{ number_format($securityDepositPendingRefundAmount, 0) }} MAD</p>
                </form>
            @endif

            @if(!$securityDepositRefundPending)
            {{-- Release Security Deposit --}}
            <button type="button"
                    id="release-security-deposit-button"
                    data-url="{{ route('admin.security-deposit.release', $booking) }}"
                    data-csrf="{{ csrf_token() }}"
                    data-idle-label="{{ $securityDepositRefundable ? 'Refund Deposit' : 'Release Hold' }}"
                    data-loading-label="{{ $securityDepositRefundable ? 'Refunding...' : 'Releasing...' }}"
                    data-confirm-refund="{{ $securityDepositRefundable ? '1' : '0' }}"
                    data-penalty-amount="0"
                    data-refund-amount="{{ max(0, $securityDepositCapturedAmount - $securityDepositRefundedAmount) }}"
                    class="w-full py-3 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold rounded-xl border border-emerald-500/30 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg data-release-icon class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg data-release-spinner class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span data-release-label>{{ $securityDepositRefundable ? 'Refund Deposit' : 'Release Hold' }}</span>
                </button>
            @endif

            <template id="security-deposit-released-template">
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3 transition-all duration-300">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-300 text-sm font-semibold">Security deposit refunded</p>
                </div>
            </template>

            @if($booking->isSecurityDepositActionable())
            <div x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-xl border border-red-500/30 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Capture Deposit
                </button>

                <div x-show="open" class="mt-4 bg-gray-900 rounded-xl p-4 border border-red-500/20">
                    <form method="POST"
                          action="{{ route('admin.security-deposit.charge', $booking) }}"
                          id="security-deposit-capture-form"
                          data-deposit-amount="{{ $securityDepositAmount }}">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Penalty Amount (Damage / Late Return)</label>
                                <input type="number"
                                       name="penalty_amount"
                                       min="0"
                                       max="{{ $securityDepositAmount }}"
                                       step="0.01"
                                       placeholder="e.g. 250"
                                       required
                                       class="w-full bg-gray-800 text-white rounded-lg px-3 py-2 border border-gray-700 text-sm outline-none focus:border-red-400">
                                <p class="text-xs text-gray-500 mt-1">
                                    Enter the amount to KEEP from the deposit.
                                    The remaining amount will be refunded to the customer.
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Penalty reason</label>
                                <select name="penalty_reason"
                                        class="w-full bg-gray-800 text-white rounded-lg px-3 py-2 border border-gray-700 text-sm outline-none focus:border-red-400">
                                    <option value="">Select a reason</option>
                                    <option value="Late return">Late return</option>
                                    <option value="Damage">Damage</option>
                                    <option value="Cleaning fee">Cleaning fee</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="w-full py-2 bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg text-sm transition">
                                Capture Deposit Once
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

        </div>
    @else
        <div class="text-center py-4 text-gray-500 text-sm">
            Security deposit {{ $securityDepositStateLabels[$securityDepositEffectiveState] ?? str_replace('_', ' ', $securityDepositEffectiveState) }} - no actions available.
        </div>
    @endif

    <div id="security-deposit-refund-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/70 px-4">
        <div class="w-full max-w-md rounded-xl border border-gray-700 bg-[#111827] p-5 shadow-2xl">
            <h4 class="text-white font-bold text-lg mb-4">Confirm Refund</h4>
            <div class="space-y-3 mb-5">
                <div class="flex items-center justify-between rounded-lg bg-gray-900 px-4 py-3">
                    <span class="text-gray-400 text-sm">You are keeping</span>
                    <span class="text-amber-400 font-bold"><span data-refund-modal-penalty>0</span> MAD</span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-900 px-4 py-3">
                    <span class="text-gray-400 text-sm">Customer will receive</span>
                    <span class="text-emerald-400 font-bold"><span data-refund-modal-refund>0</span> MAD</span>
                </div>
            </div>
            <p class="text-gray-300 text-sm mb-5">Are you sure?</p>
            <div class="flex justify-end gap-3">
                <button type="button"
                        data-refund-modal-cancel
                        class="px-4 py-2 rounded-lg border border-gray-600 text-gray-300 hover:bg-gray-800 transition">
                    Cancel
                </button>
                <button type="button"
                        data-refund-modal-confirm
                        class="px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold transition">
                    Confirm Refund
                </button>
            </div>
        </div>
    </div>

</div>
@endif
