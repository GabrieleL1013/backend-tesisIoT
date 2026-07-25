<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Insert Superusuario role
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Superusuario',
            'description' => 'Rol principal con acceso total al sistema',
            'color' => '#10b981',
            'level_permission' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Add role_id to users and set it to the new role for existing users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
        });

        DB::table('users')->update(['role_id' => $roleId]);

        // 3. Make role_id non-nullable, add foreign key constraint, and drop old 'rol' column
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
            $table->dropColumn('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->string('rol')->default('Técnico de Soporte');
        });

        DB::table('roles')->where('name', 'Superusuario')->delete();
    }
};
