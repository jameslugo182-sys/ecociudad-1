<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('roles_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('codigo')->unique();
            $table->text('descripcion')->nullable();
            $table->json('modulos')->nullable();
            $table->boolean('protegido')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 15)->unique();
            $table->string('nombres');
            $table->string('apellido_paterno');
            $table->string('apellido_materno')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->foreignId('cargo_id')->constrained('cargos')->restrictOnDelete();
            $table->string('numero')->unique();
            $table->string('tipo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('remuneracion', 10, 2)->nullable();
            $table->unsignedSmallInteger('jornada_horas')->nullable();
            $table->string('area');
            $table->string('sede')->nullable();
            $table->string('documento_path')->nullable();
            $table->string('estado')->default('Vigente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_fin']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('colaborador_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('colaboradores')
                ->nullOnDelete();
        });

        $ahora = now();

        DB::table('cargos')->insert([
            ['nombre' => 'Conductor', 'descripcion' => 'Conducción de vehículos recolectores.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Recolector', 'descripcion' => 'Recolección y limpieza de residuos sólidos.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Gestor municipal', 'descripcion' => 'Revisión y gestión operativa de incidencias.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        DB::table('roles_sistema')->insert([
            [
                'nombre' => 'Personal municipal',
                'codigo' => 'personal_municipal',
                'descripcion' => 'Gestiona incidencias, rutas e indicadores.',
                'modulos' => json_encode(['incidencias', 'rutas', 'reportes']),
                'protegido' => true,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Área de limpieza',
                'codigo' => 'area_limpieza',
                'descripcion' => 'Consulta operaciones y registra evidencias.',
                'modulos' => json_encode(['operaciones']),
                'protegido' => true,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('colaborador_id');
        });

        Schema::dropIfExists('contratos');
        Schema::dropIfExists('colaboradores');
        Schema::dropIfExists('roles_sistema');
        Schema::dropIfExists('cargos');
    }
};
