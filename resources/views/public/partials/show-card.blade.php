@php
  $approvedReviews = $show->relationLoaded('reviews') ? $show->reviews : collect();
  $reviewCount = $approvedReviews->count();
  $averageRating = $reviewCount > 0 ? $approvedReviews->avg('rating') : null;
@endphp

<article class="er-card er-showCard">
  <a class="er-showCard__media" href="{{ route('shows.show', $show) }}" aria-label="View {{ $show->title }}">
    <img
      src="{{ asset($show->primary_image_path ?: 'assets/hero-show-bg.jpg') }}"
      alt="{{ $show->title }} event artwork"
      loading="lazy"
      decoding="async"
    >
  </a>

  <div class="er-showCard__body">
    <h3>{{ $show->title }}</h3>
    <p>{{ $show->summary ?? (isset($show->description) ? substr($show->description, 0, 120) . (strlen($show->description) > 120 ? '…' : '') : 'No summary available.') }}</p>
    <p class="er-card__meta"><strong>Status:</strong> {{ strtoupper(str_replace('_', ' ', $show->status)) }}</p>
  </div>

  <div class="er-showCard__footer">
    @if($reviewCount > 0)
      <div class="er-cardScore">
        @include('public.partials.encore-stars', [
          'score' => $averageRating,
          'label' => 'Encore score '.number_format($averageRating, 1).' out of 5 from '.$reviewCount.' '.\Illuminate\Support\Str::plural('review', $reviewCount),
        ])
        <span class="er-cardScore__meta">{{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
      </div>
    @else
      <div class="er-cardScore er-cardScore--empty">No reviews yet</div>
    @endif

    <a class="er-btn" href="{{ route('shows.show', $show) }}">View show</a>
  </div>
</article>
