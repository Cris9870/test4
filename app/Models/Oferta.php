<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una Oferta es la propuesta de un vendedor para venderle al comprador el
 * objeto que pidió en su Anuncio.
 */
class Oferta extends Model
{
    use HasFactory;

    protected $table = 'ofertas';

    protected $fillable = [
        'anuncio_id',
        'user_id',
        'precio',
        'mensaje',
        'imagen_url',
        'estado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(Anuncio::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
