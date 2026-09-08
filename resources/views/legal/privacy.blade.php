@extends('layouts.app')

@section('title', __('messages.privacy_policy').' | Car Rental Morocco')

@section('content')
<section class="legal-page">
    <div class="legal-shell">
        <p class="legal-kicker">{{ __('messages.legal_pages') }}</p>
        <h1>{{ __('messages.privacy_policy') }}</h1>
        <p class="legal-lead">{{ __('messages.legal_content.privacy.lead') }}</p>

        <div class="legal-content">
            @foreach(__('messages.legal_content.privacy.sections') as $section)
                <h2>{{ $section['title'] }}</h2>
                @foreach((array) $section['body'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            @endforeach

            <h2>{{ __('messages.legal_content.privacy.contact_title') }}</h2>
            <p>
                {{ __('messages.legal_content.privacy.contact_body') }}
                @if($contact['email'])
                    {{ __('messages.legal_content.contact_email_connector') }} <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                @endif
                {{ __('messages.legal_content.contact_period') }}
            </p>

            <p class="legal-note">{{ __('messages.legal_content.note') }}</p>
        </div>
    </div>
</section>

@include('legal.partials.styles')
@endsection
