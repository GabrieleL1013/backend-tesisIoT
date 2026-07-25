<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subvariable_templates', function (Blueprint $table) {
            $table->string('icono')->nullable()->after('max_expected');
        });
    }

    public function down(): void
    {
        Schema::table('subvariable_templates', function (Blueprint $table) {
            $table->dropColumn('icono');
        });
    }
};
