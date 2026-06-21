<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * Un Anuncio es una SOLICITUD DE COMPRA: el comprador publica lo que quiere
 * comprar y cuánto ofrece (presupuesto). Los vendedores responden con Ofertas.
 */
class Anuncio extends Model
{
    use HasFactory, Searchable;

    protected $table = 'anuncios';

    protected $fillable = [
        'user_id',
        'categoria_id',
        'titulo',
        'descripcion',
        'categoria',
        'presupuesto',
        'imagen_url',
        'estado',
        'ciudad',
    ];

    protected $casts = [
        'presupuesto' => 'decimal:2',
    ];

    /**
     * Mantiene 'busqueda' = texto normalizado (sin acentos, minúsculas) para
     * búsqueda insensible a tildes (Meili + fallback PG). Ver BuscadorAnuncios::fold().
     */
    protected static function booted(): void
    {
        static::saving(function (self $anuncio): void {
            $anuncio->busqueda = Str::of($anuncio->titulo . ' ' . $anuncio->descripcion . ' ' . $anuncio->categoria)
                ->ascii()->lower()->squish()->value();
        });
    }

    // searchableAs() NO se sobreescribe a propósito: el default de Scout es
    // config('scout.prefix').$this->getTable() => el índice respeta SCOUT_PREFIX.
    // Con SCOUT_PREFIX=lab139_ el índice pasa a llamarse "lab139_anuncios".

    public function comprador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoriaRel(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class);
    }

    /**
     * Solo los anuncios abiertos viven en el índice: al cerrarse (aceptar oferta),
     * Scout los des-indexa solo y desaparecen del muro de búsqueda.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->estado === 'abierto';
    }

    /**
     * Datos que viajan a Meilisearch. 'categoria' va como string para faceting.
     * Incluimos 'imagen_url' y 'ofertas_count' para que la tarjeta renderice desde
     * el hit de Meili sin tocar la BD.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'categoria' => $this->categoria,
            // Campo normalizado (sin acentos) para búsqueda insensible a tildes.
            'busqueda' => $this->busqueda,
            'presupuesto' => (float) $this->presupuesto,
            'imagen_url' => $this->imagen_url,
            'ciudad' => $this->ciudad,
            'estado' => $this->estado,
            'ofertas_count' => $this->ofertas()->count(),
        ];
    }
}
