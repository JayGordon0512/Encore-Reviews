@extends('layouts.public')

@section('title', 'Create an organiser account — Encore Reviews')
@section('meta_description', 'Create an Encore Reviews organiser account or continue securely with your existing TicketPal organiser account.')

@section('content')
<section class="er-section er-onboardingPage">
  <div class="er-container">
    <div class="er-sectionHeader er-onboardingHeader">
      <p class="er-hero__eyebrow">Organiser access</p>
      <h1 class="er-h2">Create your organiser account</h1>
      <p class="er-sectionIntro">Manage your shows, moderate audience reviews and build a trusted reputation on Encore.</p>
    </div>

    @if(session('status'))
      <div class="er-notice er-notice--success" role="status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="er-notice er-notice--error" role="alert">Please correct the highlighted fields.</div>
    @endif

    <div class="er-onboardingGrid">
      <div class="er-card er-ticketPalAccess">
        <p class="er-hero__eyebrow">Already with TicketPal?</p>
        <h2>Already a TicketPal organiser?</h2>
        <p>Use your TicketPal details to log in. TicketPal remains responsible for authenticating your account, so your password is never entered into Encore.</p>
        <a class="er-btn er-ticketPalAccess__button" href="{{ config('encore.ticketpal.organiser_login_url') }}">
          Log in with TicketPal
        </a>
        <p class="er-ticketPalAccess__note">You’ll continue securely on TicketPal.</p>
      </div>

      <div class="er-onboardingDivider" aria-hidden="true"><span>or</span></div>

      <form class="er-card er-form er-organiserRegistration" method="POST" action="{{ route('organisers.store') }}">
        @csrf
        <div>
          <p class="er-hero__eyebrow">New to Encore</p>
          <h2>Create a new organiser account</h2>
          <p>We verify new organiser accounts before dashboard access is activated.</p>
        </div>

        <div class="er-field">
          <label for="organisation_name">Organisation name</label>
          <input class="er-input" id="organisation_name" name="organisation_name" value="{{ old('organisation_name') }}" maxlength="255" autocomplete="organization" required autofocus>
          @error('organisation_name')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>

        <div class="er-field">
          <label for="name">Your name</label>
          <input class="er-input" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>
          @error('name')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>

        <div class="er-field">
          <label for="email">Work email address</label>
          <input class="er-input" type="email" id="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
          @error('email')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>

        <div class="er-formGrid">
          <div class="er-field">
            <label for="password">Password</label>
            <input class="er-input" type="password" id="password" name="password" minlength="12" autocomplete="new-password" required>
            @error('password')<p class="er-fieldError">{{ $message }}</p>@enderror
          </div>
          <div class="er-field">
            <label for="password_confirmation">Confirm password</label>
            <input class="er-input" type="password" id="password_confirmation" name="password_confirmation" minlength="12" autocomplete="new-password" required>
          </div>
        </div>
        <p class="er-fieldHint">Use at least 12 characters, including letters and numbers.</p>

        <label class="er-choice er-authorityChoice">
          <input type="checkbox" name="authority_confirmed" value="1" @checked(old('authority_confirmed')) required>
          <span>I confirm that I am authorised to create an account for this organisation.</span>
        </label>
        @error('authority_confirmed')<p class="er-fieldError">{{ $message }}</p>@enderror

        <button class="er-btn" type="submit">Create organiser account</button>
      </form>
    </div>

    <p class="er-onboardingLogin">Already have an Encore account? <a href="{{ route('login') }}">Log in to Encore</a>.</p>
  </div>
</section>
@endsection
