@extends('layouts.public')

@section('title', 'Encore Reviews for live-event organisers')
@section('meta_description', 'Build audience trust, strengthen your reputation and learn from verified post-show reviews with Encore Reviews.')

@section('content')
<header
  class="er-hero er-hero--image er-organiserHero"
  style="background-image: url('{{ asset('assets/hero-show-bg.jpg') }}');"
>
  <div class="er-container">
    <div class="er-hero__content">
      <p class="er-hero__eyebrow">Encore Reviews for organisers</p>
      <h1 class="er-hero__title">Turn real audience experience into lasting trust</h1>
      <p class="er-hero__lead">
        Collect verified post-show feedback, build confidence in future ticket buyers and understand what your audiences value—without adding another manual task to show night.
      </p>
      <div class="er-hero__actions">
        <a class="er-btn" href="#benefits">See the benefits</a>
        <a class="er-btn er-btn--ghost" href="{{ route('organisers.create') }}">Create organiser account</a>
      </div>
    </div>
  </div>
</header>

<section class="er-organiserProof" aria-label="Encore Reviews principles">
  <div class="er-container er-organiserProof__grid">
    <p><strong>Verified</strong><span>Reviews connected to approved attendance evidence</span></p>
    <p><strong>Independent</strong><span>Review governance owned by Encore</span></p>
    <p><strong>Effortless</strong><span>Secure integration with your ticketing journey</span></p>
  </div>
</section>

<section class="er-section" id="benefits">
  <div class="er-container">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Why Encore</p>
      <h2 class="er-h2">A stronger reputation, built by the people who were there</h2>
      <p class="er-sectionIntro">Encore turns genuine audience experience into trusted proof, practical learning and better discovery for future customers.</p>
    </div>

    <div class="er-grid er-benefitGrid">
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">01</span>
        <h3>Reviews people can trust</h3>
        <p>Invitation-only reviews are tied to approved attendance evidence, giving prospective audiences greater confidence in what they read.</p>
      </article>
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">02</span>
        <h3>More useful audience feedback</h3>
        <p>Capture ratings, recommendation intent and thoughtful comments while the performance is still fresh in the audience’s mind.</p>
      </article>
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">03</span>
        <h3>Social proof for future sales</h3>
        <p>Build a credible body of audience opinion that helps future buyers discover your work and book with confidence.</p>
      </article>
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">04</span>
        <h3>Less manual administration</h3>
        <p>Encore owns the invitation and review journey, reducing the need to export lists, chase feedback or manage disconnected forms.</p>
      </article>
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">05</span>
        <h3>A reputation that keeps growing</h3>
        <p>Each approved review contributes to a durable show and organiser reputation that is not limited to one campaign or event date.</p>
      </article>
      <article class="er-card er-benefitCard">
        <span class="er-benefitCard__number">06</span>
        <h3>Insight without losing control</h3>
        <p>Learn what audiences respond to while TicketPal retains ownership of bookings, tickets, attendance and payments.</p>
      </article>
    </div>
  </div>
</section>

<section class="er-section er-section--alt">
  <div class="er-container er-trustSplit">
    <div>
      <p class="er-hero__eyebrow">Trust by design</p>
      <h2 class="er-h2">Clear ownership protects organisers and audiences</h2>
      <p class="er-sectionIntro">Encore complements your ticketing provider. It does not replace it, take ownership of transactions or blur responsibility for audience data.</p>
    </div>
    <div class="er-trustSplit__cards">
      <article class="er-card">
        <h3>TicketPal retains</h3>
        <ul class="er-checkList">
          <li>Bookings and tickets</li>
          <li>Attendance evidence</li>
          <li>Payments and fulfilment</li>
        </ul>
      </article>
      <article class="er-card er-card--brand">
        <h3>Encore owns</h3>
        <ul class="er-checkList">
          <li>Review invitations and reviewer identity</li>
          <li>Moderation and review records</li>
          <li>Reputation and audience-review data</li>
        </ul>
      </article>
    </div>
  </div>
</section>

<section class="er-section" id="how-encore-works">
  <div class="er-container">
    <div class="er-sectionHeader">
      <p class="er-hero__eyebrow">Simple by design</p>
      <h2 class="er-h2">How the journey works</h2>
    </div>
    <ol class="er-stepGrid">
      <li class="er-stepCard"><span>1</span><div><h3>Your event takes place</h3><p>TicketPal holds the booking, ticket and approved attendance context.</p></div></li>
      <li class="er-stepCard"><span>2</span><div><h3>Encore sends the invitation</h3><p>Eligible audience members receive a personal, single-use review link.</p></div></li>
      <li class="er-stepCard"><span>3</span><div><h3>The audience shares feedback</h3><p>Reviews are submitted directly to Encore and checked before publication.</p></div></li>
      <li class="er-stepCard"><span>4</span><div><h3>Your reputation grows</h3><p>Approved feedback strengthens discovery and creates a useful record of audience response.</p></div></li>
    </ol>
  </div>
</section>

<section class="er-section er-organiserCta" id="get-started">
  <div class="er-container er-organiserCta__inner">
    <div>
      <p class="er-hero__eyebrow">For TicketPal organisers</p>
      <h2 class="er-h2">Be among the first to build your Encore reputation</h2>
      <p>Create your Encore organiser account, or continue with your existing TicketPal organiser login.</p>
    </div>
    <a class="er-btn" href="{{ route('organisers.create') }}">Create organiser account</a>
  </div>
</section>
@endsection
