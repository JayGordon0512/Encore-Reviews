@extends('layouts.public')

@section('title', 'Create organisation — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container er-formPage">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Encore administration</p>
      <h1 class="er-h2">Create organisation</h1>
      <p class="er-sectionIntro">Set up the organisation and its first customer administrator.</p>
    </div>

    @if($errors->any())<div class="er-notice er-notice--error">Please correct the highlighted fields.</div>@endif

    <form class="er-card er-form" method="POST" action="{{ route('super.organisations.store') }}">
      @csrf
      <h2 class="er-adminTitle">Organisation</h2>
      <div class="er-formGrid">
        <div class="er-field"><label for="name">Account name</label><input class="er-input" id="name" name="name" value="{{ old('name') }}" required>@error('name')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
        <div class="er-field"><label for="support_email">Support email</label><input class="er-input" type="email" id="support_email" name="support_email" value="{{ old('support_email') }}">@error('support_email')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
      </div>
      <div class="er-field"><label for="notes">Internal support notes</label><textarea class="er-input" id="notes" name="notes" rows="4">{{ old('notes') }}</textarea></div>

      <h2 class="er-adminTitle">First administrator</h2>
      <div class="er-formGrid">
        <div class="er-field"><label for="admin_name">Name</label><input class="er-input" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>@error('admin_name')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
        <div class="er-field"><label for="admin_email">Email</label><input class="er-input" type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>@error('admin_email')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
        <div class="er-field"><label for="admin_password">Temporary password</label><input class="er-input" type="password" id="admin_password" name="admin_password" required>@error('admin_password')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
        <div class="er-field"><label for="admin_password_confirmation">Confirm password</label><input class="er-input" type="password" id="admin_password_confirmation" name="admin_password_confirmation" required></div>
      </div>
      <div class="er-adminActions"><button class="er-btn" type="submit">Create organisation</button><a class="er-btn er-btn--secondary" href="{{ route('super.organisations.index') }}">Cancel</a></div>
    </form>
  </div>
</section>
@endsection
