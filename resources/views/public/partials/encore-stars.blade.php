@php
  $score = $score ?? null;
  $filledStars = $score !== null ? (int) round($score) : 0;
  $label = $label ?? ($score !== null ? 'Encore score '.number_format($score, 1).' out of 5' : 'No Encore score yet');
  $class = $class ?? '';
@endphp

<div class="er-starScore {{ $class }}" aria-label="{{ $label }}">
  <div class="er-starScore__stars" aria-hidden="true">
    @for($star = 1; $star <= 5; $star++)
      <img
        class="er-starScore__star {{ $star <= $filledStars ? 'is-filled' : '' }}"
        src="{{ asset('assets/encore-icon.png') }}"
        alt=""
      >
    @endfor
  </div>
</div>
