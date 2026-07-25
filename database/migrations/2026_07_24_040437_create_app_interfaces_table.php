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
        Schema::create('app_interfaces', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('path');
            $table->text('description')->nullable();
            $table->json('allowed_roles')->nullable();
            $table->integer('min_level')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_interfaces');
    }
};
