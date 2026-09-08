@extends('layouts.app')
@section('title', __('messages.payment'))

@section('content')
@php
    $rentalPaymentAlreadySettled = $rentalPaymentAlreadySettled ?? ($amountToPay <= 0);
    $rentalAmountLabel = number_format($amountToPay, 0);
    $securityDepositAmountLabel = number_format($securityDepositAmount, 0);
    $cardCtaLabel = __('messages.pay_amount', ['amount' => $rentalAmountLabel]);
    $cardCtaNote = __('messages.deposit_hold_secondary', ['amount' => $securityDepositAmountLabel]);
    $pickupCtaLabel = __('messages.confirm_pay_at_pickup');
    $pickupCtaNote = __('messages.pay_at_pickup_subtitle');
@endphp

<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-6xl mx-auto px-6">
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

                    <form id="payment-form" class="p-5 sm:p-8">
                        @if($rentalPaymentAlreadySettled)
                            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-6">
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-bold mb-1">{{ __('messages.rental_payment_completed') }}</p>
                                        <p class="text-sm text-gray-400">{{ __('messages.no_rental_payment_due') }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            @csrf

                            <input type="hidden" name="_method" value="POST">
                            <input type="hidden" id="rental_payment_intent" name="rental_payment_intent" value="{{ $rentalIntent->client_secret }}">
                            @if($securityDepositIntent)
                                <input type="hidden" id="security_deposit_intent" name="security_deposit_intent" value="{{ $securityDepositIntent->client_secret }}">
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <label class="payment-method-card flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all min-w-0" id="cardLabel" data-selected="true">
                                    <input type="radio" name="payment_method" value="card" id="payCard" class="mt-1 w-5 h-5 accent-[#C89D66]" checked>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-2 min-w-0">
                                            <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-white font-bold break-words">{{ __('messages.card_payment') }}</p>
                                                <p class="text-sm text-gray-400 break-words">{{ __('messages.card_payment_subtitle') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2" aria-label="{{ __('messages.card_brand_indicators') }}">
                                            <span class="inline-flex h-7 items-center rounded-md border border-sky-400/30 bg-sky-500/10 px-2.5 text-[11px] font-bold uppercase tracking-wide text-sky-200">Visa</span>
                                            <span class="inline-flex h-7 items-center rounded-md border border-rose-400/30 bg-rose-500/10 px-2.5 text-[11px] font-bold uppercase tracking-wide text-rose-200">Mastercard</span>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-method-card flex items-start gap-4 p-5 border-2 rounded-2xl cursor-pointer transition-all min-w-0" id="cashLabel" data-selected="false">
                                    <input type="radio" name="payment_method" value="cash" id="payCash" class="mt-1 w-5 h-5 accent-[#C89D66]">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-3 mb-1 min-w-0">
                                            <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center flex-shrink-0">
                                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-white font-bold break-words">{{ __('messages.cash_payment') }}</p>
                                                <p class="text-sm text-gray-400 break-words">{{ __('messages.pay_at_pickup_subtitle') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            @if($securityDepositAmount > 0)
                                <div id="cashSecurityDepositNotice" style="display:none;" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mt-2 mb-6">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <div class="min-w-0">
                                            <p class="text-amber-300 font-bold text-sm mb-2">{{ __('messages.security_deposit_cash_notice_title') }}</p>
                                            <ul class="text-gray-400 text-xs space-y-1.5">
                                                <li class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 bg-amber-400 rounded-full flex-shrink-0"></span>
                                                    {{ __('messages.rental_total') }}:
                                                    <strong class="text-white">{{ $rentalAmountLabel }} MAD</strong>
                                                </li>
                                                <li class="flex items-center gap-2">
                                                    <span class="w-1.5 h-1.5 bg-purple-400 rounded-full flex-shrink-0"></span>
                                                    {{ __('messages.security_deposit_title') }}:
                                                    <strong class="text-purple-300">{{ $securityDepositAmountLabel }} MAD</strong>
                                                    <span class="text-gray-500">{{ __('messages.security_deposit_cash_note') }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div id="cardForm" class="bg-[#0f0f0f] rounded-2xl p-5 sm:p-6 border border-gray-800 space-y-5" aria-hidden="false">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        {{ __('messages.card_information') }}
                                    </h3>
                                    <span class="text-xs text-gray-500">{{ __('messages.secure_payment_powered_by_stripe') }}</span>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-2">{{ __('messages.card_details') }}</label>
                                    <div id="card-element" class="bg-black text-white"></div>
                                    <div id="card-errors" class="text-red-400 text-sm mt-2 min-h-[1.25rem]" role="alert" aria-live="polite"></div>
                                </div>

                                @if($securityDepositAmount > 0)
                                    <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            <div class="min-w-0">
                                                <p class="text-purple-300 font-bold text-sm mb-1">{{ __('messages.security_deposit_title') }}</p>
                                                <p class="text-white font-semibold text-sm mb-2">
                                                    {{ __('messages.security_deposit_authorization_hold', ['amount' => $securityDepositAmountLabel]) }}
                                                </p>
                                                <p class="text-gray-400 text-xs leading-relaxed">{{ __('messages.security_deposit_stripe_notice') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="flex items-center gap-3 p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                                    <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-emerald-300 text-xs">{{ __('messages.stripe_card_details_secure') }}</span>
                                </div>
                            </div>

                            <button type="submit" id="submit-button"
                                    class="w-full mt-6 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold py-4 px-4 rounded-2xl transition-all shadow-lg shadow-[#C89D66]/30 flex items-center justify-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span id="btnText" class="min-w-0 text-center break-words">{{ $cardCtaLabel }}</span>
                            </button>

                            <p id="paymentCtaNote" class="text-center text-gray-500 text-xs mt-3 leading-relaxed break-words">
                                @if($securityDepositAmount > 0)
                                    {{ $cardCtaNote }}
                                @else
                                    {{ __('messages.stripe_card_details_secure') }}
                                @endif
                            </p>

                            <p class="text-center text-gray-600 text-xs mt-4 flex items-center justify-center gap-2">
                                <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('messages.payment_stripe_ssl') }}
                            </p>
                        @endif
                    </form>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 sticky top-24">
                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-6 py-5 border-b border-gray-800">
                        <h3 class="text-lg font-bold text-white">{{ __('messages.order_summary') }}</h3>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('messages.car_details') }}</p>
                            <p class="text-white font-bold break-words">{{ $booking->car->brand }} {{ $booking->car->model }}</p>
                            <p class="text-gray-500 text-sm">{{ $booking->car->year }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-1">{{ __('messages.rental_period') }}</p>
                            <p class="text-gray-300 text-sm">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</p>
                            <p class="text-gray-600 text-xs">&rarr;</p>
                            <p class="text-gray-300 text-sm">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</p>
                        </div>

                        <div class="border-t border-gray-800 pt-4 space-y-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white">{{ __('messages.rental_total') }}</p>
                                    <p class="text-xs text-gray-500">{{ __('messages.rental_total_note') }}</p>
                                </div>
                                <span class="text-white text-xl font-bold whitespace-nowrap">{{ $rentalAmountLabel }} MAD</span>
                            </div>

                            @if($securityDepositAmount > 0)
                                <div class="rounded-xl border border-purple-500/25 bg-purple-500/10 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-purple-200">{{ __('messages.refundable_security_deposit') }}</p>
                                            <p class="text-xs text-gray-400">{{ __('messages.authorization_hold_not_rental_total') }}</p>
                                        </div>
                                        <span class="text-purple-200 font-semibold whitespace-nowrap">{{ $securityDepositAmountLabel }} MAD</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 rounded-2xl p-4 border border-[#C89D66]/20">
                            <p id="summaryPaymentLabel" class="text-xs text-gray-400 mb-1">{{ __('messages.amount_charged_today') }}</p>
                            <p class="text-3xl font-bold text-[#C89D66]">{{ $rentalAmountLabel }} MAD</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('messages.deposit_not_included_in_total') }}</p>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-gray-800">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3 h-3 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ __('messages.free_cancellation') }}
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3 h-3 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ __('messages.authorization_hold_not_rental_total') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method-card {
    border-color: rgb(31 41 55);
    background: rgba(15, 15, 15, 0.72);
}
.payment-method-card[data-selected="true"] {
    border-color: #C89D66;
    background: rgba(200, 157, 102, 0.12);
    box-shadow: 0 18px 40px rgba(200, 157, 102, 0.10), 0 0 0 1px rgba(200, 157, 102, 0.22);
}
.payment-method-card[data-selected="false"]:hover {
    border-color: rgb(75 85 99);
    background: rgba(31, 41, 55, 0.22);
}
.payment-method-card:focus-within {
    outline: 2px solid rgba(200, 157, 102, 0.75);
    outline-offset: 3px;
}
#card-element {
    min-height: 54px;
    background-color: #050505;
    border: 1px solid rgb(55 65 81);
    border-radius: 12px;
    padding: 16px;
}
#card-element .StripeElement {
    width: 100%;
}
</style>

@unless($rentalPaymentAlreadySettled)
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stripeKey = '{{ config('services.stripe.key') }}';
    if (!window.Stripe || !stripeKey) {
        document.getElementById('card-errors').textContent = @js(__('messages.payment_form_load_failed'));
        return;
    }

    const stripe = Stripe(stripeKey);
    const elements = stripe.elements();

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

    const cardForm = document.getElementById('cardForm');
    const cashNotice = document.getElementById('cashSecurityDepositNotice');
    const ctaNote = document.getElementById('paymentCtaNote');
    const summaryPaymentLabel = document.getElementById('summaryPaymentLabel');
    const submitIcon = `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>`;
    const cardCtaLabel = @js($cardCtaLabel);
    const cardCtaNote = @js($securityDepositAmount > 0 ? $cardCtaNote : __('messages.stripe_card_details_secure'));
    const pickupCtaLabel = @js($pickupCtaLabel);
    const pickupCtaNote = @js($pickupCtaNote);
    const amountChargedTodayLabel = @js(__('messages.amount_charged_today'));
    const pickupPaymentLabel = @js(__('messages.rental_payment_due_at_pickup'));

    function restoreSubmitButton(isCard) {
        const btn = document.getElementById('submit-button');
        btn.innerHTML = `${submitIcon}<span id="btnText" class="min-w-0 text-center break-words"></span>`;
        document.getElementById('btnText').textContent = isCard ? cardCtaLabel : pickupCtaLabel;
        ctaNote.textContent = isCard ? cardCtaNote : pickupCtaNote;
    }

    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const isCard = radio.value === 'card';

            cardForm.style.display = isCard ? 'block' : 'none';
            cardForm.setAttribute('aria-hidden', isCard ? 'false' : 'true');

            if (cashNotice) cashNotice.style.display = isCard ? 'none' : 'block';

            document.getElementById('cardLabel').dataset.selected = isCard ? 'true' : 'false';
            document.getElementById('cashLabel').dataset.selected = !isCard ? 'true' : 'false';

            document.getElementById('btnText').textContent = isCard ? cardCtaLabel : pickupCtaLabel;
            ctaNote.textContent = isCard ? cardCtaNote : pickupCtaNote;
            summaryPaymentLabel.textContent = isCard ? amountChargedTodayLabel : pickupPaymentLabel;
        });
    });

    document.getElementById('payment-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const method = document.querySelector('input[name="payment_method"]:checked').value;
        const btn = document.getElementById('submit-button');

        if (method === 'cash') {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('payments.store', $booking) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const pm = document.createElement('input');
            pm.type = 'hidden';
            pm.name = 'payment_method';
            pm.value = 'cash';
            form.appendChild(pm);

            document.body.appendChild(form);
            form.submit();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span>{{ __('messages.processing') }}</span>
        `;

        try {
            const { paymentIntent: rentalResult, error: rentalError } = await stripe.confirmCardPayment(
                document.getElementById('rental_payment_intent').value,
                { payment_method: { card: cardElement } }
            );

            if (rentalError) {
                document.getElementById('card-errors').textContent = rentalError.message;
                btn.disabled = false;
                restoreSubmitButton(true);
                return;
            }

            @if($securityDepositIntent)
            const { paymentIntent: securityDepositResult, error: securityDepositError } = await stripe.confirmCardPayment(
                document.getElementById('security_deposit_intent').value,
                { payment_method: { card: cardElement } }
            );

            if (securityDepositError) {
                document.getElementById('card-errors').textContent = @js(__('messages.security_deposit_authorization_failed')) + ' ' + securityDepositError.message;
                btn.disabled = false;
                restoreSubmitButton(true);
                return;
            }

            if (securityDepositResult?.status !== 'requires_capture') {
                document.getElementById('card-errors').textContent = @js(__('messages.security_deposit_authorization_incomplete'));
                btn.disabled = false;
                restoreSubmitButton(true);
                return;
            }
            @endif

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('payments.store', $booking) }}';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const pm = document.createElement('input');
            pm.type = 'hidden';
            pm.name = 'payment_method';
            pm.value = 'card';
            form.appendChild(pm);

            const rpi = document.createElement('input');
            rpi.type = 'hidden';
            rpi.name = 'rental_payment_intent';
            rpi.value = rentalResult.id;
            form.appendChild(rpi);

            @if($securityDepositIntent)
            if (securityDepositResult?.status === 'requires_capture') {
                const dpi = document.createElement('input');
                dpi.type = 'hidden';
                dpi.name = 'security_deposit_intent';
                dpi.value = securityDepositResult.id;
                form.appendChild(dpi);
            }
            @endif

            document.body.appendChild(form);
            form.submit();
        } catch (err) {
            document.getElementById('card-errors').textContent = @js(__('messages.generic_try_again'));
            btn.disabled = false;
            restoreSubmitButton(true);
        }
    });
});
</script>
@endunless
@endsection
