              <!-- Financial Summary -->
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Financial Summary</h3>
    @php
        $pricingBreakdown = $booking->pricing_breakdown;
    @endphp
    <div class="space-y-3">

        {{-- Base Rental --}}
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-gray-400 text-sm">Base Rental</span>
                <p class="text-xs text-gray-500">{{ $booking->total_days }} days × {{ number_format($booking->rental_price_per_day, 2) }} MAD</p>
            </div>
            <span class="text-white font-medium">
                {{ number_format($pricingBreakdown['rental_amount'], 2) }} MAD
            </span>
        </div>

        {{-- Protection Plan --}}
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-gray-400 text-sm">Protection Plan</span>
                <p class="text-xs text-gray-500">{{ $booking->insurance?->name ?? 'No Protection Plan' }}</p>
            </div>
            <span class="text-white font-medium">
                {{ number_format($pricingBreakdown['protection_plan_amount'], 2) }} MAD
            </span>
        </div>

        @if($pricingBreakdown['extras_amount'] > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-gray-400 text-sm">Extras</span>
                <p class="text-xs text-gray-500">Drop-off fees or future booking extras</p>
            </div>
            <span class="text-white font-medium">
                {{ number_format($pricingBreakdown['extras_amount'], 2) }} MAD
            </span>
        </div>
        @endif

        @if($pricingBreakdown['discount_amount'] > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-emerald-400 text-sm font-semibold">Discount</span>
                <p class="text-xs text-emerald-300">{{ $booking->couponUsage?->coupon?->code ?? 'Applied discount' }}</p>
            </div>
            <span class="text-emerald-400 font-semibold">
                -{{ number_format($pricingBreakdown['discount_amount'], 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Damage Charges --}}
        @if($booking->total_checkout_damage > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Damage Charges</span>
                <p class="text-xs text-red-300">{{ $booking->checkoutDamages->where('is_chargeable', true)->count() }} damage(s)</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->total_checkout_damage, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Fuel Charge --}}
        @if($booking->fuel_charge > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Fuel Charge</span>
                <p class="text-xs text-red-300">{{ $booking->fuel_used }}% fuel used</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->fuel_charge, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Late Fee --}}
        @if($booking->late_fee > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Late Return Fee</span>
                <p class="text-xs text-red-300">{{ floor($booking->late_minutes / 60) }} hours late</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->late_fee, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Subtotal (before tax) --}}
        @php
            $subtotalBeforeTax = $booking->invoice
                ? $booking->invoice->subtotal
                : $pricingBreakdown['subtotal_amount'] - $pricingBreakdown['discount_amount'];
            $taxAmount = $booking->invoice
                ? $booking->invoice->tax_amount
                : $pricingBreakdown['tax_amount'];
            $grandTotal = $booking->invoice
                ? $booking->invoice->total_amount
                : $pricingBreakdown['total_amount'];
        @endphp
        
        @if($booking->total_checkout_damage > 0 || $booking->fuel_charge > 0 || $booking->late_fee > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <span class="text-gray-400 text-sm">Subtotal</span>
            <span class="text-white font-medium">
                {{ number_format($subtotalBeforeTax, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Tax --}}
        @if($taxAmount > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <span class="text-gray-400 text-sm">Tax</span>
            <span class="text-white font-medium">
                {{ number_format($taxAmount, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Grand Total --}}
        <div class="flex justify-between pt-3">
            <span class="text-orange-400 font-bold text-lg">Grand Total</span>
            <span class="text-orange-400 font-bold text-2xl">
                {{ number_format($grandTotal, 2) }} MAD
            </span>
        </div>

        {{-- Invoice Status --}}
        @if($booking->invoice)
            <div class="flex justify-between">
            <span class="text-gray-400 text-sm">Amount Paid</span>
            <span class="text-emerald-400 font-semibold">
                {{ number_format($booking->invoice->paid_amount ?? 0, 2) }} MAD
            </span>
        </div>

        @if(($booking->invoice->balance ?? 0) > 0)
        <div class="flex justify-between">
            <span class="text-yellow-400 text-sm font-semibold">Balance Due</span>
            <span class="text-yellow-400 font-bold text-lg">
                {{ number_format($booking->invoice->balance ?? 0, 2) }} MAD
            </span>
        </div>
        @else
        <div class="flex justify-between">
            <span class="text-emerald-400 text-sm font-semibold">Status</span>
            <span class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 rounded-lg text-xs font-bold">
                PAID IN FULL ✓
            </span>
        </div>
        @endif
        @endif

    </div>
</div>
