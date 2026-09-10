<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas_recoleccion', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('geometria');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
            $table->json('dias_recoleccion')->nullable()->after('hora_fin');
            $table->string('horario_nota', 160)->nullable()->after('dias_recoleccion');
        });
    }

    public function down(): void
    {
        Schema::table('rutas_recoleccion', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio', 'hora_fin', 'dias_recoleccion', 'horario_nota']);
        });
    }
};
