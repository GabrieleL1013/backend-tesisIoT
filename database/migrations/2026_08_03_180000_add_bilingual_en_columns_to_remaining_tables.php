<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── locations ──────────────────────────────────────────────────────────
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (!Schema::hasColumn('locations', 'nombre_en')) {
                    $table->string('nombre_en')->nullable()->after('nombre');
                }
                if (!Schema::hasColumn('locations', 'descripcion_en')) {
                    $table->text('descripcion_en')->nullable()->after('descripcion');
                }
            });
        }

        // ── metric_templates ───────────────────────────────────────────────────
        if (Schema::hasTable('metric_templates')) {
            Schema::table('metric_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('metric_templates', 'nombre_en')) {
                    $table->string('nombre_en')->nullable()->after('nombre');
                }
            });
        }

        // ── subvariable_templates ──────────────────────────────────────────────
        if (Schema::hasTable('subvariable_templates')) {
            Schema::table('subvariable_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('subvariable_templates', 'nombre_en')) {
                    $table->string('nombre_en')->nullable()->after('nombre');
                }
            });
        }

        // ── roles ──────────────────────────────────────────────────────────────
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'name_en')) {
                    $table->string('name_en')->nullable()->after('name');
                }
                if (!Schema::hasColumn('roles', 'description_en')) {
                    $table->text('description_en')->nullable()->after('description');
                }
            });
        }

        // ── node_alerts ────────────────────────────────────────────────────────
        if (Schema::hasTable('node_alerts')) {
            Schema::table('node_alerts', function (Blueprint $table) {
                if (!Schema::hasColumn('node_alerts', 'title_en')) {
                    $table->string('title_en')->nullable()->after('title');
                }
                if (!Schema::hasColumn('node_alerts', 'message_en')) {
                    $table->text('message_en')->nullable()->after('message');
                }
            });
        }

        // ── nodes ──────────────────────────────────────────────────────────────
        if (Schema::hasTable('nodes')) {
            Schema::table('nodes', function (Blueprint $table) {
                if (!Schema::hasColumn('nodes', 'nombre_en')) {
                    $table->string('nombre_en')->nullable()->after('nombre');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                $cols = array_filter(['nombre_en', 'descripcion_en'], fn($c) => Schema::hasColumn('locations', $c));
                if (!empty($cols)) $table->dropColumn(array_values($cols));
            });
        }
        if (Schema::hasTable('metric_templates')) {
            Schema::table('metric_templates', function (Blueprint $table) {
                if (Schema::hasColumn('metric_templates', 'nombre_en')) $table->dropColumn('nombre_en');
            });
        }
        if (Schema::hasTable('subvariable_templates')) {
            Schema::table('subvariable_templates', function (Blueprint $table) {
                if (Schema::hasColumn('subvariable_templates', 'nombre_en')) $table->dropColumn('nombre_en');
            });
        }
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                $cols = array_filter(['name_en', 'description_en'], fn($c) => Schema::hasColumn('roles', $c));
                if (!empty($cols)) $table->dropColumn(array_values($cols));
            });
        }
        if (Schema::hasTable('node_alerts')) {
            Schema::table('node_alerts', function (Blueprint $table) {
                $cols = array_filter(['title_en', 'message_en'], fn($c) => Schema::hasColumn('node_alerts', $c));
                if (!empty($cols)) $table->dropColumn(array_values($cols));
            });
        }
        if (Schema::hasTable('nodes')) {
            Schema::table('nodes', function (Blueprint $table) {
                if (Schema::hasColumn('nodes', 'nombre_en')) $table->dropColumn('nombre_en');
            });
        }
    }
};
