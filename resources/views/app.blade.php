<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ data_get($page, 'props.settings.site_name', config('app.name', 'Laravel')) }}</title>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-3ZDWMNDKGT"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-3ZDWMNDKGT');
    </script>

    <!-- Favicon and Icons -->
    <link rel="icon" type="image/x-icon" href="/images/logo.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/logo.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.ico">
    <meta name="theme-color" content="#667eea">

    @php $useLocalAssets = config('app.local_assets', false); @endphp

    @if($useLocalAssets)
        <!-- Self-hosted assets for faster loading -->
        <link rel="preload" href="{{ asset('vendor/bootstrap.min.css') }}" as="style">
        <link rel="preload" href="{{ asset('fonts/inter.css') }}" as="style">

        <!-- Fonts -->
        <link href="{{ asset('fonts/inter.css') }}" rel="stylesheet">

        <!-- Bootstrap CSS -->
        <link href="{{ asset('vendor/bootstrap.min.css') }}" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome.min.css') }}" media="print" onload="this.media='all'" />
    @else
        <!-- CDN assets -->
        <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

        <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">

        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" media="print"
            onload="this.media='all'" />
        <noscript>
            <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        </noscript>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
            media="print" onload="this.media='all'" />
        <noscript>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        </noscript>
    @endif

    <!-- Preload first banner image if available (homepage only) -->
    @if(request()->is('/') && !empty($page['props']['banners']))
        @php $firstBanner = is_array($page['props']['banners'][0]) ? $page['props']['banners'][0]['image'] : $page['props']['banners'][0]->image ?? null; @endphp
        @if($firstBanner)
            <link rel="preload" href="{{ $firstBanner }}" as="image" fetchpriority="high">
        @endif
    @endif

    <!-- Conditional CSS: Admin vs Frontend -->
    @if(str_starts_with(request()->path(), 'admin'))
        <link rel="stylesheet" href="{{ asset('css/admin-apple-style.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <!-- Bootstrap JS -->
    @if($useLocalAssets)
        <script src="{{ asset('vendor/bootstrap.bundle.min.js') }}" defer></script>
    @else
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    @endif
</body>

</html>