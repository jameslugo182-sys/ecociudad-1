<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Horario de personal</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; line-height: 1.4; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 4px; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 16px 0 8px; text-transform: uppercase; border-bottom: 1px solid #111; padding-bottom: 4px; }
        .muted { color: #444; text-align: center; margin-bottom: 16px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { padding: 6px 8px; vertical-align: middle; }
        .datos td { padding: 4px 6px; }
        .label { width: 32%; font-weight: bold; }
        .horario th { background: #111; color: #fff; font-size: 10px; text-transform: uppercase; }
        .horario td, .horario th { border: 1px solid #333; text-align: center; }
        .horario td.dia { text-align: left; font-weight: bold; background: #f3f3f3; }
        .firma { margin-top: 48px; width: 100%; }
        .firma td { text-align: center; padding-top: 36px; }
        .linea { border-top: 1px solid #111; width: 70%; margin: 0 auto 6px; }
        .nota { font-size: 10px; margin-top: 18px; color: #333; }
        p { text-align: justify; }
    </style>
</head>
<body>
    <h1>Municipalidad Distrital de El Tambo</h1>
    <div class="muted">EcoCiudad · Horario de personal</div>
    <h1>Registro de horario de trabajo</h1>

    <p>
        Se entrega el presente documento a <strong>{{ $horario->colaborador?->nombre_completo }}</strong>,
        identificado(a) con DNI N.º <strong>{{ $horario->colaborador?->documento ?? '—' }}</strong>,
        para su conocimiento y cumplimiento del horario asignado.
    </p>

    <h2>Datos del colaborador</h2>
    <table class="datos">
        <tr><td class="label">Nombres y apellidos</td><td>{{ $horario->colaborador?->nombre_completo ?: '—' }}</td></tr>
        <tr><td class="label">Documento</td><td>{{ $horario->colaborador?->documento ?? '—' }}</td></tr>
        <tr><td class="label">Cargo</td><td>{{ $contrato?->cargo?->nombre ?? '—' }}</td></tr>
        <tr><td class="label">Plantilla</td><td>{{ $horario->plantilla?->nombre ?? 'Registro manual' }}</td></tr>
        <tr>
            <td class="label">Vigencia</td>
            <td>
                {{ $horario->vigencia_inicio?->format('d/m/Y') }}
                @if ($horario->vigencia_fin)
                    al {{ $horario->vigencia_fin->format('d/m/Y') }}
                @else
                    (sin fecha de término)
                @endif
            </td>
        </tr>
        <tr><td class="label">Horas semanales</td><td>{{ number_format((float) $horario->horas_semanales, 1) }} h</td></tr>
    </table>

    <h2>Horario semanal</h2>
    <table class="horario">
        <thead>
            <tr>
                <th>Día</th>
                @foreach ($turnos as $turno)
                    <th>{{ $turno['nombre'] ?? 'Turno' }}</th>
                @endforeach
                <th>Horas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                <tr>
                    <td class="dia">{{ $fila['nombre'] }}</td>
                    @foreach ($fila['celdas'] as $celda)
                        <td>{{ $celda }}</td>
                    @endforeach
                    <td>{{ $fila['horas'] > 0 ? number_format($fila['horas'], 1).' h' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        El/la trabajador(a) declara haber recibido y leído el presente horario, y se compromete a cumplirlo
        durante la vigencia indicada, salvo comunicación posterior de la Municipalidad.
    </p>

    <table class="firma">
        <tr>
            <td>
                <div class="linea"></div>
                Municipalidad Distrital de El Tambo<br>Área de personal
            </td>
            <td>
                <div class="linea"></div>
                {{ $horario->colaborador?->nombre_completo }}<br>
                DNI {{ $horario->colaborador?->documento ?? '—' }}<br>
                Recibí conforme
            </td>
        </tr>
    </table>

    <p class="nota">Huancayo, {{ now()->translatedFormat('d \d\e F \d\e Y') }}. Conservar una copia firmada del horario entregado.</p>
</body>
</html>
