<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $table = 'news_articles';

    protected $fillable = [
        'titulo',
        'titulo_en',
        'autor',
        'contenido',
        'contenido_en',
        'imagen_url',
        'estado'
    ];
}
