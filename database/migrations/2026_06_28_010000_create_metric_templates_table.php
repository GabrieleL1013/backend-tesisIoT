<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Estación Meteorológica, Sensor de Suelo
            $table->text('imagen')->nullable();
            $table->timestamps();
        });

        Schema::create('subvariable_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metric_template_id')->constrained('metric_templates')->onDelete('cascade');
            $table->string('nombre'); // Ej: Temperatura, Humedad
            $table->string('unidad'); // Ej: °C, %
            $table->string('clave_mqtt'); // Ej: temp, hum
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subvariable_templates');
        Schema::dropIfExists('metric_templates');
    }
};
