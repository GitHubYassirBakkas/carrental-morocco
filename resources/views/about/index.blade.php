@extends('layouts.app')

@section('title', __('messages.about'))

@push('styles')


<style>

.footer {
background: #0b0d12; 
color: #ccc;
padding-top: 60px;
} 
.footer-top{
max-width: 1200px; 
margin: auto;
background: #11131a; 
border-radius: 18px;
padding: 35px; 
display: grid; 
grid-template-columns: repeat(3, 1fr); 
gap: 30px; 
}
.footer-box 
{ 
 display: flex;
 align-items: center; 
 gap: 15px; 
}
.icon-circle 
{
width: 55px; 
height: 55px;
background: #f5b34d;
border-radius: 50%;
display: flex; 
align-items: center; 
justify-content: center;
color: #000; 
font-size: 20px; 
 } 
.footer-box h4 
{ 
color: #fff; 
margin-bottom: 5px;
}
/* MAIN FOOTER */ 
.footer-main {
max-width: 1200px;
margin: 70px auto 40px;
display: grid; grid-template-columns: 1.3fr 1fr 1fr;
gap: 60px; 
} 
.footer-logo {
 color: #f5b34d; 
font-size: 28px;
margin-bottom: 15px; 
} 
.footer-col h3
{
color: #fff; 
margin-bottom: 20px; 
}
.footer-col ul 
{ 
list-style: none;
 } 
  .footer-col ul li 
 { 
margin-bottom: 12px;
}
.footer-col ul li a 
{ 
 color: #aaa; 
 text-decoration: none;
 transition: 0.3s; 
}
.footer-col ul li a:hover 
{
 color: #f5b34d; 
 } 
/* SOCIAL */
.socials
{
 display: flex; 
 gap: 12px; 
 margin-top: 20px;
}
.socials a 
{
 width: 42px;
 height: 42px; 
border-radius: 50%;
border: 1px solid #f5b34d;
display: flex; 
align-items: center; 
justify-content: center;
color: #f5b34d;
transition: 0.3s;
}
.socials a:hover 
{ 
background: #f5b34d;
color: #000; 
}
 /* SUBSCRIBE */ 
.subscribe-form 
{ 
position: relative;
margin-top: 20px; 
 } 
.subscribe-form input 
{
width: 100%;
padding: 14px 55px 14px 20px;
border-radius: 40px;
border: 1px solid #333; 
background: transparent;
color: #fff; 
} 
.subscribe-form button 
{ 
position: absolute; 
right: 5px;
top: 50%;
transform: translateY(-50%);
width: 42px;
height: 42px; 
border-radius: 50%;
background: #f5b34d;
border: none;
cursor: pointer; 
} 
.footer-bottom 
{
text-align: center; 
padding: 20px 0; 
border-top: 1px solid rgba(255,255,255,0.05);
font-size: 14px; 
color: #777; 
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --gold:    #C89D66;
  --gold-d:  #a07840;
  --gold-l:  #e8c48a;
  --bg:      #080808;
  --bg-2:    #0f0f0f;
  --bg-3:    #141414;
  --border:  rgba(255,255,255,0.07);
  --text:    #f0ece4;
  --muted:   #8a8070;
  --hint:    #3a3530;
  --serif:   'Cormorant Garamond', Georgia, serif;
  --sans:    'DM Sans', system-ui, sans-serif;
}

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--sans);
  font-size: 16px;
  line-height: 1.7;
  overflow-x: hidden;
}

/* ── HERO ── */
.hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
  padding: 8rem 2rem 6rem;
}

.hero-grain {
  position: absolute; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0;
}

.hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  pointer-events: none;
}
.hero-orb--1 { width: 600px; height: 600px; background: rgba(200,157,102,0.07); top: -200px; right: -100px; }
.hero-orb--2 { width: 400px; height: 400px; background: rgba(200,157,102,0.04); bottom: -100px; left: -100px; }

.hero-line {
  position: absolute;
  left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(200,157,102,0.3), transparent);
}
.hero-line--top    { top: 120px; }
.hero-line--bottom { bottom: 80px; }

.hero-inner {
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
  position: relative; z-index: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6rem;
  align-items: center;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-family: var(--sans);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 1.5rem;
}

.hero-eyebrow::before {
  content: '';
  display: block;
  width: 30px; height: 1px;
  background: var(--gold);
}

.hero-title {
  font-family: var(--serif);
  font-size: clamp(3rem, 6vw, 5.5rem);
  font-weight: 300;
  line-height: 1.1;
  letter-spacing: -0.02em;
  color: var(--text);
  margin-bottom: 2rem;
}

