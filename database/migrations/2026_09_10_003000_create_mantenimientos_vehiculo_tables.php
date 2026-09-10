<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 1000)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $ahora = now();
        DB::table('tipos_mantenimiento')->insert([
            ['nombre' => 'Preventivo', 'descripcion' => 'Revisión programada para conservar el vehículo en operación.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Correctivo', 'descripcion' => 'Reparación por falla o desperfecto detectado.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Cambio de aceite', 'descripcion' => 'Cambio de aceite y filtros del motor.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Sistema de frenos', 'descripcion' => 'Revisión o cambio de frenos, pastillas y fluido.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Motor y transmisión', 'descripcion' => 'Intervención del motor, caja o transmisión.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Llantas y suspensión', 'descripcion' => 'Cambio o alineación de llantas y suspensión.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Sistema eléctrico', 'descripcion' => 'Batería, luces, alternador u otros componentes eléctricos.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Carrocería', 'descripcion' => 'Soldadura, pintura o reparación de carrocería.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        Schema::create('mantenimientos_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camion_id')->constrained('camiones')->restrictOnDelete();
            $table->foreignId('tipo_mantenimiento_id')->constrained('tipos_mantenimiento')->restrictOnDelete();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo');
            $table->string('estado')->default('En curso');
            $table->string('estado_anterior')->nullable();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamps();

            $table->index(['camion_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos_vehiculo');
        Schema::dropIfExists('tipos_mantenimiento');
    }
};
