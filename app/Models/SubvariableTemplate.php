<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

class SubvariableTemplate extends Model
{
    protected $table = 'subvariable_templates';

    protected $fillable = [
        'metric_template_id',
        'nombre',
        'unidad',
        'clave_mqtt',
        'min_expected',
        'max_expected',
        'icono'
    ];

    public function metricTemplate(): BelongsTo
    {
        return $this->belongsTo(MetricTemplate::class, 'metric_template_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'subvariable_id');
    }
}
