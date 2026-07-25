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
        Schema::table('nodes', function (Blueprint $table) {
            $table->string('broker')->nullable()->after('categoria');
            $table->integer('port')->nullable()->after('broker');
            $table->string('topic_data')->nullable()->after('port');
            $table->string('client_id')->nullable()->after('topic_data');
            $table->string('username')->nullable()->after('client_id');
            $table->string('password')->nullable()->after('username');
            $table->string('location_slug')->nullable()->after('password');
            $table->boolean('use_mqtt_v5')->default(false)->after('location_slug');
            $table->boolean('is_simulated')->default(false)->after('use_mqtt_v5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn([
                'broker',
                'port',
                'topic_data',
                'client_id',
                'username',
                'password',
                'location_slug',
                'use_mqtt_v5',
                'is_simulated'
            ]);
        });
    }
};
