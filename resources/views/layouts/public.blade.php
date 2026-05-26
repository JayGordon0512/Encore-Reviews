<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>@yield('title', 'Encore Reviews — Powered by TicketPal')</title>
  <meta name="description" content="@yield('meta_description', 'Audience reviews for live events — powered by TicketPal.')">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="er-body">
<header class="er-header">
  <div class="er-container er-header__inner">
    <a class="er-header__brand" href="{{ route('home') }}">
      <img class="er-header__logo" src="{{ asset('assets/encore-logo-header-900x225.png') }}" alt="Encore Reviews">
    </a>

    <nav class="er-nav" aria-label="Primary">
      <a class="er-nav__link" href="{{ route('shows.index') }}">Shows</a>
      <a class="er-nav__link" href="{{ route('review.submit') }}">Submit review</a>
      <a class="er-nav__link" href="#footer">Contact</a>
    </nav>

    <button
      class="er-menuBtn"
      type="button"
      aria-label="Open menu"
      aria-controls="erMobileMenu"
      aria-expanded="false"
      data-er-menu-button
    ></button>
  </div>
  <div class="er-mobileMenu" id="erMobileMenu" data-er-mobile-menu>
    <div class="er-container er-mobileMenu__inner">
      <a class="er-mobileMenu__link" href="{{ route('shows.index') }}">Shows</a>
      <a class="er-mobileMenu__link" href="{{ route('review.submit') }}">Submit review</a>
      <a class="er-mobileMenu__link" href="#footer">Contact</a>
    </div>
  </div>
</header>
<main class="er-main">
  @yield('content')
</main>
</body>
</html>
