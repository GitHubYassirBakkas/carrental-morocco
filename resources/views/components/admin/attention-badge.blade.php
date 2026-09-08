@props([
    'count' => 0,
    'keyName' => '',
    'label' => 'Attention',
])

@if((int) $count > 0)
    <span class="adm-nav-badge" data-admin-attention-key="{{ $keyName }}" aria-label="{{ $label }} needs attention: {{ (int) $count }}" title="{{ $label }} needs attention: {{ (int) $count }}">{{ (int) $count >= 100 ? '99+' : (int) $count }}</span>
@endif
