@extends('layouts.app')

@section('title', config('app.name') . ' — Tú pides, alguien te lo vende')

@section('content')
    @include('partials.estado')

    {{-- HERO --}}
    <section class="hero">
        <div class="hero-grid">
            <div class="hero-main">
                <span class="hero-tag">🔄 El marketplace <b>&nbsp;al revés</b></span>
                <h1>Publica lo que <em>quieres comprar</em>.<br>Quien lo tiene, te lo vende.</h1>
                <p>Aquí no publican los vendedores: publicas tú. Dices qué buscas y cuánto ofreces, y quien lo tiene guardado en casa te hace una oferta. Productos de todos los días, precio que tú decides.</p>
                <div class="hero-cta">
                    <a href="#resultados" class="primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="17" height="17"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
                        Explorar búsquedas
                    </a>
                    <a href="{{ route('publicar') }}" class="ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="17" height="17"><path d="M12 5v14M5 12h14"/></svg>
                        Publicar la mía
                    </a>
                </div>
            </div>

            <div class="hero-side">
                <h3>¿Cómo funciona?</h3>
                <div class="sub">Comprar al revés en 3 pasos</div>
                <div class="step">
                    <div class="num">1</div>
                    <div><b>Publica qué buscas</b><span>Sofá, bici, licuadora… lo que necesites.</span></div>
                </div>
                <div class="step">
                    <div class="num amb">2</div>
                    <div><b>Pones tu presupuesto</b><span>Tú decides cuánto ofreces en soles (S/).</span></div>
                </div>
                <div class="step">
                    <div class="num">3</div>
                    <div><b>Recibes ofertas y compras</b><span>Quien lo tiene en casa te lo vende.</span></div>
                </div>
            </div>
        </div>
    </section>

    {{-- STATS --}}
    <section class="stats">
        <div class="stats-row">
            <div class="stat">
                <div class="ic">🔎</div>
                <div><b>{{ number_format($total) }}</b><span>búsquedas que puedes responder</span></div>
            </div>
            <div class="stat">
                <div class="ic">✅</div>
                <div><b>100%</b><span>tú pones el precio</span></div>
            </div>
            <div class="stat">
                <div class="ic amb">⭐</div>
                <div><b>S/</b><span>todo en soles, sin comisiones</span></div>
            </div>
            <div class="stat">
                <div class="ic">🇵🇪</div>
                <div><b>Perú</b><span>de Lima a todo el país</span></div>
            </div>
        </div>
    </section>

    {{-- SECTION HEAD + BUSCADOR htmx EN VIVO --}}
    <section>
        <div class="sec-head">
            <div>
                @isset($categoriaActiva)
                    <h2>Búsquedas en <em>{{ $categoriaActiva->nombre }}</em></h2>
                    <p>Anuncios de compra en esta categoría. ¿Tienes alguno en casa? Hazle una oferta.</p>
                @else
                    <h2>Lo que <em>la gente busca</em> hoy</h2>
                    <p>Estas personas ya pusieron su presupuesto. ¿Tienes alguno guardado en casa? Hazle una oferta.</p>
                @endisset
            </div>
        </div>

        {{-- Buscador en vivo (htmx). No es <form>: evitamos recargas al pulsar Enter. --}}
        <div style="margin-bottom:18px" class="relative">
            <input id="q" name="q" type="search" autocomplete="off" value="{{ $q }}"
                   placeholder="Filtra en vivo: sofá, bici, licuadora, laptop…"
                   hx-get="{{ route('buscar') }}"
                   hx-target="#resultados"
                   hx-swap="innerHTML"
                   hx-trigger="input changed delay:300ms, keyup[key=='Enter'], search"
                   hx-include="#categoria-actual"
                   hx-indicator="#spinner"
                   class="w-full rounded-xl border border-[color:var(--borde)] bg-white px-4 py-3 text-[15px] text-[color:var(--tinta)] outline-none focus:border-verde">
            <span id="spinner" class="htmx-indicator" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--verde)">buscando…</span>
        </div>

        {{-- Contenedor que htmx reemplaza. SSR en el primer paint. --}}
        <div id="resultados">
            @include('partials.resultados')
        </div>
    </section>

    {{-- SELLER BANNER --}}
    <section class="seller-banner">
        <div class="sb-inner">
            <div class="sb-text">
                <span class="kick"><span class="ln"></span>Vende lo que ya no usas</span>
                <h2>Tu casa tiene <em>dinero guardado</em>.</h2>
                <p>Miles de compradores ya publicaron lo que quieren y cuánto pagan en soles. Encuentra una búsqueda que coincida con algo que tienes en casa y ciérrala hoy. El precio ya está puesto.</p>
                <div class="actions">
                    <a href="#resultados">Ver qué están buscando</a>
                </div>
            </div>
            <div class="sb-visual">
                <div>
                    <div class="font-display" style="font-size:40px;font-weight:900;color:var(--verde)">S/</div>
                    <p style="color:var(--tinta-suave);font-size:14px;max-width:220px">Alguien busca justo lo que tienes guardado. Tú solo aceptas el precio.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- TRUST STRIP --}}
    <section>
        <div class="trust">
            <div class="it">🔒 El comprador pone el precio</div>
            <div class="sep"></div>
            <div class="it">🚚 De Lima a todo el Perú</div>
            <div class="sep"></div>
            <div class="it">💸 Pagos con Yape / Plin</div>
            <div class="sep"></div>
            <div class="it">🤝 Trato directo comprador–vendedor</div>
        </div>
    </section>
@endsection
