<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas_recoleccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camion_id')->constrained('camiones')->restrictOnDelete();
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('estado')->default('Planificada');
            $table->decimal('distancia_estimada_km', 8, 2)->default(0);
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('finalizada_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reporte_ruta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_recoleccion_id')->constrained('rutas_recoleccion')->cascadeOnDelete();
            $table->foreignId('reporte_id')->constrained('reportes')->restrictOnDelete();
            $table->unsignedInteger('orden');
            $table->decimal('distancia_desde_anterior_km', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['ruta_recoleccion_id', 'reporte_id']);
            $table->index('reporte_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_ruta');
        Schema::dropIfExists('rutas_recoleccion');
    }
};
