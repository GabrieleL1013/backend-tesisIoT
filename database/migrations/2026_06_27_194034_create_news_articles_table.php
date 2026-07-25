<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Título de la noticia o hallazgo científico
            $table->string('autor'); // Nombre del investigador responsable (ej: Dr. Willian Zamora)
            $table->text('contenido'); // Cuerpo extenso del artículo o boletín meteorológico
            $table->string('imagen_url')->nullable(); // Ruta o URL de la imagen de portada
            
            // Estado de publicación para control administrativo (Borrador o Publicado)
            $table->string('estado')->default('Publicado'); 
            
            $table->timestamps(); // Genera automáticamente las columnas created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};