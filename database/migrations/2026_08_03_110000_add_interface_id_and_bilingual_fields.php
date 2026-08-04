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
        if (Schema::hasTable('app_interfaces')) {
            Schema::table('app_interfaces', function (Blueprint $table) {
                if (!Schema::hasColumn('app_interfaces', 'name_es')) {
                    $table->string('name_es')->nullable()->after('name');
                }
                if (!Schema::hasColumn('app_interfaces', 'name_en')) {
                    $table->string('name_en')->nullable()->after('name_es');
                }
                if (!Schema::hasColumn('app_interfaces', 'path_es')) {
                    $table->string('path_es')->nullable()->after('path');
                }
                if (!Schema::hasColumn('app_interfaces', 'path_en')) {
                    $table->string('path_en')->nullable()->after('path_es');
                }
            });
        }

        if (Schema::hasTable('interface_texts')) {
            Schema::table('interface_texts', function (Blueprint $table) {
                if (!Schema::hasColumn('interface_texts', 'interface_id')) {
                    $table->foreignId('interface_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('app_interfaces')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('interface_texts') && Schema::hasColumn('interface_texts', 'interface_id')) {
            Schema::table('interface_texts', function (Blueprint $table) {
                $table->dropForeign(['interface_id']);
                $table->dropColumn('interface_id');
            });
        }

        if (Schema::hasTable('app_interfaces')) {
            Schema::table('app_interfaces', function (Blueprint $table) {
                $cols = ['name_es', 'name_en', 'path_es', 'path_en'];
                $columns = array_filter($cols, fn($col) => Schema::hasColumn('app_interfaces', $col));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
