<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: DHT22 o MQ135
            $table->boolean('estado')->default(true);
            
            // Un nodo físico contiene múltiples sensores
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};