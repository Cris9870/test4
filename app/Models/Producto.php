<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Producto extends Model
{
    use Searchable;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'precio',
        'stock',
        'imagen_url',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Nombre del indice en Meilisearch.
     */
    public function searchableAs(): string
    {
        return 'productos';
    }

    /**
     * Datos que se envian a Meilisearch al indexar.
     * 'categoria' viaja como atributo para poder filtrar por faceta.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria' => $this->categoria,
            'precio' => (float) $this->precio,
            'stock' => (int) $this->stock,
            'imagen_url' => $this->imagen_url,
        ];
    }
}
