@extends('layouts.public')

@section('title', 'Opening your review invitation — Encore Reviews')

@section('head')
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
@endsection

@section('content')
<section class="er-section">
  <div class="er-container">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Verified audience review</p>
      <h1 class="er-h2">Opening your secure invitation</h1>
      <p class="er-sectionIntro" data-invitation-status>Please wait while Encore verifies your personal review link.</p>
    </div>

    <div class="er-card" data-invitation-error hidden>
      <p>This review invitation is missing, invalid, expired, or has already been used.</p>
      <a class="er-btn" href="{{ route('shows.index') }}">Browse shows</a>
    </div>

    <noscript>
      <div class="er-card">
        <p>JavaScript is required to open this secure invitation link.</p>
      </div>
    </noscript>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', async () => {
    const status = document.querySelector('[data-invitation-status]');
    const error = document.querySelector('[data-invitation-error]');
    const fragment = new URLSearchParams(window.location.hash.slice(1));
    let token = fragment.get('token');

    history.replaceState(null, document.title, window.location.pathname);

    const unavailable = () => {
      if (status) status.textContent = 'This review invitation is unavailable.';
      if (error) error.hidden = false;
    };

    if (!token) {
      unavailable();
      return;
    }

    try {
      const response = await fetch('{{ route('review.invitation.exchange') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ invitation_token: token }),
      });
      token = null;
      const data = await response.json();

      if (!response.ok || !data.redirect) {
        unavailable();
        return;
      }

      window.location.replace(data.redirect);
    } catch (requestError) {
      token = null;
      unavailable();
    }
  });
</script>
@endsection
