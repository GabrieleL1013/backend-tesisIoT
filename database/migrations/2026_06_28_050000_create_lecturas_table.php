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
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_id');
            $table->unsignedBigInteger('subvariable_id');
            $table->double('valor');
            $table->timestamps();

            // Relaciones foráneas indexadas con borrado en cascada
            $table->foreign('node_id')->references('id')->on('nodes')->onDelete('cascade');
            $table->foreign('subvariable_id')->references('id')->on('subvariable_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};
