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
            $table->text('introduccion_imagen')->nullable(); // Imagen para la sección de Introducción
            $table->text('metodologia_imagen')->nullable(); // Imagen para la sección de Metodología
            $table->text('resultados_imagen')->nullable(); // Imagen para la sección de Resultados
            $table->text('conclusiones_imagen')->nullable(); // Imagen para la sección de Conclusiones
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scientific_articles', function (Blueprint $table) {
            $table->dropColumn([
                'introduccion_imagen',
                'metodologia_imagen',
                'resultados_imagen',
                'conclusiones_imagen'
            ]);
        });
    }
};
