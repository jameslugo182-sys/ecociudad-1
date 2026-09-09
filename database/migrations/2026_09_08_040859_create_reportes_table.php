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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            // Relación con el ciudadano que hace el reporte
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos capturados por el ciudadano (RF-01)
            $table->string('foto_path')->nullable();
            $table->decimal('latitud', 10, 8); // Coordenadas GPS
            $table->decimal('longitud', 11, 8);
            $table->text('descripcion');

            // Datos gestionados por el sistema y la IA (RF-05, RF-06, RF-13)
            $table->string('estado')->default('Pendiente');
            $table->string('tipo_incidencia')->nullable();
            $table->integer('prioridad')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
