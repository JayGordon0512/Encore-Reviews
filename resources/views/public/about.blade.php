@extends('layouts.public')

@section('title', 'About Encore Reviews — Powered by TicketPal')

@section('content')
<section class="er-section" id="organisers">
  <div class="er-container">
    <div class="er-sectionHeader">
      <h2 class="er-h2">What TicketPal organisers get — free</h2>
    </div>
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
    <div class="er-sectionHeader">
      <h2 class="er-h2">How it works</h2>
    </div>
    <div class="er-grid">
      <div class="er-card"><h3>1. Audience attends</h3><p>Tickets are scanned at the venue.</p></div>
      <div class="er-card"><h3>2. Encore email</h3><p>A review link is sent automatically after the show.</p></div>
      <div class="er-card"><h3>3. Ratings published</h3><p>Scores appear on Encore and can be surfaced in ticketing flows.</p></div>
      <div class="er-card"><h3>4. More tickets sold</h3><p>Future buyers book with confidence.</p></div>
    </div>
  </div>
</section>

@endsection
