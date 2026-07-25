<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'nombre',
        'descripcion',
        'latitud',
        'longitud'
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class, 'location_id');
    }
}
