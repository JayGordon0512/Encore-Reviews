@extends('layouts.public')

@section('title', 'Customer login — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container er-authPage">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Customer admin</p>
      <h1 class="er-h2">Log in to Encore Reviews</h1>
      <p class="er-sectionIntro">Access your show review dashboard and moderation queue.</p>
    </div>

    <form class="er-card er-form" method="POST" action="{{ route('login') }}">
      @csrf

      <div class="er-field">
        <label for="email">Email address</label>
        <input class="er-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        @error('email')
          <p class="er-fieldError">{{ $message }}</p>
        @enderror
      </div>

      <div class="er-field">
        <label for="password">Password</label>
        <input class="er-input" type="password" id="password" name="password" autocomplete="current-password" required>
        @error('password')
          <p class="er-fieldError">{{ $message }}</p>
        @enderror
      </div>

      <label class="er-choice"><input type="checkbox" name="remember" value="1"> Remember me</label>

      <button class="er-btn" type="submit">Log in</button>
    </form>
  </div>
</section>
@endsection
