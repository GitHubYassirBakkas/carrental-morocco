@extends('layouts.app')
@section('title', __('messages.payment'))

@section('content')
<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-6xl mx-auto px-6">

        {{-- Header --}}
        <div class="mb-10">
            <h1 class="text-4xl font-bold text-white mb-2">{{ __('messages.secure_payment') }}</h1>
            <p class="text-gray-400">{{ __('messages.payment_subtitle') }}</p>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-500/10 border border-red-500/30 rounded-2xl p-4">
                @foreach($errors->all() as $error)
                    <p class="text-red-300 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- LEFT: Payment Form --}}
            <div class="lg:col-span-2 space-y-6">

                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-8 py-5 border-b border-gray-800">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            {{ __('messages.payment_method') }}
                        </h2>
                    </div>

                    <form id="payment-form" class="p-8">
                        @csrf

                        {{-- Hidden fields --}}
                        <input type="hidden" name="_method" value="POST">
                        <input type="hidden" id="rental_payment_intent" name="rental_payment_intent" value="{{ $rentalIntent->client_secret }}">
                        @if($depositIntent)
                            <input type="hidden" id="deposit_payment_intent" name="deposit_payment_intent" value="{{ $depositIntent->client_secret }}">
                        @endif

                        {{-- Payment Methods --}}
                        <div class="space-y-4 mb-6">

                            {{-- Card --}}
                            <label class="flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-[#C89D66] bg-[#C89D66]/10" id="cardLabel">
                                <input type="radio" name="payment_method" value="card" id="payCard" class="mt-1 w-5 h-5 accent-[#C89D66]" checked>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </div>
                                        <p class="text-white font-bold">{{ __('messages.card_payment') }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg" alt="Visa" class="h-5">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MC" class="h-5">
                                    </div>
                                </div>
                            </label>

                            {{-- Cash --}}
                            <label class="flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-gray-800 hover:border-gray-600" id="cashLabel">
                                <input type="radio" name="payment_method" value="cash" id="payCash" class="mt-1 w-5 h-5 accent-[#C89D66]">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <p class="text-white font-bold">{{ __('messages.cash_payment') }}</p>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ __('messages.pay_on_arrival') }}</p>
                                </div>
                            </label>
                        </div>

                        {{-- ✅ زيد هنا --}}
