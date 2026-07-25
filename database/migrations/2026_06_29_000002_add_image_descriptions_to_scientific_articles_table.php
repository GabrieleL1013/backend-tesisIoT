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
        Schema::table('scientific_articles', function (Blueprint $table) {
            $table->string('introduccion_imagen_descripcion')->nullable(); // Pie de foto para la imagen de Introducción
            $table->string('metodologia_imagen_descripcion')->nullable(); // Pie de foto para la imagen de Metodología
            $table->string('resultados_imagen_descripcion')->nullable(); // Pie de foto para la imagen de Resultados
            $table->string('conclusiones_imagen_descripcion')->nullable(); // Pie de foto para la imagen de Conclusiones
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scientific_articles', function (Blueprint $table) {
            $table->dropColumn([
                'introduccion_imagen_descripcion',
                'metodologia_imagen_descripcion',
                'resultados_imagen_descripcion',
                'conclusiones_imagen_descripcion'
            ]);
        });
    }
};
