@extends('layouts.public')

@section('title', 'Submit your review — Encore Reviews')

@section('head')
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer">
@endsection

@section('content')
<section class="er-section er-reviewPage">
  <div class="er-container er-reviewPage__inner">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Verified audience review</p>
      <h1 class="er-h2">Submit your review</h1>
      <p class="er-sectionIntro">Share feedback from your recent visit. Your invitation token confirms the review came from a verified ticket holder.</p>
    </div>

    <div class="er-reviewShell">
      <form class="er-card er-reviewForm" id="reviewSubmissionForm">
        @if($invitation->performance?->show)
          <div class="er-reviewContext">
            <span class="er-reviewContext__label">You’re reviewing</span>
            <strong>{{ $invitation->performance->show->title }}</strong>
            @if($invitation->performance->starts_at)
              <span>{{ $invitation->performance->starts_at->format('j M Y, g:ia') }}</span>
            @endif
          </div>
        @endif

        <div class="er-formGrid">
          <div class="er-field">
            <label for="email">Email address</label>
            <input class="er-input" type="email" id="email" name="email" autocomplete="email" required>
          </div>

          <div class="er-field">
            <label for="display_name">Display name</label>
            <input class="er-input" type="text" id="display_name" name="display_name" autocomplete="name" placeholder="Anonymous">
          </div>
        </div>

        <div class="er-field">
          <div class="er-fieldLegend">Your Encore score</div>
          <div class="er-ratingGroup" aria-label="Rating out of 5">
            @for($rating = 1; $rating <= 5; $rating++)
              <label class="er-ratingChoice">
                <input type="radio" name="rating" value="{{ $rating }}" required>
                <img src="{{ asset('assets/encore-icon.png') }}" alt="">
                <span>{{ $rating }}</span>
              </label>
            @endfor
          </div>
        </div>

        <div class="er-field">
          <div class="er-fieldLegend">Would you recommend this show?</div>
          <div class="er-choiceGroup er-choiceGroup--cards">
            <label class="er-choice er-choice--card"><input type="radio" name="would_recommend" value="1" required> Yes</label>
            <label class="er-choice er-choice--card"><input type="radio" name="would_recommend" value="0" required> No</label>
          </div>
        </div>

        <div class="er-field">
          <div class="er-fieldLegend">What stood out?</div>
          <div class="er-tagChoiceGroup">
            @foreach(['Funny', 'Moving', 'Original', 'Great cast', 'Great music', 'Family friendly'] as $tag)
              <label class="er-tagChoice"><input type="checkbox" name="tags[]" value="{{ $tag }}"> {{ $tag }}</label>
            @endforeach
          </div>
        </div>

        <div class="er-field">
          <label for="other_tags">Other tags</label>
          <input class="er-input" type="text" id="other_tags" name="other_tags" placeholder="e.g. intimate, surprising">
        </div>

        <div class="er-field">
          <label for="content">Your review</label>
          <textarea class="er-input" id="content" name="content" rows="7" maxlength="2000" placeholder="What should future audience members know?"></textarea>
        </div>

        <div class="er-reviewForm__actions">
          <button class="er-btn" type="submit" data-submit-review>Submit review</button>
          <span class="er-reviewForm__note">Reviews are checked before appearing publicly.</span>
        </div>

        <div class="er-formResult" id="reviewSubmissionResult" role="status" aria-live="polite"></div>
      </form>

      <aside class="er-reviewAside" aria-label="Review guidance">
        <h2>What makes a useful review?</h2>
        <p>Focus on your own experience: the performance, atmosphere, venue, and who you would recommend it to.</p>
        <ul>
          <li>Keep it honest and specific.</li>
          <li>Avoid spoilers where possible.</li>
          <li>Use the same email your invitation was sent to.</li>
        </ul>
      </aside>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reviewSubmissionForm');
    const result = document.getElementById('reviewSubmissionResult');
    const submitButton = form?.querySelector('[data-submit-review]');

    if (!form || !result || !submitButton) return;

    const setResult = (type, message) => {
      result.style.display = 'block';
      result.className = `er-formResult is-${type}`;
      result.textContent = message;
    };

    const collectTags = (formData) => {
      const selectedTags = formData.getAll('tags[]').map((tag) => String(tag).trim()).filter(Boolean);
      const otherTags = String(formData.get('other_tags') || '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean);

      return [...selectedTags, ...otherTags];
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      result.style.display = 'none';
      result.className = 'er-formResult';
      result.textContent = '';

      if (!form.reportValidity()) return;

      const formData = new FormData(form);
      const tags = collectTags(formData);
      const payload = {
        email: formData.get('email'),
        display_name: formData.get('display_name') || undefined,
        rating: Number(formData.get('rating')),
        would_recommend: formData.get('would_recommend') === '1',
        tags: tags.length ? tags : undefined,
        content: formData.get('content') || undefined,
      };

      submitButton.disabled = true;
      submitButton.textContent = 'Submitting...';

      try {
        const response = await fetch('{{ route('review.submit.store') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
          setResult('error', data.message || 'Unable to submit the review. Please check your token and try again.');
          return;
        }

        form.reset();
        setResult('success', 'Thank you. Your review has been submitted and will appear after moderation.');
      } catch (error) {
        setResult('error', 'An unexpected error occurred. Please try again later.');
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit review';
      }
    });
  });
</script>
@endsection
