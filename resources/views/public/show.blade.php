@extends('layouts.public')

@section('title', $show->title.' — Encore Reviews')

@section('content')
<header class="er-hero er-hero--image" style="background-image: linear-gradient(rgba(10,20,40,0.80), rgba(10,20,40,0.80)), url('{{ asset($show->primary_image_path ?: 'assets/hero-show-bg.jpg') }}');">
  <div class="er-container">
    <div class="er-logoLockup" style="--logo-scale: 0.85;">
      <img class="er-mark" src="{{ asset('assets/encore-icon.png') }}" alt="Encore Reviews">
      <div class="er-wordmark">
        <div class="er-wordmark__title">{{ $show->title }}</div>
        <div class="er-wordmark__tag">Encore score page</div>
      </div>
    </div>

    <p class="er-hero__lead">{{ $show->summary ?? 'Audience reviews for this show are powered by verified TicketPal ticket data.' }}</p>
    <div class="er-hero__actions">
      @if($show->ticket_url)
        <a class="er-btn" href="{{ $show->ticket_url }}" target="_blank">Book tickets</a>
      @endif
      <a class="er-btn er-btn--ghost" href="#reviews">Read reviews</a>
    </div>
  </div>
</header>

<section class="er-section" id="details">
  <div class="er-container">
    <div class="er-grid">
      <div class="er-card">
        <h2 class="er-h2">Show overview</h2>
        <p>{{ $show->description ?? 'No description is available for this show yet.' }}</p>
        <dl style="margin-top: 18px; display:grid; gap: 12px;">
          <div><strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $show->status)) }}</div>
          <div><strong>Genre:</strong> {{ $show->genre ?? 'Not specified' }}</div>
          <div><strong>Ticket source:</strong> {{ $show->ticket_url_source ?? 'TicketPal' }}</div>
        </dl>
      </div>

      <div class="er-card">
        <h2 class="er-h2">Encore score</h2>
        <p style="margin:0; font-size: 2.75rem; font-weight: 800; color: var(--midnight);">{{ $reviewCount ? number_format($averageRating, 1) : '—' }}/5</p>
        <p style="margin: 8px 0 0;">Based on {{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }}.</p>
        <p style="margin: 18px 0 0;">Recommend rate: {{ $reviewCount ? round(($recommendCount / $reviewCount) * 100) : 0 }}%</p>
      </div>
    </div>
  </div>
</section>

<section class="er-section er-section--alt" id="reviews">
  <div class="er-container">
    <h2 class="er-h2">Audience reviews</h2>

    @if($reviews->isEmpty())
      <div class="er-card">
        <p>No reviews have been submitted for this show yet.</p>
      </div>
    @else
      <div class="er-grid">
        @foreach($reviews as $review)
          <div class="er-card">
            <h3>{{ $review->reviewer?->display_name ?? 'Anonymous' }}</h3>
            <p style="margin: 6px 0 10px; font-size: 0.95rem; color: var(--slate);">Rated {{ $review->rating }}/5 · {{ $review->submitted_at?->format('j M Y') ?? 'Unknown date' }}</p>
            <p>{{ $review->content ?? 'No comment provided.' }}</p>
            @if($review->tags)
              <p style="margin-top: 12px;">Tags: {{ implode(', ', $review->tags) }}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
