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
        Schema::dropIfExists('metrics');

        Schema::create('node_subvariable_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade');
            $table->foreignId('subvariable_template_id')->constrained('subvariable_templates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_subvariable_template');

        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade');
            $table->string('sensor_nombre');
            $table->string('tipo_variable');
            $table->string('unidad');
            $table->string('data_type');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }
};