.hero-title em {
  font-style: italic;
  color: var(--gold);
}

.hero-desc {
  font-size: 1.05rem;
  color: var(--muted);
  line-height: 1.8;
  max-width: 460px;
  margin-bottom: 3rem;
}

.hero-cta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: transparent;
  border: 1px solid rgba(200,157,102,0.4);
  color: var(--gold);
  padding: 14px 28px;
  border-radius: 3px;
  font-size: 0.82rem;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  text-decoration: none;
  transition: background 0.3s, border-color 0.3s;
}

.hero-cta:hover {
  background: rgba(200,157,102,0.1);
  border-color: var(--gold);
}

.hero-cta svg { width: 16px; height: 16px; transition: transform 0.3s; }
.hero-cta:hover svg { transform: translateX(4px); }

/* Hero right — stat panel */
.hero-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
}

.hstat {
  background: var(--bg-2);
  padding: 2.5rem 2rem;
  position: relative;
  overflow: hidden;
  transition: background 0.3s;
}

.hstat:hover { background: var(--bg-3); }

.hstat::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(200,157,102,0.5), transparent);
  opacity: 0;
  transition: opacity 0.3s;
}

.hstat:hover::before { opacity: 1; }

.hstat-num {
  font-family: var(--serif);
  font-size: 3.5rem;
  font-weight: 300;
  color: var(--gold);
  line-height: 1;
  margin-bottom: 0.5rem;
  display: block;
}

.hstat-label {
  font-size: 0.75rem;
  color: var(--muted);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

/* ── SECTION BASE ── */
section {
  padding: 8rem 2rem;
  position: relative;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-tag {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-size: 0.68rem;
  font-weight: 500;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--gold);
  margin-bottom: 1.25rem;
}

.section-tag::before {
  content: '';
  display: block;
  width: 24px; height: 1px;
  background: var(--gold);
}

.section-title {
  font-family: var(--serif);
  font-size: clamp(2rem, 4vw, 3.5rem);
  font-weight: 300;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: var(--text);
}

.section-title em { font-style: italic; color: var(--gold); }

/* ── STORY ── */
.story {
  background: var(--bg-2);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}

.story-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 8rem;
  align-items: center;
}

.story-visual {
  position: relative;
}

.story-img-frame {
  aspect-ratio: 4/5;
  background: var(--bg-3);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
}

.story-img-frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(20%) contrast(1.1);
  transition: transform 6s ease;
}

.story-img-frame:hover img { transform: scale(1.05); }

.story-img-badge {
  position: absolute;
  bottom: -1px; right: -1px;
  background: var(--bg);
  border: 1px solid var(--border);
  padding: 1.5rem 2rem;
}

.story-img-badge strong {
  display: block;
  font-family: var(--serif);
  font-size: 2.5rem;
  font-weight: 300;
  color: var(--gold);
  line-height: 1;
}

.story-img-badge span {
  font-size: 0.7rem;
  color: var(--muted);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.story-content { padding-top: 1rem; }

.story-lead {
  font-family: var(--serif);
  font-size: 1.5rem;
  font-weight: 300;
  color: var(--text);
  line-height: 1.6;
  margin: 1.5rem 0 1.5rem;
  padding-left: 1.5rem;
  border-left: 1px solid var(--gold);
}

.story-body {
  font-size: 0.95rem;
  color: var(--muted);
  line-height: 1.9;
}

/* ── VALUES ── */
.values-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
  margin-top: 5rem;
}

.value-card {
  background: var(--bg);
  padding: 3rem 2.5rem;
  position: relative;
  overflow: hidden;
  transition: background 0.3s;
}

.value-card:hover { background: var(--bg-2); }

.value-num {
  font-family: var(--serif);
  font-size: 5rem;
  font-weight: 300;
  color: var(--hint);
  line-height: 1;
  position: absolute;
  top: 1.5rem; right: 2rem;
  transition: color 0.3s;
}

.value-card:hover .value-num { color: rgba(200,157,102,0.15); }

.value-icon {
  width: 48px; height: 48px;
  border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 2rem;
  transition: border-color 0.3s;
}

.value-card:hover .value-icon { border-color: rgba(200,157,102,0.4); }

.value-icon svg { width: 22px; height: 22px; color: var(--gold); }

.value-title {
  font-family: var(--serif);
  font-size: 1.5rem;
  font-weight: 400;
  color: var(--text);
  margin-bottom: 1rem;
}

