<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $guarded = [];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}
