@extends('layouts.public')

@section('title', 'Submit your review — Encore Reviews')

@section('content')
<section class="er-section">
  <div class="er-container" style="max-width: 720px;">
    <h1 class="er-h2">Submit your review</h1>
    <p>Use your review invitation token to share honest feedback about the show.</p>

    <div class="er-card">
      <form id="reviewSubmissionForm">
        <label for="invitation_token">Invitation token</label>
        <input type="text" id="invitation_token" name="invitation_token" required style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;" />

        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;" />

        <label for="display_name">Display name</label>
        <input type="text" id="display_name" name="display_name" style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;" />

        <label for="rating">Rating</label>
        <select id="rating" name="rating" required style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;">
          <option value="">Select rating</option>
          <option value="5">5 — Excellent</option>
          <option value="4">4 — Very good</option>
          <option value="3">3 — Good</option>
          <option value="2">2 — Fair</option>
          <option value="1">1 — Poor</option>
        </select>

        <label>Would you recommend this show?</label>
        <div style="display:flex; gap:12px; margin:8px 0 16px;">
          <label><input type="radio" name="would_recommend" value="1" required /> Yes</label>
          <label><input type="radio" name="would_recommend" value="0" required /> No</label>
        </div>

        <label for="tags">Tags (comma separated)</label>
        <input type="text" id="tags" name="tags" placeholder="e.g. funny, intimate" style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;" />

        <label for="content">Review</label>
        <textarea id="content" name="content" rows="6" style="width:100%; padding:12px; margin:8px 0 16px; border-radius:10px; border:1px solid #cbd5e1;"></textarea>

        <button class="er-btn" type="submit">Submit review</button>
      </form>

      <div id="reviewSubmissionResult" style="margin-top:18px; display:none;"></div>
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
          result.style.color = '#b91c1c';
          result.textContent = data.message || 'Unable to submit the review. Please check your token and try again.';
          return;
        }

        form.reset();
        result.style.display = 'block';
        result.style.color = '#0f766e';
        result.textContent = 'Thank you! Your review has been submitted successfully.';
      } catch (error) {
        result.style.display = 'block';
        result.style.color = '#b91c1c';
        result.textContent = 'An unexpected error occurred. Please try again later.';
      }
    });
  });
</script>
