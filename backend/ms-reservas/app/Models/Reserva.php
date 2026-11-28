<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $guarded = [];

    protected $casts = [
        'cantidad_personas' => 'integer',
        'mesa_id' => 'integer',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}
