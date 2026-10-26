<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 16px 0 8px; border-bottom: 1px solid #111; padding-bottom: 4px; text-transform: uppercase; }
        .muted { color: #444; text-align: center; margin-bottom: 14px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #eef2f7; font-size: 10px; }
        .kpi { width: 24%; display: inline-block; border: 1px solid #ccc; padding: 6px; margin: 0 1% 8px 0; }
        .kpi strong { display: block; font-size: 14px; }
        .empty { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <h1>Municipalidad Distrital de El Tambo</h1>
    <div class="muted">EcoCiudad · {{ $titulo }} · Periodo {{ $periodo['etiqueta'] }} · Generado {{ $generado }}</div>

    @if ($seccion === 'resumen')
        <h2>Indicadores del periodo</h2>
        <p>
            Incidencias: <strong>{{ $resumen['total'] }}</strong> ·
            Atendidas: <strong>{{ $resumen['atendidos'] }}</strong> ·
            Tasa: <strong>{{ $resumen['tasaAtencion'] }}%</strong> ·
            Tiempo promedio: <strong>{{ $resumen['promedioHoras'] }} h</strong>
        </p>
        <p>
            Rutas: <strong>{{ $resumen['rutasPeriodo'] }}</strong> ·
            Km estimados: <strong>{{ $resumen['kmEstimados'] }}</strong> ·
            Lecturas IoT: <strong>{{ $resumen['lecturasPeriodo'] }}</strong>
        </p>
        <p>
            Estado actual — personal activo: <strong>{{ $resumen['personalActivo'] }}</strong> ·
            vehículos disponibles: <strong>{{ $resumen['vehiculosDisponibles'] }}</strong> ·
            tachos en alerta: <strong>{{ $resumen['tachosAlerta'] }}</strong> ·
            contratos vigentes: <strong>{{ $resumen['contratosVigentes'] }}</strong>
        </p>
        @include('reportes.parcial-barras', ['titulo' => 'Incidencias por estado', 'datos' => $porEstado])
        @include('reportes.parcial-barras', ['titulo' => 'Tipos más reportados', 'datos' => $porTipo])
        @include('reportes.parcial-barras', ['titulo' => 'Origen', 'datos' => $porOrigen])
        @include('reportes.parcial-barras', ['titulo' => 'Registros por mes', 'datos' => $porMes])
    @endif

    @if ($seccion === 'incidencias')
        @include('reportes.parcial-barras', ['titulo' => 'Por estado', 'datos' => $porEstado])
        <h2>Incidencias registradas</h2>
        @if (count($incidencias) === 0)
            <p class="empty">No hay incidencias en el periodo.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">N.º</th>
                        <th scope="col">Ciudadano / origen</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Registrado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incidencias as $fila)
                        <tr>
                            <td>{{ $fila['codigo'] }}</td>
                            <td>{{ $fila['ciudadano'] }} · {{ $fila['origen'] }}</td>
                            <td>{{ $fila['tipo'] }}</td>
                            <td>{{ $fila['estado'] }}</td>
                            <td>{{ $fila['responsable'] ?? '—' }}</td>
                            <td>{{ $fila['registrado'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if ($seccion === 'rutas')
        <p>
            Rutas del periodo: <strong>{{ $resumenRutas['total'] }}</strong> ·
            Km: <strong>{{ $resumenRutas['km'] }}</strong> ·
            Activas ahora: <strong>{{ $resumenRutas['activas'] }}</strong> ·
            Equipos completos: <strong>{{ $resumenRutas['equiposCompletos'] }}</strong> /
            {{ $resumenRutas['vehiculos'] }}
        </p>
        <h2>Rutas</h2>
        @if (count($rutas) === 0)
            <p class="empty">No hay rutas en el periodo.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Vehículo</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Paradas</th>
                        <th scope="col">Km</th>
                        <th scope="col">Horario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rutas as $ruta)
                        <tr>
                            <td>{{ $ruta['fecha'] }}</td>
                            <td>{{ $ruta['camion'] }}</td>
                            <td>{{ $ruta['estado'] }}</td>
                            <td>{{ $ruta['paradas'] }}</td>
                            <td>{{ $ruta['km'] }}</td>
                            <td>{{ $ruta['horario'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <h2>Flota</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Placa</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Personal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($flota as $camion)
                    <tr>
                        <td>{{ $camion['codigo'] }}</td>
                        <td>{{ $camion['placa'] }}</td>
                        <td>{{ $camion['estado'] }}</td>
                        <td>{{ $camion['personal'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Sin vehículos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($seccion === 'tachos')
        <p>
            Tachos: <strong>{{ $resumenTachos['total'] }}</strong> ·
            En alerta: <strong>{{ $resumenTachos['alertas'] }}</strong> ·
            Llenos: <strong>{{ $resumenTachos['llenos'] }}</strong> ·
            Lecturas del periodo: <strong>{{ $resumenTachos['lecturasPeriodo'] }}</strong> ·
            Nivel promedio: <strong>{{ $resumenTachos['nivelPromedio'] }}%</strong>
        </p>
        <table>
            <thead>
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Nivel</th>
                    <th scope="col">Lecturas</th>
                    <th scope="col">Última lectura</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tachos as $tacho)
                    <tr>
                        <td>{{ $tacho['codigo'] }}</td>
                        <td>{{ $tacho['nombre'] }}</td>
                        <td>{{ $tacho['estado'] }}{{ $tacho['alerta'] ? ' (alerta)' : '' }}</td>
                        <td>{{ $tacho['nivel'] !== null ? $tacho['nivel'].'%' : 'Sin lecturas' }}</td>
                        <td>{{ $tacho['lecturas'] }}</td>
                        <td>{{ $tacho['ultimaLectura'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No hay tachos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($seccion === 'personal')
        <p>
            Cuentas municipales: <strong>{{ $resumenPersonal['cuentas'] }}</strong>
            ({{ $resumenPersonal['activas'] }} activas) ·
            Ciudadanos: <strong>{{ $resumenPersonal['ciudadanos'] }}</strong> ·
            Contratos vigentes: <strong>{{ $resumenPersonal['vigentes'] }}</strong>
            / {{ $resumenPersonal['contratos'] }}
        </p>
        <h2>Cuentas</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">Correo</th>
                    <th scope="col">Rol</th>
                    <th scope="col">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario['name'] }}</td>
                        <td>{{ $usuario['email'] }}</td>
                        <td>{{ $usuario['rol'] }}</td>
                        <td>{{ $usuario['activo'] ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h2>Contratos</h2>
        <table>
            <thead>
                <tr>
                    <th scope="col">N.º</th>
                    <th scope="col">Colaborador</th>
                    <th scope="col">Cargo</th>
                    <th scope="col">Tipo</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Vigencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contratos as $contrato)
                    <tr>
                        <td>{{ $contrato['numero'] }}</td>
                        <td>{{ $contrato['colaborador'] }}</td>
                        <td>{{ $contrato['cargo'] ?? '—' }}</td>
                        <td>{{ $contrato['tipo'] }}</td>
                        <td>{{ $contrato['estado'] }}</td>
                        <td>{{ $contrato['inicio'] }} – {{ $contrato['fin'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No hay contratos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
