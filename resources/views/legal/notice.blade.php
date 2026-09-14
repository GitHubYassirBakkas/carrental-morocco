@extends('layouts.app')

@section('title', __('messages.legal_notice').' | '.($contact['name'] ?? setting('site_name', 'Car Rental Morocco')))

@section('content')
<section class="legal-page">
    <div class="legal-shell">
        <p class="legal-kicker">{{ __('messages.legal_pages') }}</p>
        <h1>{{ __('messages.legal_notice') }}</h1>
        <p class="legal-lead">{{ __('messages.legal_content.notice.lead') }}</p>

        <div class="legal-content">
            <h2>{{ __('messages.legal_content.notice.site_service_title') }}</h2>
            <p><strong>{{ __('messages.legal_content.notice.service_name_label') }}:</strong> {{ $contact['name'] ?? setting('site_name', 'Car Rental Morocco') }}</p>
            <p><strong>{{ __('messages.legal_content.notice.website_purpose_label') }}:</strong> {{ __('messages.legal_content.notice.website_purpose_body') }}</p>

            <h2>{{ __('messages.legal_content.notice.operator_title') }}</h2>
            <p>{{ __('messages.legal_content.notice.operator_body') }}</p>

            @if($contact['email'] || $contact['phone'] || $contact['address'] || $primaryLocation)
                <h2>{{ __('messages.legal_content.notice.public_contact_title') }}</h2>
                @if($contact['email'])
                    <p><strong>{{ __('messages.email') }}:</strong> <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></p>
                @endif
                @if($contact['phone'])
                    <p><strong>{{ __('messages.phone') }}:</strong> {{ $contact['phone'] }}</p>
                @endif
                @if($contact['address'])
                    <p><strong>{{ __('messages.address') }}:</strong> {{ $contact['address'] }}</p>
                @elseif($primaryLocation)
                    <p><strong>{{ __('messages.primary_location') }}:</strong> {{ $primaryLocation->full_address }}</p>
                @endif
            @endif

            <h2>{{ __('messages.legal_content.notice.missing_details_title') }}</h2>
            <p>{{ __('messages.legal_content.notice.missing_details_body') }}</p>

            <p class="legal-note">{{ __('messages.legal_content.note') }}</p>
        </div>
    </div>
</section>

@include('legal.partials.styles')
@endsection
