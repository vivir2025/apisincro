<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración debe ejecutarse en las 3 bases de datos
     */
    public function up(): void
    {
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 100)->index();
            $table->integer('registro_id')->index();
            $table->enum('operacion', ['INSERT', 'UPDATE', 'DELETE']);
            $table->text('datos_json');
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->boolean('sincronizado')->default(false)->index();
            $table->string('sede', 50)->index();
            $table->string('hash_cambio', 64)->unique();
            $table->integer('usuario_id')->nullable();
            
            $table->index(['sincronizado', 'sede', 'fecha_cambio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_log');
    }
};