@if($depositAmount > 0)
<div id="cashDepositNotice" style="display:none;"
     class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mt-2">
    <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="text-amber-300 font-bold text-sm mb-2">
                {{ __('messages.cash_deposit_notice_title') }}
            </p>
            <ul class="text-gray-400 text-xs space-y-1.5">
                <li class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-amber-400 rounded-full flex-shrink-0"></span>
                    {{ __('messages.pay_on_arrival') }}: 
                    <strong class="text-white">{{ number_format($amountToPay, 0) }} MAD</strong>
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-purple-400 rounded-full flex-shrink-0"></span>
                    {{ __('messages.deposit_title') }}: 
                    <strong class="text-purple-300">{{ number_format($depositAmount, 0) }} MAD</strong>
                    <span class="text-gray-500">{{ __('messages.deposit_cash_note') }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endif
                        {{-- Stripe Card Form --}}
                        <div id="cardForm" class="bg-[#0f0f0f] rounded-2xl p-6 border border-gray-800 space-y-5">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                {{ __('messages.card_information') }}
                            </h3>

                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">{{ __('messages.card_details') }}</label>
                                <div id="card-element" class="bg-black text-white"></div>
                                <div id="card-errors" class="text-red-400 text-sm mt-2"></div>
                            </div>

                            {{-- Deposit Notice --}}
                            @if($depositAmount > 0)
                            <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <div>
                                        <p class="text-purple-300 font-bold text-sm mb-1">
                                            {{ __('messages.deposit_title') }}: {{ number_format($depositAmount, 0) }} MAD
                                        </p>
                                        <p class="text-gray-400 text-xs leading-relaxed">
                                            {{ __('messages.deposit_stripe_notice') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="flex items-center gap-3 p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                                <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-emerald-300 text-xs">{{ __('messages.payment_stripe_ssl') }}</span>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <button type="submit" id="submit-button"
                                class="w-full mt-6 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold py-4 rounded-2xl transition-all shadow-lg shadow-[#C89D66]/30 flex items-center justify-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span id="btnText">
                                {{ __('messages.pay_now') }} {{ number_format($amountToPay, 0) }} MAD
                                @if($depositAmount > 0)
                                    + {{ __('messages.deposit_title') }} {{ number_format($depositAmount, 0) }} MAD
                                @endif
                            </span>
                        </button>

                        <p class="text-center text-gray-600 text-xs mt-4 flex items-center justify-center gap-2">
                            <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('messages.payment_stripe_ssl') }}
                        </p>
                    </form>
                </div>
            </div>

            {{-- RIGHT: Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 sticky top-24">
                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-6 py-5 border-b border-gray-800">
                        <h3 class="text-lg font-bold text-white">Order Summary</h3>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Car --}}
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('messages.car_details') }}</p>
                            <p class="text-white font-bold">{{ $booking->car->brand }} {{ $booking->car->model }}</p>
                            <p class="text-gray-500 text-sm">{{ $booking->car->year }}</p>
                        </div>

                        {{-- Dates --}}
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('messages.rental_period') }}</p>
                            <p class="text-gray-300 text-sm">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</p>
                            <p class="text-gray-600 text-xs">→</p>
                            <p class="text-gray-300 text-sm">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</p>
                        </div>

                        <div class="border-t border-gray-800 pt-4 space-y-2">
                            {{-- Rental --}}
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">{{ __('messages.rental_period') }}</span>
                                <span class="text-white font-semibold">{{ number_format($amountToPay, 0) }} MAD</span>
                            </div>

                            {{-- Deposit --}}
                            @if($depositAmount > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-400">{{ __('messages.deposit_title') }} 🔒</span>
                                <span class="text-purple-300 font-semibold">{{ number_format($depositAmount, 0) }} MAD</span>
                            </div>
                            <p class="text-xs text-gray-600">{{ __('messages.deposit_refundable') }}</p>
                            @endif
                        </div>

                        {{-- Total --}}
                        <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 rounded-2xl p-4 border border-[#C89D66]/20">
                            <p class="text-xs text-gray-400 mb-1">{{ __('messages.total_price') }}</p>
                            <p class="text-3xl font-bold text-[#C89D66]">{{ number_format($amountToPay, 0) }} MAD</p>
                            @if($depositAmount > 0)
                                <p class="text-xs text-purple-400 mt-1">
                                    + {{ number_format($depositAmount, 0) }} MAD {{ __('messages.deposit_held') }}
                                </p>
                            @endif
                        </div>

                        {{-- Benefits --}}
                        <div class="space-y-2 pt-2 border-t border-gray-800">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3 h-3 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ __('messages.free_cancellation') }}
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3 h-3 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ __('messages.deposit_refundable') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
#card-element {
    min-height: 50px;
    background-color: #111827; /* dark */
    border-radius: 12px;
    padding: 12px;
}
#card-element .StripeElement {
    width: 100%;
}
</style>

