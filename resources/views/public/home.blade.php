@extends('layouts.public')

@section('title', 'Encore Reviews — Powered by TicketPal')

@section('content')
<header
  class="er-hero er-hero--image"
  style="
    background-image:
      linear-gradient(rgba(10,20,40,0.80), rgba(10,20,40,0.80)),
      url('{{ asset('assets/hero-show-bg.jpg') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  "
>
  <div class="er-container">
    <div class="er-logoLockup" style="--logo-scale: 1.15;">
  <img
    class="er-mark"
    src="{{ asset('assets/encore-icon.png') }}"
    alt="Encore Reviews"
  >
  <div class="er-wordmark">
    <div class="er-wordmark__title">Encore Reviews</div>
    <div class="er-wordmark__tag">Powered by TicketPal</div>
  </div>
</div>    <p class="er-hero__lead">
      The UK’s audience-review platform for live events — powered by verified ticket data from TicketPal.
    </p>

    <div class="er-hero__actions">
      <a class="er-btn" href="#organisers">For organisers</a>
      <a class="er-btn er-btn--ghost" href="#how-it-works">How it works</a>
    </div>
  </div>
</header>

<section class="er-section" id="organisers">
  <div class="er-container">
    <h2 class="er-h2">What TicketPal organisers get — free</h2>
    <div class="er-grid">
      <div class="er-card">
        <h3>Automatic review emails</h3>
        <p>Post-show review requests sent while the experience is still fresh.</p>
      </div>
      <div class="er-card">
        <h3>Encore rating badge</h3>
        <p>Your audience score displayed on listings and show pages.</p>
      </div>
      <div class="er-card">
        <h3>Marketing quotes</h3>
        <p>Export testimonials for posters, social, and programmes.</p>
      </div>
      <div class="er-card">
        <h3>Performance insights</h3>
        <p>Track trends and benchmark against similar shows.</p>
      </div>
    </div>
  </div>
</section>

<section class="er-section er-section--alt" id="how-it-works">
  <div class="er-container">
    <h2 class="er-h2">How it works</h2>
    <div class="er-grid">
      <div class="er-card"><h3>1. Audience attends</h3><p>Tickets are scanned at the venue.</p></div>
      <div class="er-card"><h3>2. Encore email</h3><p>A review link is sent automatically after the show.</p></div>
      <div class="er-card"><h3>3. Ratings published</h3><p>Scores appear on Encore and can be surfaced in ticketing flows.</p></div>
      <div class="er-card"><h3>4. More tickets sold</h3><p>Future buyers book with confidence.</p></div>
    </div>
  </div>
</section>

<footer class="er-footer" id="footer">
  <div class="er-container">
    <p><strong>Encore Reviews</strong> — Powered by TicketPal</p>
    <p class="er-footer__small">© {{ date('Y') }} TicketPal Ltd. All rights reserved.</p>
  </div>
</footer>
@endsection