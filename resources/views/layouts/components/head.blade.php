<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="author" content="{{ config('app.name') }}" />
<meta name="robots" content="noindex, nofollow" data-rh="true" />

@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ $title ?? config('app.name').' - AI Placement Test & 24/7 AI Tutor' }}</title>
@endif

@hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')" data-rh="true" />
@else
    <meta name="description"
        content="{{ $metaDescription ?? 'GLC AI Platform - AI English placement testing and a 24/7 curriculum-based AI tutor by Greats Language Center.' }}"
        data-rh="true" />
@endif

<link rel="canonical" href="@yield('canonical_url', strtok(url()->current(), '?'))" />

<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<meta name="theme-color" content="#ffffff" />

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicons/favicon-32x32.png" type="image/png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

@vite(['resources/css/app.css'])
@stack('styles')
