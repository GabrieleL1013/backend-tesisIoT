<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetricTemplate extends Model
{
    protected $table = 'metric_templates';
    
    protected $fillable = ['nombre', 'nombre_en', 'imagen'];

    public function subvariables(): HasMany
    {
        return $this->hasMany(SubvariableTemplate::class, 'metric_template_id');
    }
}
