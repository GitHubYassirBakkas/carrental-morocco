@extends('layouts.app')

@section('title', __('messages.invoice') . ' #' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT))

@section('content')
<section class="bg-[#0b0b0b] text-white py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <p class="text-sm text-[#C89D66] font-semibold">{{ __('messages.invoice') }}</p>
                <h1 class="text-3xl font-bold">#{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</h1>
                <p class="text-gray-400 mt-2">{{ __('messages.booking') }} #{{ $invoice->booking_id }}</p>
            </div>

            <a href="{{ route('customer.invoices.download', $invoice) }}"
               class="inline-flex items-center justify-center px-5 py-3 bg-[#C89D66] text-black font-semibold rounded-lg hover:bg-[#b88b54] transition">
                {{ __('messages.download_pdf') }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-[#151515] border border-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-5">{{ __('messages.rental_summary') }}</h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.vehicle') }}</dt>
                        <dd class="text-white font-medium">
                            {{ $invoice->booking?->car?->brand }} {{ $invoice->booking?->car?->model }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.status') }}</dt>
                        <dd class="text-white font-medium">{{ ui_status($invoice->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.pickup') }}</dt>
                        <dd class="text-white font-medium">
                            {{ $invoice->booking?->pickupLocation?->name ?? __('messages.not_available_abbrev') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.dropoff') }}</dt>
                        <dd class="text-white font-medium">
                            {{ $invoice->booking?->dropoffLocation?->name ?? __('messages.not_available_abbrev') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.start_date') }}</dt>
                        <dd class="text-white font-medium">
                            {{ $invoice->booking?->start_date?->format('M d, Y') ?? __('messages.not_available_abbrev') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">{{ __('messages.end_date') }}</dt>
                        <dd class="text-white font-medium">
                            {{ $invoice->booking?->end_date?->format('M d, Y') ?? __('messages.not_available_abbrev') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-[#151515] border border-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-5">{{ __('messages.totals') }}</h2>

                <div class="space-y-3">
                    <div class="flex justify-between gap-4 text-gray-300">
                        <span>{{ __('messages.subtotal') }}</span>
                        <span>{{ number_format($invoice->subtotal, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between gap-4 text-gray-300">
                        <span>{{ __('messages.discount') }}</span>
                        <span>{{ number_format($invoice->discount_amount ?? 0, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between gap-4 text-gray-300">
                        <span>{{ __('messages.tax') }}</span>
                        <span>{{ number_format($invoice->tax_amount, 2) }} MAD</span>
                    </div>
                    <div class="border-t border-gray-800 pt-3 flex justify-between gap-4 text-lg font-bold">
                        <span>{{ __('messages.total') }}</span>
                        <span class="text-[#C89D66]">{{ number_format($invoice->total_amount, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between gap-4 text-gray-300">
                        <span>{{ __('messages.paid') }}</span>
                        <span>{{ number_format($invoice->paid_amount, 2) }} MAD</span>
                    </div>
                    <div class="flex justify-between gap-4 text-gray-300">
                        <span>{{ __('messages.balance') }}</span>
                        <span>{{ number_format($invoice->balance, 2) }} MAD</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
