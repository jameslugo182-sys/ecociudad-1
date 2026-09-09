<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('rol');
        });

        Schema::create('camiones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('placa', 10)->unique();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->unsignedInteger('capacidad_kg')->nullable();
            $table->string('estado')->default('Disponible');
            $table->timestamps();
        });

        Schema::create('camion_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camion_id')->constrained('camiones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('puesto');
            $table->timestamps();

            $table->unique('user_id');
            $table->unique(['camion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camion_user');
        Schema::dropIfExists('camiones');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
