@extends('layouts.public')

@section('title', 'Browse shows — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container">
    <div class="er-sectionHeader">
      <h1 class="er-h2">Shows on Encore</h1>
      <p class="er-sectionIntro">Browse live events with audience-reviewed ratings powered by verified TicketPal ticket data.</p>
    </div>

    <form class="er-showSearch" action="{{ route('shows.index') }}" method="get" role="search">
      <div class="er-showSearch__field er-showSearch__field--query">
        <label for="show-search">Search shows</label>
        <input
          id="show-search"
          name="q"
          type="search"
          value="{{ $search }}"
          maxlength="100"
          placeholder="Title, description or genre"
        >
      </div>

      <div class="er-showSearch__field">
        <label for="show-status">Show status</label>
        <select id="show-status" name="status">
          <option value="" @selected(!$status)>All live shows</option>
          <option value="now_playing" @selected($status === 'now_playing')>Now playing</option>
          <option value="upcoming" @selected($status === 'upcoming')>Upcoming</option>
        </select>
      </div>

      <button class="er-btn" type="submit">Find shows</button>

      @if($search !== '' || $status)
        <a class="er-showSearch__clear" href="{{ route('shows.index') }}">Clear filters</a>
      @endif
    </form>

    <p class="er-resultsSummary" aria-live="polite">
      {{ $shows->total() }} {{ \Illuminate\Support\Str::plural('show', $shows->total()) }} found
    </p>

    @if($shows->isEmpty())
      <div class="er-card">
        @if($search !== '' || $status)
          <h2>No matching shows</h2>
          <p>Try a different search or <a href="{{ route('shows.index') }}">clear the filters</a>.</p>
        @else
          <p>No shows are available yet. Check back later.</p>
        @endif
      </div>
    @else
      <div class="er-grid">
        @foreach($shows as $show)
          @include('public.partials.show-card', ['show' => $show])
        @endforeach
      </div>

      @if($shows->hasPages())
        <nav class="er-pagination" aria-label="Show results pages">
          @if($shows->onFirstPage())
            <span class="er-pagination__disabled">Previous</span>
          @else
            <a href="{{ $shows->previousPageUrl() }}" rel="prev">Previous</a>
          @endif

          <span>Page {{ $shows->currentPage() }} of {{ $shows->lastPage() }}</span>

          @if($shows->hasMorePages())
            <a href="{{ $shows->nextPageUrl() }}" rel="next">Next</a>
          @else
            <span class="er-pagination__disabled">Next</span>
          @endif
        </nav>
      @endif
    @endif
  </div>
</section>
@endsection
