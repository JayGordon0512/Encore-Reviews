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
          @include('public.partials.show-card', ['show' => $show])
        @endforeach
      </div>
    @endif
  </div>
</section>
@endsection
