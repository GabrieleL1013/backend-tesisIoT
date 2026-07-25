<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Estación Meteorológica FCVT
            $table->string('serial_number')->unique(); // ID Único MQTT (Ej: ESP32-AQI-99A1)
            $table->string('categoria'); // Aquí se guarda "Manta", "Calidad del Aire", "Acuicultura"
            
            // Relación con Ubicaciones (Si se borra la ubicación, se restringe para evitar huérfanos)
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            
            $table->boolean('estado')->default(true); // Activo / Inactivo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};