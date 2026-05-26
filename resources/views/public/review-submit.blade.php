@extends('layouts.public')

@section('title', 'Submit your review — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container er-formPage">
    <div class="er-sectionHeader">
      <h1 class="er-h2">Submit your review</h1>
      <p class="er-sectionIntro">Use your review invitation token to share honest feedback about the show.</p>
    </div>

    <div class="er-card">
      <form class="er-form" id="reviewSubmissionForm">
        <div class="er-field">
          <label for="invitation_token">Invitation token</label>
          <input class="er-input" type="text" id="invitation_token" name="invitation_token" required>
        </div>

        <div class="er-field">
          <label for="email">Email address</label>
          <input class="er-input" type="email" id="email" name="email" required>
        </div>

        <div class="er-field">
          <label for="display_name">Display name</label>
          <input class="er-input" type="text" id="display_name" name="display_name">
        </div>

        <div class="er-field">
          <label for="rating">Rating</label>
          <select class="er-input" id="rating" name="rating" required>
            <option value="">Select rating</option>
            <option value="5">5 — Excellent</option>
            <option value="4">4 — Very good</option>
            <option value="3">3 — Good</option>
            <option value="2">2 — Fair</option>
            <option value="1">1 — Poor</option>
          </select>
        </div>

        <div class="er-field">
          <div class="er-fieldLegend">Would you recommend this show?</div>
          <div class="er-choiceGroup">
            <label class="er-choice"><input type="radio" name="would_recommend" value="1" required> Yes</label>
            <label class="er-choice"><input type="radio" name="would_recommend" value="0" required> No</label>
          </div>
        </div>

        <div class="er-field">
          <label for="tags">Tags (comma separated)</label>
          <input class="er-input" type="text" id="tags" name="tags" placeholder="e.g. funny, intimate">
        </div>

        <div class="er-field">
          <label for="content">Review</label>
          <textarea class="er-input" id="content" name="content" rows="6"></textarea>
        </div>

        <button class="er-btn" type="submit">Submit review</button>
      </form>

      <div class="er-formResult" id="reviewSubmissionResult"></div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reviewSubmissionForm');
    const result = document.getElementById('reviewSubmissionResult');

      if (!form || !result) return;

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      result.style.display = 'none';
      result.className = 'er-formResult';
      result.textContent = '';

      const formData = new FormData(form);
      const payload = {
        invitation_token: formData.get('invitation_token'),
        email: formData.get('email'),
        display_name: formData.get('display_name'),
        rating: Number(formData.get('rating')),
        would_recommend: formData.get('would_recommend') === '1',
        tags: formData.get('tags') ? formData.get('tags').split(',').map(tag => tag.trim()).filter(Boolean) : undefined,
        content: formData.get('content') || undefined,
      };

      try {
        const response = await fetch('/api/reviews', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
          result.style.display = 'block';
          result.classList.add('is-error');
          result.textContent = data.message || 'Unable to submit the review. Please check your token and try again.';
          return;
        }

        form.reset();
        result.style.display = 'block';
        result.classList.add('is-success');
        result.textContent = 'Thank you! Your review has been submitted successfully.';
      } catch (error) {
        result.style.display = 'block';
        result.classList.add('is-error');
        result.textContent = 'An unexpected error occurred. Please try again later.';
      }
    });
  });
</script>
@endsection
