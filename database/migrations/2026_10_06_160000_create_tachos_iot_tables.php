<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tachos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->unsignedSmallInteger('distancia_vacio_cm')->default(75);
            $table->unsignedSmallInteger('distancia_lleno_cm')->default(5);
            $table->unsignedTinyInteger('umbral_alerta')->default(80);
            $table->unsignedTinyInteger('nivel_porcentaje')->nullable();
            $table->decimal('distancia_cm', 6, 1)->nullable();
            $table->string('estado')->default('Operativo');
            $table->boolean('alerta_activa')->default(false);
            $table->string('token_hash');
            $table->timestamp('ultima_lectura_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lecturas_tacho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tacho_id')->constrained('tachos')->cascadeOnDelete();
            $table->decimal('distancia_cm', 6, 1)->nullable();
            $table->unsignedTinyInteger('nivel_porcentaje');
            $table->timestamps();

            $table->index(['tacho_id', 'created_at']);
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->foreignId('tacho_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tachos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tacho_id');
        });
        Schema::dropIfExists('lecturas_tacho');
        Schema::dropIfExists('tachos');
    }
};