<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const stripeKey = '{{ config('services.stripe.key') }}';
    if (!window.Stripe || !stripeKey) {
        console.error('Stripe JS failed to initialize.', {
            stripeLoaded: !!window.Stripe,
            stripeKeyPresent: !!stripeKey
        });
        return;
    }

    const stripe   = Stripe(stripeKey);
    const elements = stripe.elements();

    // ── Card Element ──
   const cardElement = elements.create('card', {
    style: {
        base: {
            color: '#ffffff',
            fontSize: '16px',
            '::placeholder': {
                color: '#9ca3af'
            }
        },
        invalid: {
            color: '#ef4444'
        }
    }
});
    cardElement.mount('#card-element');

    cardElement.on('change', e => {
        document.getElementById('card-errors').textContent = e.error ? e.error.message : '';
    });

    // ── Payment method toggle ──
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const isCard = radio.value === 'card';

            // Show/hide card form
            document.getElementById('cardForm').style.display = isCard ? 'block' : 'none';

            // Show/hide cash deposit notice
            const cashNotice = document.getElementById('cashDepositNotice');
            if (cashNotice) cashNotice.style.display = isCard ? 'none' : 'block';

            // Update label styles
            document.getElementById('cardLabel').className = isCard
                ? 'flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-[#C89D66] bg-[#C89D66]/10'
                : 'flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-gray-800 hover:border-gray-600';

            document.getElementById('cashLabel').className = !isCard
                ? 'flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-[#C89D66] bg-[#C89D66]/10'
                : 'flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all border-gray-800 hover:border-gray-600';

            // Update button text
            const btnText = document.getElementById('btnText');
            if (isCard) {
                btnText.textContent = '{{ __("messages.pay_now") }} {{ number_format($amountToPay, 0) }} MAD{{ $depositAmount > 0 ? " + " . __("messages.deposit_title") . " " . number_format($depositAmount, 0) . " MAD" : "" }}';
            } else {
                btnText.textContent = '{{ __("messages.pay_on_arrival") }}';
            }
        });
    });

    // ── Form submission ──
    document.getElementById('payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        console.log('Stripe payment form submit handler running');

        const method = document.querySelector('input[name="payment_method"]:checked').value;
        const btn    = document.getElementById('submit-button');

        // ── CASH ──
        if (method === 'cash') {
            const form  = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('payments.store', $booking) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const pm = document.createElement('input');
            pm.type = 'hidden'; pm.name = 'payment_method'; pm.value = 'cash';
            form.appendChild(pm);

            document.body.appendChild(form);
            form.submit();
            return;
        }

        // ── CARD ──
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ __('messages.processing') }}</span>
        `;

        try {
            // 1. Confirm rental payment (charges immediately)
            const { paymentIntent: rentalResult, error: rentalError } = await stripe.confirmCardPayment(
                document.getElementById('rental_payment_intent').value,
                { payment_method: { card: cardElement } }
            );

            console.log('Rental intent result', {
                id: rentalResult?.id,
                status: rentalResult?.status,
                type: rentalResult?.metadata?.type ?? 'rental'
            });

            if (rentalError) {
                document.getElementById('card-errors').textContent = rentalError.message;
                btn.disabled = false;
                btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span>{{ __('messages.pay_now') }}</span>`;
                return;
            }

            @if($depositIntent)
            // 2. Authorize deposit (blocks but doesn't charge)
            const { paymentIntent: depositResult, error: depositError } = await stripe.confirmCardPayment(
                document.getElementById('deposit_payment_intent').value,
                { payment_method: { card: cardElement } }
            );

            console.log('Deposit intent result', {
                id: depositResult?.id,
                status: depositResult?.status,
                type: depositResult?.metadata?.type ?? 'deposit'
            });

            if (depositError) {
                document.getElementById('card-errors').textContent = 'Deposit authorization failed: ' + depositError.message;
                btn.disabled = false;
                btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span>{{ __('messages.pay_now') }}</span>`;
                return;
            }

            if (depositResult?.status !== 'requires_capture') {
                console.warn('Deposit authorization was not held correctly.', {
                    id: depositResult?.id,
                    status: depositResult?.status,
                    type: depositResult?.metadata?.type ?? 'deposit'
                });
            }
            @endif

            // 3. Submit to server
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('payments.store', $booking) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const pm = document.createElement('input');
            pm.type = 'hidden'; pm.name = 'payment_method'; pm.value = 'card';
            form.appendChild(pm);

            const rpi = document.createElement('input');
            rpi.type = 'hidden'; rpi.name = 'rental_payment_intent'; rpi.value = rentalResult.id;
            form.appendChild(rpi);

            @if($depositIntent)
            if (depositResult?.status === 'requires_capture') {
                const dpi = document.createElement('input');
                dpi.type = 'hidden'; dpi.name = 'deposit_payment_intent'; dpi.value = depositResult.id;
                form.appendChild(dpi);
            }
            @endif

            document.body.appendChild(form);
            form.submit();

        } catch (err) {
            console.error('Payment error:', err);
            document.getElementById('card-errors').textContent = 'An error occurred. Please try again.';
            btn.disabled = false;
            btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><span>{{ __('messages.pay_now') }}</span>`;
        }
    });
});
</script>