.value-desc {
  font-size: 0.88rem;
  color: var(--muted);
  line-height: 1.8;
}

/* ── TEAM ── */
.team { background: var(--bg-2); border-top: 1px solid var(--border); }

.team-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 5rem;
  flex-wrap: wrap;
  gap: 2rem;
}

.team-desc {
  max-width: 420px;
  font-size: 0.95rem;
  color: var(--muted);
  line-height: 1.8;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
}

.team-card {
  background: var(--bg-2);
  padding: 2.5rem 2rem;
  transition: background 0.3s;
  position: relative;
  overflow: hidden;
}

.team-card:hover { background: var(--bg-3); }

.team-avatar {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--bg-3), var(--hint));
  border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.5rem;
  font-family: var(--serif);
  font-size: 1.6rem;
  font-weight: 300;
  color: var(--gold);
}

.team-name {
  font-family: var(--serif);
  font-size: 1.2rem;
  font-weight: 400;
  color: var(--text);
  margin-bottom: 4px;
}

.team-role {
  font-size: 0.72rem;
  color: var(--gold);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 1rem;
}

.team-bio {
  font-size: 0.82rem;
  color: var(--muted);
  line-height: 1.7;
}

/* ── DESTINATIONS ── */
.destinations-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  background: var(--border);
  border: 1px solid var(--border);
  margin-top: 5rem;
}

.dest-card {
  background: var(--bg);
  padding: 2.5rem;
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  transition: background 0.3s;
  cursor: default;
}

.dest-card:hover { background: var(--bg-2); }

.dest-num {
  font-family: var(--serif);
  font-size: 2.5rem;
  font-weight: 300;
  color: var(--hint);
  line-height: 1;
  flex-shrink: 0;
  transition: color 0.3s;
}

.dest-card:hover .dest-num { color: var(--gold); }

.dest-city {
  font-family: var(--serif);
  font-size: 1.3rem;
  font-weight: 400;
  color: var(--text);
  margin-bottom: 4px;
}

.dest-region {
  font-size: 0.72rem;
  color: var(--muted);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

/* ── CTA BANNER ── */
.cta-banner {
  background: var(--bg-2);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  padding: 8rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.cta-banner::before {
  content: '';
  position: absolute;
  top: 0; left: 50%; transform: translateX(-50%);
  width: 1px; height: 4rem;
  background: linear-gradient(to bottom, transparent, var(--gold));
}

.cta-banner::after {
  content: '';
  position: absolute;
  bottom: 0; left: 50%; transform: translateX(-50%);
  width: 1px; height: 4rem;
  background: linear-gradient(to top, transparent, var(--gold));
}

.cta-orb {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(200,157,102,0.06) 0%, transparent 70%);
  pointer-events: none;
}

.cta-title {
  font-family: var(--serif);
  font-size: clamp(2.5rem, 5vw, 4.5rem);
  font-weight: 300;
  color: var(--text);
  margin-bottom: 1.5rem;
  line-height: 1.1;
  position: relative;
}

.cta-title em { font-style: italic; color: var(--gold); }

.cta-sub {
  font-size: 1rem;
  color: var(--muted);
  max-width: 480px;
  margin: 0 auto 3rem;
  line-height: 1.8;
  position: relative;
}

.cta-buttons {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
  position: relative;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 32px;
  font-size: 0.82rem;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  text-decoration: none;
  border-radius: 3px;
  transition: all 0.3s;
}

.cta-btn--primary {
  background: var(--gold);
  color: #0a0a0a;
  border: 1px solid var(--gold);
}

.cta-btn--primary:hover {
  background: var(--gold-l);
  border-color: var(--gold-l);
}

.cta-btn--outline {
  background: transparent;
  color: var(--gold);
  border: 1px solid rgba(200,157,102,0.4);
}

.cta-btn--outline:hover {
  background: rgba(200,157,102,0.1);
  border-color: var(--gold);
}

/* ── SCROLL ANIMATIONS ── */
.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }
.reveal-delay-4 { transition-delay: 0.4s; }

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
  .hero-inner    { grid-template-columns: 1fr; gap: 4rem; }
  .hero-stats    { grid-template-columns: repeat(4, 1fr); }
  .story-grid    { grid-template-columns: 1fr; gap: 4rem; }
  .values-grid   { grid-template-columns: 1fr; }
  .team-grid     { grid-template-columns: repeat(2, 1fr); }
  .destinations-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  section { padding: 5rem 1.5rem; }
  .hero   { padding: 6rem 1.5rem 4rem; }
  .hero-stats { grid-template-columns: repeat(2, 1fr); }
  .team-grid  { grid-template-columns: 1fr; }
  .destinations-grid { grid-template-columns: 1fr; }
  .team-header { flex-direction: column; align-items: flex-start; }
}

