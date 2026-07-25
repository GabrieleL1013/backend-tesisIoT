<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('data_type'); // La clave exacta del JSON (Ej: pm25, temp_env, co2)
            $table->string('tipo_variable'); // Descripción UX (Ej: Materia Particulada 2.5)
            $table->string('unidad'); // Unidad de medida (Ej: µg/m³, °C, %, pH)
            
            // Una métrica pertenece a un sensor específico
            $table->foreignId('sensor_id')->constrained('sensors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};