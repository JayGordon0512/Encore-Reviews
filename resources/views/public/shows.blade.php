@extends('layouts.public')

@section('title', 'Browse shows — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container">
    <h1 class="er-h2">Shows on Encore</h1>
    <p>Browse live events with audience-reviewed ratings powered by verified TicketPal ticket data.</p>

    @if($shows->isEmpty())
      <div class="er-card" style="margin-top:24px;">
        <p>No shows are available yet. Check back later.</p>
      </div>
    @else
      <div class="er-grid" style="margin-top:24px;">
        @foreach($shows as $show)
          <article class="er-card">
            <h3>{{ $show->title }}</h3>
            <p>{{ $show->summary ?? (isset($show->description) ? substr($show->description, 0, 120) . (strlen($show->description) > 120 ? '…' : '') : 'No summary available.') }}</p>
            <p style="margin-top: 12px;"><strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $show->status)) }}</p>
            <a class="er-btn" href="{{ route('shows.show', $show) }}">View show</a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
