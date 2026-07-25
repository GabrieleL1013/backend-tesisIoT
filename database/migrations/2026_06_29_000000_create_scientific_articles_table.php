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
        Schema::create('scientific_articles', function (Blueprint $table) {
            $table->id();
            $table->string('titulo'); // Título del artículo científico
            $table->string('autores'); // Autores del artículo (ej. Dr. John Doe, Dr. Jane Smith)
            $table->string('revista')->nullable(); // Institución o revista (ej. ULEAM, IEEE Access)
            $table->text('resumen'); // Abstract / Resumen
            $table->string('palabras_clave')->nullable(); // Keywords
            
            // Secciones del artículo (para visualización en dos columnas)
            $table->text('introduccion')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('resultados')->nullable();
            $table->text('conclusiones')->nullable();
            $table->text('referencias')->nullable();
            
            $table->string('url_pdf')->nullable(); // Opcional, link a archivo PDF
            $table->string('estado')->default('Publicado'); // Borrador o Publicado
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scientific_articles');
    }
};
