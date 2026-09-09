<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('evidencia_path')->nullable()->after('foto_path');
            $table->unsignedInteger('ruta_orden')->nullable()->after('prioridad');
            $table->timestamp('assigned_at')->nullable()->after('ruta_orden');
            $table->timestamp('atendido_at')->nullable()->after('assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'assigned_to',
                'evidencia_path',
                'ruta_orden',
                'assigned_at',
                'atendido_at',
            ]);
        });
    }
};
