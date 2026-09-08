@extends('admin.layouts.app')

@section('content')

<div class="cf">

    {{-- ══ HEADER ══ --}}
    <div class="cf-header">
        <div class="cf-header-left">
            <div class="cf-breadcrumb">
                <a href="{{ route('admin.cars.index') }}" class="cf-bc-link">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    Fleet
                </a>
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:10px;height:10px;color:#2a3045"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                Edit Car
            </div>
            <h1 class="cf-title">{{ $car->full_name }}</h1>
            <p class="cf-sub">ID #{{ $car->id }} · Last updated {{ $car->updated_at->diffForHumans() }}</p>
        </div>
        <div class="cf-header-actions">
            <a href="{{ route('admin.cars.show', $car) }}" class="cf-view-btn" target="_blank">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                Preview
            </a>
            <a href="{{ route('admin.cars.index') }}" class="cf-back-btn">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                Back
            </a>
        </div>
    </div>

    {{-- ══ ERRORS ══ --}}
    @if($errors->any())
    <div class="cf-errors">
        <div class="cf-errors-icon">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        </div>
        <div>
            <div class="cf-errors-title">Please fix the following errors</div>
            @foreach($errors->all() as $error)
                <div class="cf-errors-item">{{ $error }}</div>
            @endforeach
        </div>
    </div>
    @endif

    <form action="{{ route('admin.cars.update', $car) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="cf-layout">

            {{-- ══ LEFT ══ --}}
            <div class="cf-left">

                {{-- Basic Info --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#3b82f6"></div>
                        Basic Information
                    </div>
                    <div class="cf-grid-2">
                        <div class="cf-field">
                            <label>Brand <span class="cf-req">*</span></label>
                            <input type="text" name="brand" value="{{ old('brand', $car->brand) }}" required>
                        </div>
                        <div class="cf-field">
                            <label>Model <span class="cf-req">*</span></label>
                            <input type="text" name="model" value="{{ old('model', $car->model) }}" required>
                        </div>
                        <div class="cf-field">
                            <label>Year <span class="cf-req">*</span></label>
                            <input type="number" name="year" value="{{ old('year', $car->year) }}" required>
                        </div>
                        <div class="cf-field">
                            <label>Type <span class="cf-req">*</span></label>
                            <select name="type" required>
                                <option value="">Select type</option>
                                @foreach(['Economy','Compact','Sedan','SUV','Luxury','Coupe','Family / Van'] as $t)
                                    <option value="{{ $t }}" {{ old('type', $car->type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Specs --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#8b5cf6"></div>
                        Specifications
                    </div>
                    <div class="cf-grid-3">
                        <div class="cf-field">
                            <label>Transmission <span class="cf-req">*</span></label>
                            <select name="transmission" required>
                                <option value="">Select</option>
                                <option value="Automatic" {{ old('transmission', $car->transmission) == 'Automatic' ? 'selected' : '' }}>Automatic</option>
                                <option value="Manual"    {{ old('transmission', $car->transmission) == 'Manual'    ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div class="cf-field">
                            <label>Fuel Type <span class="cf-req">*</span></label>
                            <select name="fuel_type" required>
                                <option value="">Select</option>
                                @foreach(['Petrol','Diesel','Hybrid','Electric'] as $f)
                                    <option value="{{ $f }}" {{ old('fuel_type', $car->fuel_type) == $f ? 'selected' : '' }}>{{ $f }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cf-field">
                            <label>Seats <span class="cf-req">*</span></label>
                            <input type="number" name="seats" value="{{ old('seats', $car->seats) }}" min="1" max="12" required>
                        </div>
                        <div class="cf-field">
                            <label>Doors</label>
                            <input type="number" name="doors" value="{{ old('doors', $car->doors) }}" min="2" max="6">
                        </div>
                        <div class="cf-field">
                            <label>Luggage Bags</label>
                            <input type="number" name="luggage" value="{{ old('luggage', $car->luggage) }}" min="0">
                        </div>
                    </div>
                </div>

                {{-- Pricing & Location --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#10b981"></div>
                        Pricing & Location
                    </div>
                    <div class="cf-grid-2">
                        <div class="cf-field">
                            <label>Price per Day (MAD) <span class="cf-req">*</span></label>
                            <div class="cf-input-prefix">
                                <span>MAD</span>
                                <input type="number" name="price_per_day" value="{{ old('price_per_day', $car->price_per_day) }}" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="cf-field">
                            <label>Location <span class="cf-req">*</span></label>
                            <select name="location_id" required>
                                <option value="">Select location</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id', $car->location_id) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->name }}@if($loc->city) — {{ $loc->city }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>


                {{-- Rental Conditions --}}
<div class="cf-section">
    <div class="cf-section-head">
        <div class="cf-section-dot" style="background:#c084fc"></div>
        Rental Conditions
    </div>
    <div class="cf-grid-2">

        {{-- Security Deposit Amount --}}
        <div class="cf-field">
            <label>Security Deposit (MAD)</label>
            <div class="cf-input-prefix">
                <span>MAD</span>
                <input type="number"
                       name="security_deposit_amount"
                       value="{{ old('security_deposit_amount', $car->security_deposit_amount ?? '') }}"
                       min="0"
                       step="100"
                       placeholder="e.g. 3000">
            </div>
            <p style="font-size:0.65rem;color:#52596e;margin-top:4px;">
                🔒 Held on customer's card (refundable after inspection)
            </p>
        </div>

        {{-- Minimum Age --}}
        <div class="cf-field">
            <label>Minimum Driver Age</label>
            <input type="number"
                   name="minimum_age"
                   value="{{ old('minimum_age', $car->minimum_age ?? 21) }}"
                   min="18"
                   max="30"
                   placeholder="21">
        </div>

        {{-- Fuel Policy --}}
        <div class="cf-field">
            <label>Fuel Policy</label>
            <select name="fuel_policy">
                <option value="">Select policy</option>
                @foreach(['Full to Full', 'Full to Empty', 'Same to Same'] as $fp)
                    <option value="{{ $fp }}" {{ old('fuel_policy', $car->fuel_policy) == $fp ? 'selected' : '' }}>
                        {{ $fp }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Cancellation Policy --}}
        <div class="cf-field">
            <label>Cancellation Policy</label>
            <select name="cancellation_policy">
                <option value="">Select policy</option>
                @foreach([
                    'Free cancellation 48h before',
                    'Free cancellation 72h before',
                    'No refund within 7 days',
                    'Free cancellation up to 72 hours before pickup',
                ] as $cp)
                    <option value="{{ $cp }}" {{ old('cancellation_policy', $car->cancellation_policy) == $cp ? 'selected' : '' }}>
                        {{ $cp }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>

    {{-- Required Documents --}}
    <div class="cf-field" style="margin-top:10px;">
        <label>Required Documents</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
            @foreach(['Valid driving license (3+ years)', 'Passport or National ID', 'Credit Card', 'International driving license'] as $doc)
                @php
                    $currentDocs = is_array($car->required_documents) 
                        ? $car->required_documents 
                        : (is_string($car->required_documents) ? json_decode($car->required_documents, true) : []);
                @endphp
                <label class="cf-feat-item" style="min-width:fit-content;">
                    <input type="checkbox" name="required_documents[]" value="{{ $doc }}"
                           {{ in_array($doc, old('required_documents', $currentDocs ?? [])) ? 'checked' : '' }}>
                    <div class="cf-feat-check">
                        <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span>{{ $doc }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

                {{-- Description --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#f59e0b"></div>
                        Description
                    </div>
                    <div class="cf-field">
                        <label>Car Description</label>
                        <textarea name="description" rows="4">{{ old('description', $car->description) }}</textarea>
                    </div>
                </div>

                {{-- Features --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#6366f1"></div>
                        Features
                    </div>
                    <div class="cf-features-grid">
                        @foreach(['Air Conditioning','GPS','Bluetooth','USB','Cruise Control','Leather Seats','Backup Camera','Parking Sensors','Sunroof','Heated Seats'] as $feat)
                        <label class="cf-feat-item">
                            <input type="checkbox" name="features[]" value="{{ $feat }}"
                                {{ in_array($feat, old('features', $car->features ?? [])) ? 'checked' : '' }}>
                            <div class="cf-feat-check">
                                <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <span>{{ $feat }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- ══ RIGHT ══ --}}
            <div class="cf-right">

                {{-- Availability --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#10b981"></div>
                        Availability
                    </div>
                    <label class="cf-toggle">
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" value="1" {{ $car->is_available ? 'checked' : '' }}>
                        <div class="cf-toggle-track">
                            <div class="cf-toggle-thumb"></div>
                        </div>
                        <div class="cf-toggle-label">
                            <span class="cf-toggle-on">Available for booking</span>
                            <span class="cf-toggle-sub">Car appears in search results</span>
                        </div>
                    </label>
                </div>

                {{-- Current image --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#C89D66"></div>
                        Main Photo
                    </div>
                    @if($car->image)
                    <div class="cf-current-img">
                        <img src="{{ $car->image_url }}" alt="{{ $car->full_name }}" id="mainImgPreview">
                        <div class="cf-current-img-label">Current photo</div>
                    </div>
                    @endif
                    <div class="cf-dropzone {{ $car->image ? 'cf-dropzone--sm' : '' }}" id="mainDrop">
                        <input type="file" name="image" accept="image/*" id="mainImg">
                        <div class="cf-drop-content">
                            <div class="cf-drop-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            </div>
                            <p class="cf-drop-txt">{{ $car->image ? 'Replace photo' : 'Upload photo' }}</p>
                            <p class="cf-drop-sub">JPG, PNG · Max 5MB</p>
                        </div>
                    </div>
                </div>

                {{-- Gallery --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#52596e"></div>
                        Gallery
                    </div>
                    {{-- Current gallery --}}
                    @if(is_array($car->gallery) && count($car->gallery))
                    <div class="cf-gallery-current">
                        @foreach($car->gallery as $img)
                        <div class="cf-gal-thumb">
                            <img src="{{ $car->resolveImageUrl($img) }}" alt="">
                        </div>
                        @endforeach
                    </div>
                    <p class="cf-hint-txt" style="margin-top:8px">Add more images below:</p>
                    @endif
                    <div class="cf-dropzone cf-dropzone--sm cf-dropzone--gallery">
                        <input type="file" name="gallery[]" multiple accept="image/*" id="galleryImgs">
                        <div class="cf-drop-content">
                            <div class="cf-drop-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                            </div>
                            <p class="cf-drop-txt">Add photos</p>
                            <p class="cf-drop-sub">Multiple images</p>
                        </div>
                    </div>
                    <div class="cf-gallery-previews" id="galleryPreviews"></div>
                </div>

                {{-- Insurance --}}
                <div class="cf-section">
                    <div class="cf-section-head">
                        <div class="cf-section-dot" style="background:#f97316"></div>
                        Insurance Plans
                        <span class="cf-optional">optional</span>
                    </div>
                    <p class="cf-hint-txt">Select available plans for this car.</p>

                    @php
                        $carInsuranceIds = $car->insurances->pluck('id')->toArray();
                        $defaultInsurance = $car->insurances->where('pivot.is_default', true)->first();
                        $allInsurances = \App\Models\Insurance::active()->ordered()->get();
                    @endphp

                    @forelse($allInsurances as $ins)
                    <label class="cf-ins-card">
                        <input type="checkbox" name="insurances[]" value="{{ $ins->id }}"
                               {{ in_array($ins->id, old('insurances', $carInsuranceIds)) ? 'checked' : '' }}>
                        <div class="cf-ins-check">
                            <svg viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="cf-ins-body">
                            <div class="cf-ins-head">
                                <span class="cf-ins-name">{{ $ins->name }}</span>
                                <span class="cf-ins-type cf-ins-type--{{ $ins->type }}">{{ strtoupper($ins->type) }}</span>
                            </div>
                            <p class="cf-ins-desc">{{ Str::limit($ins->description, 80) }}</p>
                            <div class="cf-ins-meta">
                                <span>{{ number_format($ins->fixed_price, 0) }} MAD one-time</span>
                                <span>Max {{ number_format($ins->max_coverage, 0) }} MAD</span>
                            </div>
                        </div>
                        <label class="cf-ins-default" onclick="event.stopPropagation()">
                            <input type="radio" name="default_insurance" value="{{ $ins->id }}"
                                   {{ old('default_insurance', $defaultInsurance?->id) == $ins->id ? 'checked' : '' }}>
                            <span>Default</span>
                        </label>
                    </label>
                    @empty
                    <div class="cf-ins-empty">
                        <p>No insurance plans available</p>
                        <a href="{{ route('admin.insurances.create') }}" class="cf-ins-link">Create one →</a>
                    </div>
                    @endforelse
                </div>

                {{-- Submit --}}
                <button type="submit" class="cf-submit">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Update Car
                </button>

            </div>
        </div>
    </form>
</div>

<style>
.cf {
    --bg:   #07090f;
    --bg2:  #0c0f18;
    --bg3:  #111520;
    --bdr:  rgba(255,255,255,0.055);
    --txt:  #e8eaf0;
    --muted:#52596e;
    --hint: #2a3045;
    --gold: #C89D66;
    background: var(--bg);
    color: var(--txt);
    font-family: 'DM Sans', system-ui, sans-serif;
    padding: 1.75rem 2rem;
    min-height: 100%;
}

.cf-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap; }
.cf-breadcrumb { display:flex; align-items:center; gap:7px; font-size:0.65rem; font-weight:700; color:var(--muted); letter-spacing:0.12em; text-transform:uppercase; margin-bottom:5px; }
.cf-bc-link { color:var(--gold); text-decoration:none; display:flex; align-items:center; gap:5px; }
.cf-bc-link svg { width:11px; height:11px; }
.cf-title { font-size:clamp(1.4rem,3vw,2rem); font-weight:900; letter-spacing:-0.05em; color:var(--txt); line-height:1; margin-bottom:3px; }
.cf-sub { font-size:0.72rem; color:var(--muted); }
.cf-header-actions { display:flex; align-items:center; gap:8px; }

.cf-view-btn, .cf-back-btn {
    display:flex; align-items:center; gap:7px;
    padding:8px 14px; border-radius:8px;
    font-size:0.78rem; text-decoration:none; transition:all 0.15s;
}
.cf-view-btn { background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.2); color:#60a5fa; }
.cf-view-btn:hover { background:rgba(59,130,246,0.15); }
.cf-back-btn { background:var(--bg2); border:1px solid var(--bdr); color:var(--muted); }
.cf-back-btn:hover { border-color:var(--gold); color:var(--gold); }
.cf-view-btn svg, .cf-back-btn svg { width:14px; height:14px; }

.cf-errors { display:flex; align-items:flex-start; gap:12px; background:rgba(239,68,68,0.07); border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:1rem 1.1rem; margin-bottom:1.25rem; }
.cf-errors-icon { width:32px; height:32px; border-radius:8px; background:rgba(239,68,68,0.12); display:flex; align-items:center; justify-content:center; color:#f87171; flex-shrink:0; }
.cf-errors-icon svg { width:16px; height:16px; }
.cf-errors-title { font-size:0.8rem; font-weight:700; color:#fca5a5; margin-bottom:5px; }
.cf-errors-item { font-size:0.75rem; color:#f87171; line-height:1.6; }
.cf-errors-item::before { content:'· '; }

.cf-layout { display:grid; grid-template-columns:1fr 380px; gap:14px; align-items:start; }
@media(max-width:1100px){ .cf-layout{ grid-template-columns:1fr; } }
.cf-left, .cf-right { display:flex; flex-direction:column; gap:12px; }

.cf-section { background:var(--bg2); border:1px solid var(--bdr); border-radius:12px; padding:1.1rem; animation:cf-up 0.35s ease both; }
@keyframes cf-up { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }

.cf-section-head { display:flex; align-items:center; gap:8px; font-size:0.78rem; font-weight:700; color:var(--txt); margin-bottom:1rem; }
.cf-section-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.cf-optional { font-size:0.6rem; color:var(--muted); font-weight:400; margin-left:4px; }
.cf-req { color:#f87171; margin-left:1px; }
.cf-hint-txt { font-size:0.72rem; color:var(--muted); line-height:1.6; margin-bottom:10px; }

.cf-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.cf-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
@media(max-width:700px){ .cf-grid-2,.cf-grid-3{ grid-template-columns:1fr; } }

.cf-field { display:flex; flex-direction:column; gap:5px; }
.cf-field label { font-size:0.68rem; font-weight:600; color:var(--muted); letter-spacing:0.04em; }
.cf-field input,.cf-field select,.cf-field textarea {
    background:var(--bg3)!important; border:1px solid var(--bdr)!important;
    border-radius:8px!important; color:var(--txt)!important;
    font-size:0.85rem!important; padding:9px 12px!important;
    outline:none!important; font-family:inherit!important;
    -webkit-text-fill-color:var(--txt)!important;
    transition:border-color 0.15s,box-shadow 0.15s;
    width:100%; appearance:none; -webkit-appearance:none;
}
.cf-field select {
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2352596e' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E")!important;
    background-repeat:no-repeat!important; background-position:right 10px center!important;
    background-size:14px!important; padding-right:34px!important;
}
.cf-field select option { background:#111520; color:#e8eaf0; }
.cf-field textarea { resize:vertical; min-height:90px; line-height:1.6; }
.cf-field input:focus,.cf-field select:focus,.cf-field textarea:focus {
    border-color:var(--gold)!important; box-shadow:0 0 0 3px rgba(200,157,102,0.1)!important;
}
.cf-field input::placeholder,.cf-field textarea::placeholder { color:var(--hint)!important; }
.cf-field input:-webkit-autofill { -webkit-box-shadow:0 0 0 1000px #111520 inset!important; }

.cf-input-prefix { position:relative; }
.cf-input-prefix span { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:0.72rem; font-weight:700; color:var(--gold); pointer-events:none; }
.cf-input-prefix input { padding-left:44px!important; }

/* Features grid */
.cf-features-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:8px; }
@media(max-width:600px){ .cf-features-grid{ grid-template-columns:1fr; } }

.cf-feat-item { display:flex; align-items:center; gap:8px; cursor:pointer; padding:7px 10px; border-radius:7px; border:1px solid var(--bdr); background:var(--bg3); transition:border-color 0.15s,background 0.15s; }
.cf-feat-item:hover { border-color:rgba(200,157,102,0.2); }
.cf-feat-item:has(input:checked) { border-color:rgba(200,157,102,0.35); background:rgba(200,157,102,0.05); }
.cf-feat-item input[type=checkbox] { display:none; }
.cf-feat-check { width:16px; height:16px; border-radius:4px; border:1.5px solid var(--bdr); background:var(--bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:transparent; transition:all 0.15s; }
.cf-feat-item:has(input:checked) .cf-feat-check { background:var(--gold); border-color:var(--gold); color:#fff; }
.cf-feat-check svg { width:9px; height:9px; }
.cf-feat-item span { font-size:0.78rem; color:var(--muted); }
.cf-feat-item:has(input:checked) span { color:var(--txt); }

/* Toggle */
.cf-toggle { display:flex; align-items:center; gap:12px; cursor:pointer; }
.cf-toggle input[type=checkbox] { display:none; }
.cf-toggle-track { width:44px; height:24px; border-radius:12px; background:var(--hint); position:relative; flex-shrink:0; transition:background 0.2s; border:1px solid var(--bdr); }
.cf-toggle input:checked ~ .cf-toggle-track { background:var(--gold); }
.cf-toggle-thumb { position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; transition:transform 0.2s; }
.cf-toggle input:checked ~ .cf-toggle-track .cf-toggle-thumb { transform:translateX(20px); }
.cf-toggle-on { font-size:0.85rem; font-weight:600; color:var(--txt); display:block; }
.cf-toggle-sub { font-size:0.68rem; color:var(--muted); }

/* Current image */
.cf-current-img { position:relative; border-radius:9px; overflow:hidden; margin-bottom:8px; border:1px solid var(--bdr); }
.cf-current-img img { width:100%; height:160px; object-fit:cover; display:block; }
.cf-current-img-label { position:absolute; top:8px; left:8px; font-size:0.6rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:3px 9px; border-radius:100px; background:rgba(0,0,0,0.6); color:#fff; }

/* Dropzone */
.cf-dropzone { position:relative; border:1.5px dashed var(--bdr); border-radius:10px; overflow:hidden; transition:border-color 0.2s,background 0.2s; cursor:pointer; }
.cf-dropzone--sm .cf-drop-content { padding:1.2rem 1rem; }
.cf-dropzone:hover { border-color:rgba(200,157,102,0.4); background:rgba(200,157,102,0.03); }
.cf-dropzone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; z-index:2; width:100%; height:100%; }
.cf-drop-content { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1rem; gap:6px; pointer-events:none; }
.cf-drop-icon { width:36px; height:36px; border-radius:9px; background:rgba(200,157,102,0.08); border:1px solid rgba(200,157,102,0.15); display:flex; align-items:center; justify-content:center; color:var(--gold); margin-bottom:4px; }
.cf-drop-icon svg { width:18px; height:18px; }
.cf-drop-txt { font-size:0.8rem; font-weight:600; color:var(--txt); }
.cf-drop-sub { font-size:0.67rem; color:var(--muted); }

.cf-gallery-current { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px; }
.cf-gallery-previews { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.cf-gal-thumb { width:60px; height:46px; border-radius:6px; overflow:hidden; border:1px solid var(--bdr); }
.cf-gal-thumb img { width:100%; height:100%; object-fit:cover; }

/* Insurance */
.cf-ins-card { display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border-radius:9px; border:1px solid var(--bdr); background:var(--bg3); cursor:pointer; transition:border-color 0.15s,background 0.15s; margin-bottom:8px; }
.cf-ins-card:last-of-type { margin-bottom:0; }
.cf-ins-card:hover { border-color:rgba(200,157,102,0.25); }
.cf-ins-card input[type=checkbox] { display:none; }
.cf-ins-card:has(input:checked) { border-color:rgba(200,157,102,0.4); background:rgba(200,157,102,0.04); }
.cf-ins-check { width:18px; height:18px; border-radius:5px; border:1.5px solid var(--bdr); background:var(--bg); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; color:transparent; transition:all 0.15s; }
.cf-ins-card:has(input:checked) .cf-ins-check { background:var(--gold); border-color:var(--gold); color:#fff; }
.cf-ins-check svg { width:10px; height:10px; }
.cf-ins-body { flex:1; min-width:0; }
.cf-ins-head { display:flex; align-items:center; gap:7px; margin-bottom:3px; }
.cf-ins-name { font-size:0.82rem; font-weight:700; color:var(--txt); }
.cf-ins-type { font-size:0.58rem; font-weight:800; padding:1px 7px; border-radius:100px; letter-spacing:0.08em; }
.cf-ins-type--basic    { background:rgba(59,130,246,0.12); color:#60a5fa; }
.cf-ins-type--standard { background:rgba(245,158,11,0.12); color:#fbbf24; }
.cf-ins-type--premium  { background:rgba(16,185,129,0.12); color:#10b981; }
.cf-ins-desc { font-size:0.72rem; color:var(--muted); line-height:1.5; margin-bottom:5px; }
.cf-ins-meta { display:flex; gap:10px; font-size:0.65rem; color:var(--hint); flex-wrap:wrap; }
.cf-ins-default { display:flex; align-items:center; gap:5px; flex-shrink:0; font-size:0.65rem; color:var(--muted); cursor:pointer; white-space:nowrap; }
.cf-ins-empty { padding:1.5rem; text-align:center; }
.cf-ins-empty p { font-size:0.82rem; color:var(--muted); margin-bottom:8px; }
.cf-ins-link { color:var(--gold); font-size:0.78rem; text-decoration:none; font-weight:600; }

/* Submit */
.cf-submit { width:100%; padding:11px; background:linear-gradient(135deg,var(--gold),#B8935E); border:none; border-radius:10px; color:#fff; font-size:0.9rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(200,157,102,0.25); transition:opacity 0.2s,box-shadow 0.2s; font-family:inherit; }
.cf-submit svg { width:16px; height:16px; }
.cf-submit:hover { opacity:0.9; box-shadow:0 6px 22px rgba(200,157,102,0.4); }
.cf-submit:active { transform:scale(0.99); }
</style>

<script>
document.getElementById('mainImg').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const prev = document.getElementById('mainImgPreview');
        if (prev) prev.src = ev.target.result;
        else {
            const div = document.createElement('div');
            div.className = 'cf-current-img';
            div.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:160px;object-fit:cover;display:block">`;
            document.getElementById('mainDrop').before(div);
        }
    };
    reader.readAsDataURL(file);
});

document.getElementById('galleryImgs').addEventListener('change', function(e) {
    const container = document.getElementById('galleryPreviews');
    container.innerHTML = '';
    Array.from(e.target.files).slice(0,10).forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            const div = document.createElement('div');
            div.className = 'cf-gal-thumb';
            div.innerHTML = `<img src="${ev.target.result}">`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});
</script>

@endsection
