<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaboradores', function (Blueprint $table) {
            $table->string('sexo', 20)->nullable()->after('apellido_materno');
            $table->string('estado_civil', 30)->nullable()->after('sexo');
            $table->string('nacionalidad', 80)->nullable()->after('estado_civil');
            $table->string('departamento', 80)->nullable()->after('direccion');
            $table->string('provincia', 80)->nullable()->after('departamento');
            $table->string('distrito', 80)->nullable()->after('provincia');
            $table->string('contacto_emergencia')->nullable()->after('distrito');
            $table->string('telefono_emergencia', 20)->nullable()->after('contacto_emergencia');
            $table->string('nivel_educativo', 40)->nullable()->after('telefono_emergencia');
            $table->string('institucion_estudios')->nullable()->after('nivel_educativo');
            $table->string('especialidad')->nullable()->after('institucion_estudios');
            $table->string('grado_titulo')->nullable()->after('especialidad');
            $table->unsignedSmallInteger('anio_egreso')->nullable()->after('grado_titulo');
        });

        Schema::create('areas_unidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 1000)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('sedes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion', 1000)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        $ahora = now();
        DB::table('areas_unidad')->insert([
            ['nombre' => 'Limpieza pública', 'descripcion' => 'Recolección y barrido de vías.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Operaciones', 'descripcion' => 'Gestión operativa de flota y rutas.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Administración', 'descripcion' => 'Área administrativa y de contratos.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
        DB::table('sedes_trabajo')->insert([
            ['nombre' => 'Base municipal El Tambo', 'descripcion' => 'Sede principal de operaciones.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Municipalidad de Huancayo', 'descripcion' => 'Sede institucional.', 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);

        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('jornada_horas')->constrained('areas_unidad')->restrictOnDelete();
            $table->foreignId('sede_id')->nullable()->after('area_id')->constrained('sedes_trabajo')->nullOnDelete();
        });

        Schema::create('plantillas_horario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion', 500)->nullable();
            $table->json('turnos');
            $table->json('dias');
            $table->decimal('horas_semanales', 6, 2)->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('horarios_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colaborador_id')->constrained('colaboradores')->restrictOnDelete();
            $table->foreignId('plantilla_horario_id')->nullable()->constrained('plantillas_horario')->nullOnDelete();
            $table->json('turnos');
            $table->json('dias');
            $table->decimal('horas_semanales', 6, 2)->default(0);
            $table->date('vigencia_inicio');
            $table->date('vigencia_fin')->nullable();
            $table->string('estado')->default('Vigente');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['colaborador_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_personal');
        Schema::dropIfExists('plantillas_horario');

        Schema::table('contratos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('sede_id');
        });

        Schema::dropIfExists('sedes_trabajo');
        Schema::dropIfExists('areas_unidad');

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn([
                'sexo',
                'estado_civil',
                'nacionalidad',
                'departamento',
                'provincia',
                'distrito',
                'contacto_emergencia',
                'telefono_emergencia',
                'nivel_educativo',
                'institucion_estudios',
                'especialidad',
                'grado_titulo',
                'anio_egreso',
            ]);
        });
    }
};
