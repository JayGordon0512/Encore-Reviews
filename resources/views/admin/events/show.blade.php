@extends('layouts.public')

@section('title', 'Manage '.$show->title.' — Encore Reviews')

@section('content')
<section class="er-section er-adminPage">
  <div class="er-container">
    <div class="er-adminHeader">
      <div class="er-sectionHeader">
        <p class="er-hero__eyebrow">Organiser event</p>
        <h1 class="er-h2">{{ $show->title }}</h1>
        <p class="er-sectionIntro">{{ $show->performances->count() }} date(s) · {{ $show->audience_attendances_count }} imported customer(s)</p>
      </div>
      <div class="er-adminActions">
        <a class="er-btn er-btn--secondary" href="{{ route('shows.show', $show) }}">View public page</a>
        <a class="er-btn er-btn--secondary" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
      </div>
    </div>

    @if(session('status'))
      <div class="er-notice er-notice--success" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
      <div class="er-notice er-notice--error" role="alert">Please correct the customer import.</div>
    @endif

    @if($invitationIssuingEnabled)
      <div class="er-notice er-notice--success" role="status">
        Automatic review invitations are active. Emails are scheduled {{ $invitationDelayHours }} {{ Str::plural('hour', $invitationDelayHours) }} after each performance ends.
      </div>
    @else
      <div class="er-notice" role="status">
        <strong>Automatic review invitations are paused.</strong> New imports are securely held and no review emails are sent while paused.
      </div>
    @endif

    <div class="er-adminGrid er-eventManagementGrid">
      <section class="er-card er-artworkManager">
        <h2 class="er-adminTitle">Event artwork</h2>
        <img class="er-artworkManager__preview" src="{{ asset($show->artworkPath()) }}" alt="{{ $show->title }} event artwork">
        <form class="er-form" method="POST" action="{{ route('admin.events.artwork.update', $show) }}" enctype="multipart/form-data">
          @csrf
          @method('PATCH')
          <div class="er-field">
            <label for="event_image">Upload new artwork</label>
            <input class="er-input" type="file" id="event_image" name="event_image" accept="image/jpeg,image/png,image/webp" required>
            <p class="er-fieldHint">JPEG, PNG or WebP, up to {{ round(config('encore.event_images.max_size_kb') / 1024) }} MB.</p>
            @error('event_image')<p class="er-fieldError">{{ $message }}</p>@enderror
          </div>
          <button class="er-btn er-btn--secondary" type="submit">Update artwork</button>
        </form>
      </section>

      <section class="er-card">
        <h2 class="er-adminTitle">Event dates</h2>
        <div class="er-adminList">
          @foreach($show->performances as $performance)
            <article class="er-adminListItem">
              <div>
                <h3>{{ $performance->starts_at?->format('D j M Y, H:i') ?? 'Date to be confirmed' }}</h3>
                <p>{{ $performance->venue?->name ?? 'Venue to be confirmed' }}@if($performance->venue?->city) · {{ $performance->venue->city }} @endif</p>
                @if($performance->next_invitation_at)
                  <p>Next email run: {{ \Illuminate\Support\Carbon::parse($performance->next_invitation_at)->format('D j M Y, H:i') }}</p>
                @endif
              </div>
              <div class="er-invitationMetrics" aria-label="Invitation status">
                <span><strong>{{ $performance->audience_attendances_count }}</strong> customers</span>
                @if($performance->invitation_scheduled_count)<span><strong>{{ $performance->invitation_scheduled_count }}</strong> scheduled</span>@endif
                @if($performance->invitation_issued_count)<span><strong>{{ $performance->invitation_issued_count }}</strong> sent</span>@endif
                @if($performance->invitation_held_count)<span><strong>{{ $performance->invitation_held_count }}</strong> held</span>@endif
                @if($performance->invitation_attention_count)<span class="er-invitationMetrics__attention"><strong>{{ $performance->invitation_attention_count }}</strong> need attention</span>@endif
                @if($performance->invitation_stopped_count)<span><strong>{{ $performance->invitation_stopped_count }}</strong> stopped</span>@endif
              </div>
            </article>
          @endforeach
        </div>
      </section>

      <section class="er-card">
        <h2 class="er-adminTitle">Import customers</h2>
        <p>Upload a CSV with an <code>email</code> column and optional <code>name</code> column. Maximum {{ config('encore.audience_imports.max_rows') }} rows.</p>
        <p><a href="{{ route('admin.audience-imports.template') }}">Download the CSV template</a></p>

        <form class="er-form" method="POST" action="{{ route('admin.audience-imports.store', $show) }}" enctype="multipart/form-data">
          @csrf
          <div class="er-field">
            <label for="performance_id">Event date</label>
            <select class="er-input" id="performance_id" name="performance_id" required>
              <option value="">Choose a date</option>
              @foreach($show->performances as $performance)
                <option value="{{ $performance->id }}" @selected(old('performance_id') === $performance->id)>{{ $performance->starts_at?->format('D j M Y, H:i') ?? 'Date to be confirmed' }}</option>
              @endforeach
            </select>
            @error('performance_id')<p class="er-fieldError">{{ $message }}</p>@enderror
          </div>

          <div class="er-field">
            <label for="customers_csv">Customer CSV</label>
            <input class="er-input" type="file" id="customers_csv" name="customers_csv" accept=".csv,text/csv" required>
            @error('customers_csv')<p class="er-fieldError">{{ $message }}</p>@enderror
          </div>

          <label class="er-choice er-authorityChoice">
            <input type="checkbox" name="attendance_confirmed" value="1" @checked(old('attendance_confirmed')) required>
            <span>I confirm these customers attended the selected performance and may be contacted for a post-event review under our privacy notice.</span>
          </label>
          @error('attendance_confirmed')<p class="er-fieldError">{{ $message }}</p>@enderror

          <div class="er-notice">
            Imported customer details are encrypted.
            @if($invitationIssuingEnabled)
              Each eligible customer will be scheduled for a single review invitation after the selected performance.
            @else
              Invitations will be held while automatic sending is paused.
            @endif
          </div>
          <button class="er-btn" type="submit">Import customers</button>
        </form>
      </section>
    </div>

    <section class="er-card er-adminRecent">
      <h2 class="er-adminTitle">Recent imports</h2>
      @if($show->audienceImports->isEmpty())
        <p>No customer CSV files have been imported.</p>
      @else
        <div class="er-adminTable er-importTable">
          <div class="er-adminTable__head">
            <span>Date imported</span><span>Performance</span><span>Imported</span><span>Skipped</span>
          </div>
          @foreach($show->audienceImports as $audienceImport)
            <div class="er-adminTable__row">
              <span>{{ $audienceImport->created_at?->format('j M Y, H:i') }}</span>
              <span>{{ $audienceImport->performance?->starts_at?->format('j M Y, H:i') ?? 'Unknown' }}</span>
              <span>{{ $audienceImport->rows_imported }}</span>
              <span>{{ $audienceImport->rows_skipped }}</span>
            </div>
          @endforeach
        </div>
      @endif
    </section>
  </div>
</section>
@endsection
