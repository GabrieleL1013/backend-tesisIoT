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
        Schema::create('interface_images', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('image_data')->nullable(); // Base64 encoded image
            $table->string('mime_type')->default('image/jpeg'); // MIME type of the image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interface_images');
    }
};
