<!DOCTYPE html>
<html lang="es-PE" class="reversa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name') . ' — Tú pides, alguien te lo vende')</title>

    {{-- Fuentes del diseño Reversa (Fontshare) --}}
    <link rel="preconnect" href="https://api.fontshare.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800,900,500,700&f[]=hanken-grotesk@400,500,600,700,800&display=swap" rel="stylesheet">

    {{-- Tailwind v4 + CSS Reversa compilado por Vite en el mini-server (public/build) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- htmx: interacciones que tocan el servidor (por CDN, NO via Vite) --}}
    <script src="https://unpkg.com/htmx.org@2.0.4/dist/htmx.min.js"></script>
    {{-- Alpine.js: interacciones SOLO de cliente (por CDN, NO via Vite) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body>

{{-- UTILITY BAR --}}
<div class="util">
    <div class="wrap">
        <div class="util-left">
            <span class="util-claim"><span class="dot"></span>Al revés: <b>&nbsp;tú pones el precio, alguien te lo vende</b></span>
        </div>
        <div class="util-right">
            <a href="{{ route('subir') }}">Subir imagen</a>
            @auth
                <a href="{{ route('cuenta') }}">Hola, {{ str(auth()->user()->name)->before(' ') }}</a>
            @else
                <a href="{{ route('login') }}">Ingresar</a>
            @endauth
        </div>
    </div>
</div>

{{-- HEADER --}}
<header class="site-head">
    <div class="wrap head-main">
        <a href="{{ route('home') }}" class="logo">
            <div class="mark"><span>R</span></div>
            <div class="word">Rever<i>sa</i><small>tú pides · te lo venden</small></div>
        </a>

        {{-- Buscador del header: GET a la home (funciona en cualquier página) --}}
        <form action="{{ route('home') }}" method="get" class="search" role="search">
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="¿Qué necesitas comprar? Ej: sofá, bici, licuadora…">
            <button class="go" aria-label="Buscar">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
            </button>
        </form>

        <div class="head-actions">
            @auth
                @if (auth()->user()->es_admin)
                    <a href="{{ route('admin.dashboard') }}" class="h-link">⚙ Admin</a>
                @endif
                <a href="{{ route('cuenta') }}" class="h-link">Mi cuenta</a>
                <form action="{{ route('logout') }}" method="post">@csrf
                    <button class="h-link" type="submit">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="h-link">Ingresar</a>
            @endauth
            <a href="{{ route('publicar') }}" class="btn-pub">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
                Publicar búsqueda
            </a>
        </div>
    </div>

    <nav class="depts">
        <div class="wrap">
            <a href="{{ route('home') }}" class="all">Departamentos</a>
            <a href="{{ route('categoria.show', 'hogar-y-muebles') }}">Hogar y muebles</a>
            <a href="{{ route('categoria.show', 'electrodomesticos') }}">Electrodomésticos</a>
            <a href="{{ route('categoria.show', 'tecnologia') }}">Tecnología</a>
            <a href="{{ route('categoria.show', 'bebes') }}">Bebés</a>
            <a href="{{ route('categoria.show', 'deportes') }}">Deportes</a>
            <a href="{{ route('categoria.show', 'herramientas') }}">Herramientas</a>
            <a href="{{ route('categoria.show', 'oficina') }}">Oficina</a>
            <a href="{{ route('categoria.show', 'cocina') }}">Cocina</a>
        </div>
    </nav>
</header>

@if (session('ok'))
    <div class="wrap" style="padding-top:18px">
        <div class="flash-ok">✅ {{ session('ok') }}</div>
    </div>
@endif

<main class="wrap" style="padding-top:24px;padding-bottom:24px">
    @yield('content')
</main>

{{-- FOOTER --}}
<footer class="site-foot">
    <div class="wrap">
        <div class="foot-grid">
            <div class="foot-brand">
                <a href="{{ route('home') }}" class="logo">
                    <div class="mark"><span>R</span></div>
                    <div class="word">Rever<i>sa</i></div>
                </a>
                <p>El marketplace al revés: tú publicas lo que quieres comprar y cuánto ofreces. Quien lo tiene guardado en casa te lo vende.</p>
            </div>
            <div class="foot-col">
                <h4>Comprar</h4>
                <a href="{{ route('publicar') }}">Publicar una búsqueda</a>
                <a href="{{ route('home') }}">Explorar búsquedas</a>
                <a href="{{ route('categoria.show', 'tecnologia') }}">Categorías</a>
            </div>
            <div class="foot-col">
                <h4>Vender</h4>
                <a href="{{ route('home') }}">Qué están buscando</a>
                <a href="{{ route('registro') }}">Empezar a vender</a>
            </div>
            <div class="foot-col">
                <h4>Reversa</h4>
                <a href="{{ route('cuenta') }}">Mi cuenta</a>
                <a href="{{ route('login') }}">Ingresar</a>
            </div>
        </div>
        <div class="foot-bottom">
            <span>© {{ date('Y') }} Reversa Perú · Hecho en Lima 🇵🇪 · Precios en soles (S/)</span>
            <div class="pays">
                <span>Yape</span><span>Plin</span><span>VISA</span><span>MASTERCARD</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
