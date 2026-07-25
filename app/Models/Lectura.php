<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'node_id',
        'subvariable_id',
        'valor'
    ];

    /**
     * Relación con el Nodo.
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    /**
     * Relación con la Subvariable (Métrica individual).
     */
    public function subvariable(): BelongsTo
    {
        return $this->belongsTo(SubvariableTemplate::class, 'subvariable_id');
    }
}
