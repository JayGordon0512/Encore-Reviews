@extends('layouts.public')

@section('title', 'Review invitation unavailable — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Verified audience review</p>
      <h1 class="er-h2">Review invitation unavailable</h1>
      <p class="er-sectionIntro">This review link is missing, invalid, expired, or has already been used.</p>
    </div>

    <div class="er-card">
      <p>Reviews can only be submitted using the personal link in an Encore review invitation email.</p>
      <a class="er-btn" href="{{ route('shows.index') }}">Browse shows</a>
    </div>
  </div>
</section>
@endsection
