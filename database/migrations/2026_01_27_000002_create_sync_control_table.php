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
        Schema::create('sync_control', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 100);
            $table->integer('ultimo_id_sincronizado')->default(0);
            $table->timestamp('ultima_sincronizacion')->nullable();
            $table->string('sede', 50);
            
            $table->unique(['tabla', 'sede']);
            $table->index('sede');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_control');
    }
};
