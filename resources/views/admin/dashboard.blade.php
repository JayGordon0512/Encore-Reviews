@extends('layouts.public')

@section('title', ($supportMode ? 'Support view' : 'Customer admin').' — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">{{ $supportMode ? 'Encore support view' : 'Customer admin' }}</p>
        <h1 class="er-h2">Review dashboard</h1>
        <p class="er-sectionIntro">{{ $organisation->name }} · Monitor verified audience feedback across your live shows.</p>
      </div>

      @if($supportMode)
        <a class="er-btn er-btn--secondary" href="{{ route('super.organisations.edit', $organisation) }}">Back to organisation</a>
      @else
        <div class="er-adminActions">
          <a class="er-btn" href="{{ route('admin.events.create') }}">Create event</a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="er-btn er-btn--secondary" type="submit">Log out</button>
          </form>
        </div>
      @endif
    </div>

    @if($supportMode)
      <div class="er-notice">Read-only support mode. You are viewing the same organisation-scoped data available to this customer.</div>
    @endif
    @if(session('status'))
      <div class="er-notice er-notice--success">{{ session('status') }}</div>
    @endif

    <div class="er-adminStats">
      <div class="er-card er-statCard">
        <span>Live shows</span>
        <strong>{{ $stats['shows'] }}</strong>
      </div>
      <div class="er-card er-statCard">
        <span>Approved reviews</span>
        <strong>{{ $stats['approvedReviews'] }}</strong>
      </div>
      <div class="er-card er-statCard">
        <span>Pending reviews</span>
        <strong>{{ $stats['pendingReviews'] }}</strong>
      </div>
      <div class="er-card er-statCard">
        <span>Average score</span>
        <strong>{{ $stats['averageRating'] ? number_format($stats['averageRating'], 1) : '—' }}</strong>
      </div>
    </div>

    <div class="er-adminGrid">
      <section class="er-card">
        <h2 class="er-adminTitle">Shows</h2>
        @if($shows->isEmpty())
          <p>No shows are available yet.</p>
        @else
          <div class="er-adminList">
            @foreach($shows as $show)
              <article class="er-adminListItem">
                <div>
                  <h3>{{ $show->title }}</h3>
                  <p>{{ strtoupper(str_replace('_', ' ', $show->status)) }} · {{ $show->performances_count }} date(s) · {{ $show->audience_attendances_count }} imported</p>
                </div>
                <div class="er-adminActions">
                  @if(!$supportMode && $show->provider_source === \App\Models\Show::SOURCE_MANUAL)
                    <a class="er-btn er-btn--secondary er-btn--small" href="{{ route('admin.events.show', $show) }}">Manage</a>
                  @endif
                  <div class="er-adminMetric">
                    <strong>{{ $show->reviews->count() }}</strong>
                    <span>approved</span>
                  </div>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </section>

      <section class="er-card">
        <h2 class="er-adminTitle">Pending review queue</h2>
        @if($pendingReviews->isEmpty())
          <p>No reviews are waiting for moderation.</p>
        @else
          <div class="er-adminList">
            @foreach($pendingReviews as $review)
              <article class="er-adminListItem er-adminListItem--review">
                <div>
                  <h3>{{ $review->performance?->show?->title ?? 'Unknown show' }}</h3>
                  <p>{{ $review->reviewer?->display_name ?? 'Anonymous' }} · {{ $review->submitted_at?->format('j M Y') ?? 'Unknown date' }}</p>
                  @if($review->content)<p class="er-adminExcerpt">{{ $review->content }}</p>@endif
                </div>
                @if($supportMode)
                  <span class="er-statusPill">PENDING</span>
                @else
                  <div class="er-moderationActions">
                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="moderation_status" value="approved">
                      <button class="er-btn er-btn--small" type="submit">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
                      @csrf
                      @method('PATCH')
                      <input type="hidden" name="moderation_status" value="rejected">
                      <button class="er-btn er-btn--small er-btn--danger" type="submit">Reject</button>
                    </form>
                  </div>
                @endif
              </article>
            @endforeach
          </div>
        @endif
      </section>
    </div>

    <section class="er-card er-adminRecent">
      <h2 class="er-adminTitle">Recent reviews</h2>
      @if($recentReviews->isEmpty())
        <p>No reviews have been submitted yet.</p>
      @else
        <div class="er-adminTable">
          <div class="er-adminTable__head">
            <span>Show</span>
            <span>Reviewer</span>
            <span>Rating</span>
            <span>Status</span>
          </div>
          @foreach($recentReviews as $review)
            <div class="er-adminTable__row">
              <span>{{ $review->performance?->show?->title ?? 'Unknown show' }}</span>
              <span>{{ $review->reviewer?->display_name ?? 'Anonymous' }}</span>
              <span>{{ $review->rating }}/5</span>
              <span>{{ strtoupper($review->moderation_status ?? 'unknown') }}</span>
            </div>
          @endforeach
        </div>
      @endif
    </section>
  </div>
</section>
@endsection
