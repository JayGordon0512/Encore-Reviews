@extends('layouts.public')

@section('title', $show->title.' — Encore Reviews')

@section('content')
<header class="er-hero er-hero--image" style="background-image: url('{{ asset($show->primary_image_path ?: 'assets/hero-show-bg.jpg') }}');">
  <div class="er-container">
    <div class="er-hero__content">
      <p class="er-hero__eyebrow">Encore score page</p>
      <h1 class="er-hero__title">{{ $show->title }}</h1>

      <p class="er-hero__lead">{{ $show->summary ?? 'Audience reviews for this show are powered by verified TicketPal ticket data.' }}</p>
      <div class="er-hero__actions">
        @if($show->ticket_url)
          <a class="er-btn" href="{{ $show->ticket_url }}" target="_blank">Book tickets</a>
        @endif
        <a class="er-btn er-btn--ghost" href="#reviews">Read reviews</a>
      </div>
    </div>
  </div>
</header>

<section class="er-section" id="details">
  <div class="er-container">
    <div class="er-grid">
      <div class="er-card">
        <h2 class="er-h2">Show overview</h2>
        <p>{{ $show->description ?? 'No description is available for this show yet.' }}</p>
        <dl class="er-showMeta">
          <div><strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $show->status)) }}</div>
          <div><strong>Genre:</strong> {{ $show->genre ?? 'Not specified' }}</div>
          <div><strong>Ticket source:</strong> {{ $show->ticket_url_source ?? 'TicketPal' }}</div>
        </dl>
      </div>

      <div class="er-card">
        <h2 class="er-h2">Encore score</h2>
        <p class="er-score">{{ $reviewCount ? number_format($averageRating, 1) : '—' }}/5</p>
        <p class="er-card__meta">Based on {{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }}.</p>
        <p class="er-card__meta">Recommend rate: {{ $reviewCount ? round(($recommendCount / $reviewCount) * 100) : 0 }}%</p>
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
            <p class="er-reviewMeta">Rated {{ $review->rating }}/5 · {{ $review->submitted_at?->format('j M Y') ?? 'Unknown date' }}</p>
            <p>{{ $review->content ?? 'No comment provided.' }}</p>
            @if($review->tags)
              <p class="er-tags">Tags: {{ implode(', ', $review->tags) }}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
