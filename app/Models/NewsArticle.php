<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $table = 'news_articles';

    protected $fillable = [
        'titulo',
        'autor',
        'contenido',
        'imagen_url',
        'estado'
    ];
}
