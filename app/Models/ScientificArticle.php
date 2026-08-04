<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScientificArticle extends Model
{
    protected $table = 'scientific_articles';

    protected $fillable = [
        'titulo',
        'titulo_en',
        'autores',
        'revista',
        'resumen',
        'resumen_en',
        'palabras_clave',
        'palabras_clave_en',
        'introduccion',
        'introduccion_en',
        'introduccion_imagen',
        'introduccion_imagen_descripcion',
        'introduccion_imagen_descripcion_en',
        'metodologia',
        'metodologia_en',
        'metodologia_imagen',
        'metodologia_imagen_descripcion',
        'metodologia_imagen_descripcion_en',
        'resultados',
        'resultados_en',
        'resultados_imagen',
        'resultados_imagen_descripcion',
        'resultados_imagen_descripcion_en',
        'conclusiones',
        'conclusiones_en',
        'conclusiones_imagen',
        'conclusiones_imagen_descripcion',
        'conclusiones_imagen_descripcion_en',
        'referencias',
        'url_pdf',
        'estado',
        'tipo_registro'
    ];
}
