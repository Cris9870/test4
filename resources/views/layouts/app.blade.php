<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>

    {{-- Tailwind compilado por Vite en el mini-server (public/build) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- htmx: interacciones que tocan el servidor (por CDN, NO via Vite) --}}
    <script src="https://unpkg.com/htmx.org@2.0.4/dist/htmx.min.js"></script>

    {{-- Alpine.js: interacciones SOLO de cliente (por CDN, NO via Vite) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-lg text-indigo-600">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">🛍️</span>
                {{ config('app.name') }}
            </a>
            <nav class="ml-auto flex items-center gap-3 text-sm">
                <span class="hidden text-xs text-slate-400 md:inline">Laravel {{ app()->version() }} · Blade SSR</span>
                @auth
                    <a href="{{ route('cuenta') }}" class="text-slate-600 hover:text-indigo-600">Mi cuenta</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-slate-500 hover:text-rose-600">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-indigo-600">Ingresar</a>
                    <a href="{{ route('registro') }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-700">Crear cuenta</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    <footer class="max-w-6xl mx-auto px-4 py-8 text-center text-xs text-slate-400">
        Test de stack Laravel + Blade + htmx + Alpine + Meilisearch sobre Plesk.
    </footer>
</body>
</html>
