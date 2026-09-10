<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rutas_recoleccion', function (Blueprint $table) {
            $table->json('geometria')->nullable()->after('distancia_estimada_km');
        });
    }

    public function down(): void
    {
        Schema::table('rutas_recoleccion', function (Blueprint $table) {
            $table->dropColumn('geometria');
        });
    }
};
