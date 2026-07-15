@extends('layouts.public')

@section('title', 'Manage '.$organisation->name.' — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">Encore administration</p>
        <h1 class="er-h2">{{ $organisation->name }}</h1>
        <p class="er-sectionIntro">Manage organisation access, ownership, and internal support details.</p>
      </div>
      <div class="er-adminActions"><a class="er-btn er-btn--secondary" href="{{ route('super.organisations.index') }}">All organisations</a><a class="er-btn" href="{{ route('super.organisations.support', $organisation) }}">Open support view</a></div>
    </div>

    @if(session('status'))<div class="er-notice er-notice--success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="er-notice er-notice--error">Please correct the form errors below.</div>@endif

    <div class="er-adminGrid">
      <form class="er-card er-form" method="POST" action="{{ route('super.organisations.update', $organisation) }}">
        @csrf @method('PATCH')
        <h2 class="er-adminTitle">Organisation details</h2>
        <div class="er-field"><label for="name">Organisation name</label><input class="er-input" id="name" name="name" value="{{ old('name', $organisation->name) }}" required></div>
        <div class="er-field"><label for="support_email">Support email</label><input class="er-input" type="email" id="support_email" name="support_email" value="{{ old('support_email', $organisation->support_email) }}"></div>
        <div class="er-field"><label for="notes">Internal notes</label><textarea class="er-input" id="notes" name="notes" rows="5">{{ old('notes', $organisation->notes) }}</textarea></div>
        <div class="er-field"><div class="er-fieldLegend">Status</div><div class="er-choiceGroup"><label class="er-choice"><input type="radio" name="is_active" value="1" @checked($organisation->is_active)> Active</label><label class="er-choice"><input type="radio" name="is_active" value="0" @checked(!$organisation->is_active)> Inactive</label></div></div>
        <button class="er-btn" type="submit">Save organisation</button>
      </form>

      <div class="er-card">
        <h2 class="er-adminTitle">Add organisation user</h2>
        <form class="er-form" method="POST" action="{{ route('super.organisations.users.store', $organisation) }}">
          @csrf
          <div class="er-field"><label for="new_name">Name</label><input class="er-input" id="new_name" name="name" required></div>
          <div class="er-field"><label for="new_email">Email</label><input class="er-input" type="email" id="new_email" name="email" required></div>
          <div class="er-formGrid"><div class="er-field"><label for="new_password">Temporary password</label><input class="er-input" type="password" id="new_password" name="password" required></div><div class="er-field"><label for="new_password_confirmation">Confirm</label><input class="er-input" type="password" id="new_password_confirmation" name="password_confirmation" required></div></div>
          <button class="er-btn" type="submit">Add user</button>
        </form>
      </div>
    </div>

    <section class="er-card er-adminRecent">
      <h2 class="er-adminTitle">Organisation users</h2>
      <div class="er-organisationUsers">
        @foreach($organisation->users as $user)
          <form class="er-organisationUser" method="POST" action="{{ route('super.organisations.users.update', [$organisation, $user]) }}">
            @csrf @method('PATCH')
            <div class="er-formGrid"><div class="er-field"><label>Name</label><input class="er-input" name="name" value="{{ $user->name }}" required></div><div class="er-field"><label>Email</label><input class="er-input" type="email" name="email" value="{{ $user->email }}" required></div></div>
            <div class="er-formGrid"><div class="er-field"><label>New password <span class="er-muted">(optional)</span></label><input class="er-input" type="password" name="password"></div><div class="er-field"><label>Confirm new password</label><input class="er-input" type="password" name="password_confirmation"></div></div>
            <div class="er-adminActions"><label class="er-choice"><input type="radio" name="is_active" value="1" @checked($user->is_active)> Active</label><label class="er-choice"><input type="radio" name="is_active" value="0" @checked(!$user->is_active)> Inactive</label><button class="er-btn er-btn--small" type="submit">Save user</button></div>
          </form>
        @endforeach
      </div>
    </section>

    <section class="er-card er-adminRecent">
      <h2 class="er-adminTitle">Assigned shows</h2>
      @forelse($organisation->shows as $show)
        <div class="er-organisationShow"><span><strong>{{ $show->title }}</strong> · {{ strtoupper(str_replace('_', ' ', $show->status)) }}</span><form method="POST" action="{{ route('super.organisations.shows.destroy', [$organisation, $show]) }}">@csrf @method('DELETE')<button class="er-btn er-btn--secondary er-btn--small" type="submit">Remove</button></form></div>
      @empty<p>No shows are assigned to this organisation.</p>@endforelse

      @if($availableShows->isNotEmpty())
        <form class="er-inlineForm" method="POST" action="{{ route('super.organisations.shows.store', $organisation) }}">@csrf<label class="er-srOnly" for="show_id">Unassigned show</label><select class="er-input" id="show_id" name="show_id" required><option value="">Select an unassigned show</option>@foreach($availableShows as $show)<option value="{{ $show->id }}">{{ $show->title }}</option>@endforeach</select><button class="er-btn" type="submit">Assign show</button></form>
      @endif
    </section>
  </div>
</section>
@endsection
