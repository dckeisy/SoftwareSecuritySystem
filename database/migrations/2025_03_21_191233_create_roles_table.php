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
    public function up() {
        Schema::create('roles', function(Blueprint $table){
            $table->id();
            $table->string('name')->unique();
            // Lista de permisos, separados por coma (ej. "crear,editar,borrar,ver_reportes")
            $table->string('permissions'); 
            $table->timestamps();
        });

        // Inserta los roles por defecto
        DB::table('roles')->insert([
            ['name' => 'SuperAdmin', 'permissions' => 'crear,editar,borrar,ver_reportes', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Auditor', 'permissions' => 'ver_reportes', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Registrador', 'permissions' => 'crear,editar,borrar', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
