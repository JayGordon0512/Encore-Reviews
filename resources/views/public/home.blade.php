@extends('layouts.public')

@section('title', 'Featured shows — Encore Reviews')

@section('content')
<header
  class="er-hero er-hero--image"
  style="
    background-image:
      url('{{ asset('assets/hero-show-bg.jpg') }}');
  "
>
  <div class="er-container">
    <div class="er-hero__content">
      <h1 class="er-hero__title">Reviews you can trust</h1>
      <p class="er-hero__lead">
        Discover live events through verified audience reviews powered by TicketPal.
      </p>

      <div class="er-hero__actions">
        <a class="er-btn" href="#featured-shows">Featured shows</a>
        <a class="er-btn er-btn--ghost" href="{{ route('organisers') }}">For organisers</a>
      </div>
    </div>
  </div>
</header>

<section class="er-section" id="featured-shows">
  <div class="er-container">
    <div class="er-sectionHeader">
      <h1 class="er-h2">Featured shows</h1>
      <p class="er-sectionIntro">Discover featured live events with audience-reviewed ratings powered by verified TicketPal ticket data.</p>
    </div>

    @if($shows->isEmpty())
      <div class="er-card">
        <p>No featured shows are available yet. Check back later.</p>
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
