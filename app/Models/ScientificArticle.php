<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScientificArticle extends Model
{
    protected $table = 'scientific_articles';

    protected $fillable = [
        'titulo',
        'autores',
        'revista',
        'resumen',
        'palabras_clave',
        'introduccion',
        'introduccion_imagen',
        'introduccion_imagen_descripcion',
        'metodologia',
        'metodologia_imagen',
        'metodologia_imagen_descripcion',
        'resultados',
        'resultados_imagen',
        'resultados_imagen_descripcion',
        'conclusiones',
        'conclusiones_imagen',
        'conclusiones_imagen_descripcion',
        'referencias',
        'url_pdf',
        'estado',
        'tipo_registro'
    ];
}
