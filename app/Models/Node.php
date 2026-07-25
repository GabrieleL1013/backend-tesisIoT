<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $table = 'nodes';

    protected $fillable = [
        'nombre',
        'serial_number',
        'categoria',
        'location_id',
        'estado',
        'broker',
        'port',
        'topic_data',
        'client_id',
        'username',
        'password',
        'location_slug',
        'use_mqtt_v5',
        'is_simulated',
        'save_frequency',
        'instability_alert_interval'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'use_mqtt_v5' => 'boolean',
        'is_simulated' => 'boolean'
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function subvariables(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(SubvariableTemplate::class, 'node_subvariable_template', 'node_id', 'subvariable_template_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'node_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(NodeAlert::class, 'node_id');
    }
}
