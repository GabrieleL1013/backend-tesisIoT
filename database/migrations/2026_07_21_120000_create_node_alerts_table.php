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
        Schema::create('node_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade');
            $table->enum('type', ['offline', 'instability']);
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['warning', 'critical'])->default('warning');
            $table->boolean('is_read')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['node_id', 'type', 'is_read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_alerts');
    }
};
