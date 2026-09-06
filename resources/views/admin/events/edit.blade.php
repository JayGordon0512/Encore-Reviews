@extends('layouts.public')

@section('title', 'Edit '.$show->title.' — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container er-formPage">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">Organiser event</p>
        <h1 class="er-h2">Edit {{ $show->title }}</h1>
        <p class="er-sectionIntro">Timing changes automatically recalculate review emails that have not yet been sent.</p>
      </div>
      <a class="er-btn er-btn--secondary" href="{{ route('admin.events.show', $show) }}">Back to event</a>
    </div>

    @if($errors->any())
      <div class="er-notice er-notice--error" role="alert">Please correct the highlighted fields.</div>
    @endif

    <form class="er-card er-form" method="POST" action="{{ route('admin.events.update', $show) }}">
      @csrf
      @method('PATCH')

      <div class="er-formGrid">
        <div class="er-field">
          <label for="title">Event title</label>
          <input class="er-input" id="title" name="title" value="{{ old('title', $show->title) }}" maxlength="255" required autofocus>
          @error('title')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>
        <div class="er-field">
          <label for="genre">Genre <span class="er-muted">(optional)</span></label>
          <input class="er-input" id="genre" name="genre" value="{{ old('genre', $show->genre) }}" maxlength="100">
          @error('genre')<p class="er-fieldError">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="er-field">
        <label for="summary">Short summary <span class="er-muted">(optional)</span></label>
        <input class="er-input" id="summary" name="summary" value="{{ old('summary', $show->summary) }}" maxlength="1000">
        @error('summary')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>
      <div class="er-field">
        <label for="description">Description <span class="er-muted">(optional)</span></label>
        <textarea class="er-input" id="description" name="description" maxlength="10000">{{ old('description', $show->description) }}</textarea>
        @error('description')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>
      <div class="er-field">
        <label for="ticket_url">Ticket or event page <span class="er-muted">(optional)</span></label>
        <input class="er-input" type="url" id="ticket_url" name="ticket_url" value="{{ old('ticket_url', $show->ticket_url) }}" placeholder="https://">
        @error('ticket_url')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>

      @php($venue = $show->performances->first()?->venue)
      <fieldset class="er-formSection">
        <legend>Venue <span class="er-muted">(optional)</span></legend>
        <div class="er-formGrid er-formGrid--three">
          <div class="er-field"><label for="venue_name">Venue name</label><input class="er-input" id="venue_name" name="venue_name" value="{{ old('venue_name', $venue?->name) }}">@error('venue_name')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
          <div class="er-field"><label for="venue_city">Town or city</label><input class="er-input" id="venue_city" name="venue_city" value="{{ old('venue_city', $venue?->city) }}">@error('venue_city')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
          <div class="er-field"><label for="venue_postcode">Postcode</label><input class="er-input" id="venue_postcode" name="venue_postcode" value="{{ old('venue_postcode', $venue?->postcode) }}">@error('venue_postcode')<p class="er-fieldError">{{ $message }}</p>@enderror</div>
        </div>
      </fieldset>

      @php($durationOptions = collect([30, 45, 60, 75, 90, 105, 120, 150, 180, 240, 300, 360])->push($durationMinutes)->unique()->sort())
      <div class="er-field er-durationField">
        <label for="duration_minutes">Event duration</label>
        <select class="er-input" id="duration_minutes" name="duration_minutes" required data-event-duration>
          @foreach($durationOptions as $minutes)
            <option value="{{ $minutes }}" @selected((int) old('duration_minutes', $durationMinutes) === $minutes)>
              {{ intdiv($minutes, 60) ? intdiv($minutes, 60).' '.Str::plural('hour', intdiv($minutes, 60)) : '' }}{{ intdiv($minutes, 60) && $minutes % 60 ? ' ' : '' }}{{ $minutes % 60 ? ($minutes % 60).' minutes' : '' }}
            </option>
          @endforeach
        </select>
        <p class="er-fieldHint">Applied to every active performance date.</p>
        @error('duration_minutes')<p class="er-fieldError">{{ $message }}</p>@enderror
      </div>

      @php($performanceValues = old('performances', $show->performances->map(fn ($performance) => ['id' => $performance->id, 'starts_at' => $performance->starts_at?->format('Y-m-d\TH:i')])->all()) ?: [['id' => null, 'starts_at' => '']])
      <fieldset class="er-formSection" data-performance-editor data-invitation-delay-hours="{{ config('encore.audience_imports.invitation_delay_hours') }}">
        <div class="er-formSection__header">
          <div><legend>Event dates</legend><p>Change a start time or add another performance. Use “Cancel date” on the event page to withdraw a date.</p></div>
          <button class="er-btn er-btn--secondary er-btn--small" type="button" data-add-performance>Add another date</button>
        </div>
        <div class="er-performanceEditor" data-performance-list>
          @foreach($performanceValues as $index => $performance)
            <div class="er-performanceRow" data-performance-row>
              @if(filled($performance['id'] ?? null))<input type="hidden" name="performances[{{ $index }}][id]" value="{{ $performance['id'] }}">@endif
              <div class="er-field">
                <label for="performance_{{ $index }}_starts_at">Starts</label>
                <input class="er-input" type="datetime-local" id="performance_{{ $index }}_starts_at" name="performances[{{ $index }}][starts_at]" value="{{ $performance['starts_at'] ?? '' }}" required>
                @error("performances.$index.starts_at")<p class="er-fieldError">{{ $message }}</p>@enderror
              </div>
              <div class="er-performanceTiming" aria-live="polite"><strong>Automatic timing</strong><output data-performance-timing>Choose a start time</output></div>
              <button class="er-btn er-btn--secondary er-btn--small er-performanceRow__remove" type="button" data-remove-performance>Remove</button>
            </div>
          @endforeach
        </div>
        <template data-performance-template>
          <div class="er-performanceRow" data-performance-row>
            <div class="er-field"><label for="performance___INDEX___starts_at">Starts</label><input class="er-input" type="datetime-local" id="performance___INDEX___starts_at" name="performances[__INDEX__][starts_at]" required></div>
            <div class="er-performanceTiming" aria-live="polite"><strong>Automatic timing</strong><output data-performance-timing>Choose a start time</output></div>
            <button class="er-btn er-btn--secondary er-btn--small er-performanceRow__remove" type="button" data-remove-performance>Remove</button>
          </div>
        </template>
        @error('performances')<p class="er-fieldError">{{ $message }}</p>@enderror
      </fieldset>

      <button class="er-btn" type="submit">Save event changes</button>
    </form>
  </div>
</section>
@endsection
