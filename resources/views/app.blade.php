<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FIFA League">
    <meta name="theme-color" content="#05070d">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&family=outfit:500,600,700,800,900&display=swap" rel="stylesheet"/>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/icons/icon-192.png">

    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Nucleo Icons -->
    <link href="/assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="/assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Popper -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <!-- Main Styling -->
    <link href="/assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
<div id="app-loader">
    <img src="/icons/icon-192.png" alt="">
    <div class="app-loader-spinner"></div>
</div>
<style>
    #app-loader {
        position: fixed; inset: 0; z-index: 9999;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px;
        background: #05070d;
        transition: opacity .35s ease-out;
    }
    #app-loader img { width: 110px; height: 110px; margin-top: -30px; }
    #app-loader.is-hidden { opacity: 0; pointer-events: none; }
    .app-loader-spinner {
        width: 32px; height: 32px;
        border: 3px solid rgba(255, 255, 255, .2); border-top-color: #fff; border-radius: 50%;
        animation: app-loader-spin .8s linear infinite;
    }
    @keyframes app-loader-spin { to { transform: rotate(360deg); } }
</style>
<script>
    // Never leave the loader up if the app fails to boot.
    setTimeout(() => document.getElementById('app-loader')?.remove(), 10000);
</script>
{{--<script src="/assets/js/sidenav-burger.js" async></script>--}}
{{--<script src="/assets/js/plugins/perfect-scrollbar.min.js" async></script>--}}
{{--<script src="/assets/js/argon-dashboard-tailwind.js?v=1.0.1" async></script>--}}
@inertia
</body>
</html>
