<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Paints the page before the stylesheet lands, so these must stay equal to
             --background in resources/css/app.css. They were the template's neutral
             white and near-black long after the tokens stopped being neutral, which
             flashed the wrong colour on every load. --}}
        <style>
            html {
                background-color: #f4f5f8;
            }

            html.dark {
                background-color: #131518;
            }
        </style>

        <meta name="description" content="Freelance outreach, end to end: the companies worth approaching, the letter and cover email written for each one, and what came back.">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Prospect">
        <meta property="og:title" content="Prospect">
        <meta property="og:description" content="The companies worth approaching, the letters written for each one, and what came back.">
        <meta name="twitter:card" content="summary">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.json">
        {{-- Was #FF2D20, Laravel's red, against this app's own blue icon. --}}
        <meta name="theme-color" content="#2563eb">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="Prospect">
        <script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){})})}</script>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