</style>
@endpush

@section('content')

<!-- ══════════════════════════════
     HERO
══════════════════════════════ -->
<section class="hero">
  <div class="hero-grain"></div>
  <div class="hero-orb hero-orb--1"></div>
  <div class="hero-orb hero-orb--2"></div>
  <div class="hero-line hero-line--top"></div>
  <div class="hero-line hero-line--bottom"></div>

  <div class="hero-inner">
    <div class="hero-text">
      <div class="hero-eyebrow reveal">Est. 2018 — Casablanca</div>
      <h1 class="hero-title reveal reveal-delay-1">
        Driving Morocco's<br>
        <em>finest roads</em><br>
        since 2018
      </h1>
      <p class="hero-desc reveal reveal-delay-2">
        We believe that every journey deserves a vehicle worthy of it.
        From the Atlas Mountains to the Atlantic coast — we put you
        behind the wheel of Morocco's most premium fleet.
      </p>
      <a href="#story" class="hero-cta reveal reveal-delay-3">
        Our Story
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>

    <div class="hero-stats reveal reveal-delay-2">
      <div class="hstat">
        <span class="hstat-num">500<sup style="font-size:1.5rem">+</sup></span>
        <span class="hstat-label">Premium vehicles</span>
      </div>
      <div class="hstat">
        <span class="hstat-num">12</span>
        <span class="hstat-label">Cities covered</span>
      </div>
      <div class="hstat">
        <span class="hstat-num">10K<sup style="font-size:1.5rem">+</sup></span>
        <span class="hstat-label">Happy clients</span>
      </div>
      <div class="hstat">
        <span class="hstat-num">24<span style="font-size:1.5rem">/7</span></span>
        <span class="hstat-label">Support</span>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════
     STORY
══════════════════════════════ -->
<section class="story" id="story">
  <div class="container">
    <div class="story-grid">

      <div class="story-visual reveal">
        <div class="story-img-frame">
          <img src="https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&q=80" alt="Premium car on Moroccan road">
          <div class="story-img-badge">
            <strong>6+</strong>
            <span>Years of excellence</span>
          </div>
        </div>
      </div>

      <div class="story-content">
        <div class="section-tag reveal">Our story</div>
        <h2 class="section-title reveal reveal-delay-1">
          Born from a passion<br>for <em>the open road</em>
        </h2>
        <blockquote class="story-lead reveal reveal-delay-2">
          "Morocco deserved a car rental service as exceptional as its landscapes."
        </blockquote>
        <p class="story-body reveal reveal-delay-3">
          Founded in Casablanca in 2018, CarRental Morocco started with a simple belief: 
          travelers deserve more than a generic car and a handshake. 
          We built our fleet from scratch — hand-selecting each vehicle for comfort, 
          reliability, and style.
        </p>
        <br>
        <p class="story-body reveal reveal-delay-3">
          Today we serve thousands of clients across 12 Moroccan cities, 
          from weekend explorers to international business travelers. 
          Every booking comes with our promise: the right car, the right price, 
          and someone ready to help you 24/7.
        </p>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════
     VALUES
══════════════════════════════ -->
<section>
  <div class="container">
    <div style="max-width:600px">
      <div class="section-tag reveal">What drives us</div>
      <h2 class="section-title reveal reveal-delay-1">
        Three principles.<br><em>One commitment.</em>
      </h2>
    </div>

    <div class="values-grid">

      <div class="value-card reveal">
        <span class="value-num">01</span>
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h3 class="value-title">Uncompromising quality</h3>
        <p class="value-desc">Every vehicle in our fleet is inspected, cleaned, and fully serviced before each rental. We set the standard, then exceed it.</p>
      </div>

      <div class="value-card reveal reveal-delay-1">
        <span class="value-num">02</span>
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="value-title">Effortless simplicity</h3>
        <p class="value-desc">Book in under 2 minutes. No hidden fees, no confusing contracts. Just a car, a destination, and the open road ahead of you.</p>
      </div>

      <div class="value-card reveal reveal-delay-2">
        <span class="value-num">03</span>
        <div class="value-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
        <h3 class="value-title">People-first service</h3>
        <p class="value-desc">Our team lives and breathes Morocco. Whatever you need — a route recommendation or roadside help — we are always a call away.</p>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════
     TEAM
