<span
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold whitespace-nowrap',
    ])->class([
        'bg-amber-500/15 text-amber-300 border-amber-400/35' => $status === 'pending',
        'bg-blue-500/15 text-blue-300 border-blue-400/35' => $status === 'confirmed',
        'bg-emerald-500/15 text-emerald-300 border-emerald-400/35' => $status === 'active',
        'bg-purple-500/15 text-purple-300 border-purple-400/35' => $status === 'completed',
        'bg-red-500/15 text-red-300 border-red-400/35' => $isCancelled(),
        'bg-gray-500/15 text-gray-300 border-gray-400/35' => $status === 'no_show',
        'bg-teal-500/15 text-teal-300 border-teal-400/35' => $status === 'refunded',
        'bg-slate-500/15 text-slate-300 border-slate-400/35' => ! in_array($status, ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'canceled', 'no_show', 'refunded'], true),
    ]) }}
    data-booking-status="{{ $status }}"
>
    {{ $label() }}
</span>
