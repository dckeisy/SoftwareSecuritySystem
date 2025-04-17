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
        Schema::create('role_entity_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            // Foreign key restrictions
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            
            // Ensure that the combination is unique
            $table->unique(['role_id', 'entity_id', 'permission_id'], 'role_entity_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_entity_permission');
    }
};
