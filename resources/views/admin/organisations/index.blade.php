@extends('layouts.public')

@section('title', 'Organisations — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">Encore administration</p>
        <h1 class="er-h2">Organisations</h1>
        <p class="er-sectionIntro">Create customer access, manage organisation status, and inspect dashboards for support.</p>
      </div>
      <div class="er-adminActions">
        <a class="er-btn" href="{{ route('super.organisations.create') }}">Create organisation</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="er-btn er-btn--secondary" type="submit">Log out</button></form>
      </div>
    </div>

    @if(session('status'))<div class="er-notice er-notice--success">{{ session('status') }}</div>@endif

    <div class="er-card er-organisationDirectory">
      @forelse($organisations as $organisation)
        <article class="er-organisationRow">
          <div>
            <div class="er-organisationRow__title">
              <h2>{{ $organisation->name }}</h2>
              <span class="er-statusPill {{ $organisation->is_active ? 'is-active' : 'is-inactive' }}">{{ $organisation->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
            </div>
            <p>{{ $organisation->support_email ?: 'No support email' }}</p>
          </div>
          <div class="er-organisationRow__metrics">
            <span><strong>{{ $organisation->users_count }}</strong> users</span>
            <span><strong>{{ $organisation->shows_count }}</strong> shows</span>
          </div>
          <div class="er-adminActions">
            <a class="er-btn er-btn--secondary er-btn--small" href="{{ route('super.organisations.support', $organisation) }}">Support view</a>
            <a class="er-btn er-btn--small" href="{{ route('super.organisations.edit', $organisation) }}">Manage</a>
          </div>
        </article>
      @empty
        <p>No organisations have been created yet.</p>
      @endforelse
    </div>
  </div>
</section>
@endsection
