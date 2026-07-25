<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Campus Central ULEAM
            $table->string('descripcion')->nullable();
            $table->decimal('latitud', 10, 8); // Coordenadas GPS para el mapa
            $table->decimal('longitud', 11, 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};