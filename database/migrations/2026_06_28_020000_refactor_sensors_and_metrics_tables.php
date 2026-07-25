<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables in order of dependencies
        Schema::dropIfExists('metrics');
        Schema::dropIfExists('sensors');

        // Create unified flat metrics table directly associated with nodes
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade');
            $table->string('sensor_nombre'); // Ej: DHT22 o Estación Meteorológica
            $table->string('tipo_variable'); // Ej: Temperatura, Humedad
            $table->string('unidad'); // Ej: °C, %
            $table->string('data_type'); // Clave JSON MQTT (Ej: temp, hum)
            $table->boolean('estado')->default(true); // Estado del sensor/métrica
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