══════════════════════════════ -->
<section class="team">
  <div class="container">
    <div class="team-header">
      <div>
        <div class="section-tag reveal">The team</div>
        <h2 class="section-title reveal reveal-delay-1">
          The people<br>behind <em>every trip</em>
        </h2>
      </div>
      <p class="team-desc reveal reveal-delay-2">
        A small, dedicated team with deep roots in Morocco and
        a shared obsession with delivering exceptional experiences.
      </p>
    </div>

    <div class="team-grid">
      <div class="team-card reveal">
        <div class="team-avatar">Y</div>
        <div class="team-name">Youssef Amrani</div>
        <div class="team-role">Founder & CEO</div>
        <p class="team-bio">15 years in luxury hospitality across Morocco. Built CarRental to bring that same standard to the road.</p>
      </div>
      <div class="team-card reveal reveal-delay-1">
        <div class="team-avatar">S</div>
        <div class="team-name">Sara El Fassi</div>
        <div class="team-role">Head of Operations</div>
        <p class="team-bio">Keeps the fleet spotless and every booking seamless. If something needs fixing, Sara already knows about it.</p>
      </div>
      <div class="team-card reveal reveal-delay-2">
        <div class="team-avatar">K</div>
        <div class="team-name">Karim Benali</div>
        <div class="team-role">Fleet Manager</div>
        <p class="team-bio">Automotive engineer by training. Every car in our fleet passes his personal inspection before it hits the road.</p>
      </div>
      <div class="team-card reveal reveal-delay-3">
        <div class="team-avatar">L</div>
        <div class="team-name">Leila Rachidi</div>
        <div class="team-role">Customer Experience</div>
        <p class="team-bio">Your first and last point of contact. Leila makes sure every client leaves happier than when they arrived.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════
     DESTINATIONS
══════════════════════════════ -->
<section>
  <div class="container">
    <div style="max-width:600px">
      <div class="section-tag reveal">Where we operate</div>
      <h2 class="section-title reveal reveal-delay-1">
        From the coast<br>to the <em>desert</em>
      </h2>
    </div>

    <div class="destinations-grid reveal reveal-delay-2">
      <div class="dest-card"><span class="dest-num">01</span><div><div class="dest-city">Meknes</div><div class="dest-region">Atlantic coast — Hub</div></div></div>
      <div class="dest-card"><span class="dest-num">02</span><div><div class="dest-city">Casablanca</div><div class="dest-region">Imperial city</div></div></div>
      <div class="dest-card"><span class="dest-num">03</span><div><div class="dest-city">Rabat</div><div class="dest-region">Capital region</div></div></div>
      <div class="dest-card"><span class="dest-num">04</span><div><div class="dest-city">Fès</div><div class="dest-region">Northern medina</div></div></div>
      <div class="dest-card"><span class="dest-num">05</span><div><div class="dest-city">Agadir</div><div class="dest-region">Southern coast</div></div></div>
      <div class="dest-card"><span class="dest-num">06</span><div><div class="dest-city">Marrakech</div><div class="dest-region">Gateway to Europe</div></div></div>
      <div class="dest-card"><span class="dest-num">07</span><div><div class="dest-city">Tangier</div><div class="dest-region">Wind city</div></div></div>
      <div class="dest-card"><span class="dest-num">08</span><div><div class="dest-city">Ouarzazate</div><div class="dest-region">Gateway to Sahara</div></div></div>
      <div class="dest-card"><span class="dest-num">09</span><div><div class="dest-city">Chefchaouen</div><div class="dest-region">The blue city</div></div></div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════
     CTA
══════════════════════════════ -->
<section class="cta-banner">
  <div class="cta-orb"></div>
  <div class="cta-title reveal">
    Ready to explore<br><em>Morocco?</em>
  </div>
  <p class="cta-sub reveal reveal-delay-1">
    Browse our fleet, pick your car, and hit the road.
    Your next adventure is one booking away.
  </p>
  <div class="cta-buttons reveal reveal-delay-2">
    <a href="/cars" class="cta-btn cta-btn--primary">
      Browse fleet
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
    </a>
    <a href="/contact" class="cta-btn cta-btn--outline">Contact us</a>
  </div>
</section>

<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      observer.unobserve(e.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Trigger hero elements immediately
setTimeout(() => {
  document.querySelectorAll('.hero .reveal').forEach(el => el.classList.add('visible'));
}, 100);
</script>

@endsection