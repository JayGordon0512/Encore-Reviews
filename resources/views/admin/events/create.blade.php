@extends('layouts.public')

@section('title', 'Create event — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container er-formPage">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">Organiser event</p>
        <h1 class="er-h2">Create an event</h1>
        <p class="er-sectionIntro">Add an event independently of TicketPal and include every performance date in one step.</p>
      </div>
      <a class="er-btn er-btn--secondary" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
    </div>

    @if($errors->any())
      <div class="er-notice er-notice--error" role="alert">Please correct the highlighted fields.</div>
    @endif

    <form class="er-card er-form" method="POST" action="{{ route('admin.events.store') }}">
      @csrf

      <div class="er-formGrid">
        <div class="er-field">
          <label for="title">Event title</label>
          <input class="er-input" id="title" name="title" value="{{ old('title') }}" maxlength="255" required autofocus>
          @error('title')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>
        <div class="er-field">
          <label for="genre">Genre <span class="er-muted">(optional)</span></label>
          <input class="er-input" id="genre" name="genre" value="{{ old('genre') }}" maxlength="100">
          @error('genre')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="er-field">
        <label for="summary">Short summary <span class="er-muted">(optional)</span></label>
        <input class="er-input" id="summary" name="summary" value="{{ old('summary') }}" maxlength="1000">
        @error('summary')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>

      <div class="er-field">
        <label for="description">Description <span class="er-muted">(optional)</span></label>
        <textarea class="er-input" id="description" name="description" maxlength="10000">{{ old('description') }}</textarea>
        @error('description')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>

      <div class="er-field">
        <label for="ticket_url">Ticket or event page <span class="er-muted">(optional)</span></label>
        <input class="er-input" type="url" id="ticket_url" name="ticket_url" value="{{ old('ticket_url') }}" placeholder="https://">
        <p class="er-fieldHint">Leave blank if tickets are not sold online.</p>
        @error('ticket_url')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>

      <fieldset class="er-formSection">
        <legend>Venue <span class="er-muted">(optional)</span></legend>
        <div class="er-formGrid er-formGrid--three">
          <div class="er-field">
            <label for="venue_name">Venue name</label>
            <input class="er-input" id="venue_name" name="venue_name" value="{{ old('venue_name') }}">
          </div>
          <div class="er-field">
            <label for="venue_city">Town or city</label>
            <input class="er-input" id="venue_city" name="venue_city" value="{{ old('venue_city') }}">
          </div>
          <div class="er-field">
            <label for="venue_postcode">Postcode</label>
            <input class="er-input" id="venue_postcode" name="venue_postcode" value="{{ old('venue_postcode') }}">
          </div>
        </div>
      </fieldset>

      <fieldset class="er-formSection" data-performance-editor>
        <div class="er-formSection__header">
          <div>
            <legend>Event dates</legend>
            <p>Add each performance date and time. End time is optional.</p>
          </div>
          <button class="er-btn er-btn--secondary er-btn--small" type="button" data-add-performance>Add another date</button>
        </div>

        <div class="er-performanceEditor" data-performance-list>
          @foreach(old('performances', [['starts_at' => '', 'ends_at' => '']]) as $index => $performance)
            <div class="er-performanceRow" data-performance-row>
              <div class="er-field">
                <label for="performance_{{ $index }}_starts_at">Starts</label>
                <input class="er-input" type="datetime-local" id="performance_{{ $index }}_starts_at" name="performances[{{ $index }}][starts_at]" value="{{ $performance['starts_at'] ?? '' }}" required>
                @error("performances.$index.starts_at")<p class="er-fieldError">{{ $message }}</p>@enderror
              </div>
              <div class="er-field">
                <label for="performance_{{ $index }}_ends_at">Ends <span class="er-muted">(optional)</span></label>
                <input class="er-input" type="datetime-local" id="performance_{{ $index }}_ends_at" name="performances[{{ $index }}][ends_at]" value="{{ $performance['ends_at'] ?? '' }}">
                @error("performances.$index.ends_at")<p class="er-fieldError">{{ $message }}</p>@enderror
              </div>
              <button class="er-btn er-btn--secondary er-btn--small er-performanceRow__remove" type="button" data-remove-performance>Remove</button>
            </div>
          @endforeach
        </div>

        <template data-performance-template>
          <div class="er-performanceRow" data-performance-row>
            <div class="er-field">
              <label for="performance___INDEX___starts_at">Starts</label>
              <input class="er-input" type="datetime-local" id="performance___INDEX___starts_at" name="performances[__INDEX__][starts_at]" required>
            </div>
            <div class="er-field">
              <label for="performance___INDEX___ends_at">Ends <span class="er-muted">(optional)</span></label>
              <input class="er-input" type="datetime-local" id="performance___INDEX___ends_at" name="performances[__INDEX__][ends_at]">
            </div>
            <button class="er-btn er-btn--secondary er-btn--small er-performanceRow__remove" type="button" data-remove-performance>Remove</button>
          </div>
        </template>
        @error('performances')<p class="er-fieldError">{{ $message }}</p>@enderror
      </fieldset>

      <button class="er-btn" type="submit">Create event</button>
    </form>
  </div>
</section>
@endsection
