@extends('layouts.public')

@section('title', 'Browse shows — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container">
    <div class="er-sectionHeader">
      <h1 class="er-h2">Shows on Encore</h1>
      <p class="er-sectionIntro">Browse live events with audience-reviewed ratings powered by verified TicketPal ticket data.</p>
    </div>

    @if($shows->isEmpty())
      <div class="er-card">
        <p>No shows are available yet. Check back later.</p>
      </div>
    @else
      <div class="er-grid">
        @foreach($shows as $show)
          <article class="er-card">
            <h3>{{ $show->title }}</h3>
            <p>{{ $show->summary ?? (isset($show->description) ? substr($show->description, 0, 120) . (strlen($show->description) > 120 ? '…' : '') : 'No summary available.') }}</p>
            <p class="er-card__meta"><strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $show->status)) }}</p>
            <a class="er-btn" href="{{ route('shows.show', $show) }}">View show</a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
