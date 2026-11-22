<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $guarded = [];

    protected $casts = [
        'mesa_id' => 'integer',
        'subtotal' => 'float',
        'total' => 'float',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'pedido_id');
    }
}
