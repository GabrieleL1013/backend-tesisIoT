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
        if (Schema::hasTable('interface_texts') && !Schema::hasColumn('interface_texts', 'text_en')) {
            Schema::table('interface_texts', function (Blueprint $table) {
                $table->text('text_en')->nullable()->after('text');
            });
        }

        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'nombre_en')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('nombre_en')->nullable()->after('nombre');
            });
        }

        if (Schema::hasTable('news_articles')) {
            Schema::table('news_articles', function (Blueprint $table) {
                if (!Schema::hasColumn('news_articles', 'titulo_en')) {
                    $table->string('titulo_en')->nullable()->after('titulo');
                }
                if (!Schema::hasColumn('news_articles', 'contenido_en')) {
                    $table->text('contenido_en')->nullable()->after('contenido');
                }
            });
        }

        if (Schema::hasTable('scientific_articles')) {
            Schema::table('scientific_articles', function (Blueprint $table) {
                if (!Schema::hasColumn('scientific_articles', 'titulo_en')) {
                    $table->string('titulo_en')->nullable()->after('titulo');
                }
                if (!Schema::hasColumn('scientific_articles', 'resumen_en')) {
                    $table->text('resumen_en')->nullable()->after('resumen');
                }
                if (!Schema::hasColumn('scientific_articles', 'palabras_clave_en')) {
                    $table->string('palabras_clave_en')->nullable()->after('palabras_clave');
                }
                if (!Schema::hasColumn('scientific_articles', 'introduccion_en')) {
                    $table->text('introduccion_en')->nullable()->after('introduccion');
                }
                if (!Schema::hasColumn('scientific_articles', 'introduccion_imagen_descripcion_en')) {
                    $table->text('introduccion_imagen_descripcion_en')->nullable()->after('introduccion_imagen_descripcion');
                }
                if (!Schema::hasColumn('scientific_articles', 'metodologia_en')) {
                    $table->text('metodologia_en')->nullable()->after('metodologia');
                }
                if (!Schema::hasColumn('scientific_articles', 'metodologia_imagen_descripcion_en')) {
                    $table->text('metodologia_imagen_descripcion_en')->nullable()->after('metodologia_imagen_descripcion');
                }
                if (!Schema::hasColumn('scientific_articles', 'resultados_en')) {
                    $table->text('resultados_en')->nullable()->after('resultados');
                }
                if (!Schema::hasColumn('scientific_articles', 'resultados_imagen_descripcion_en')) {
                    $table->text('resultados_imagen_descripcion_en')->nullable()->after('resultados_imagen_descripcion');
                }
                if (!Schema::hasColumn('scientific_articles', 'conclusiones_en')) {
                    $table->text('conclusiones_en')->nullable()->after('conclusiones');
                }
                if (!Schema::hasColumn('scientific_articles', 'conclusiones_imagen_descripcion_en')) {
                    $table->text('conclusiones_imagen_descripcion_en')->nullable()->after('conclusiones_imagen_descripcion');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('interface_texts') && Schema::hasColumn('interface_texts', 'text_en')) {
            Schema::table('interface_texts', function (Blueprint $table) {
                $table->dropColumn('text_en');
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'nombre_en')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('nombre_en');
            });
        }

        if (Schema::hasTable('news_articles')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $columns = array_filter(['titulo_en', 'contenido_en'], fn($col) => Schema::hasColumn('news_articles', $col));
                if (!empty($columns)) $table->dropColumn($columns);
            });
        }

        if (Schema::hasTable('scientific_articles')) {
            Schema::table('scientific_articles', function (Blueprint $table) {
                $cols = ['titulo_en', 'resumen_en', 'palabras_clave_en', 'introduccion_en', 'introduccion_imagen_descripcion_en', 'metodologia_en', 'metodologia_imagen_descripcion_en', 'resultados_en', 'resultados_imagen_descripcion_en', 'conclusiones_en', 'conclusiones_imagen_descripcion_en'];
                $columns = array_filter($cols, fn($col) => Schema::hasColumn('scientific_articles', $col));
                if (!empty($columns)) $table->dropColumn($columns);
            });
        }
    }
};
