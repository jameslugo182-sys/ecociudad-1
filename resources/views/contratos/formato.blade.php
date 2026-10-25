<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Formato de contrato</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; line-height: 1.45; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 6px; text-transform: uppercase; }
        h2 { font-size: 12px; margin: 18px 0 8px; text-transform: uppercase; border-bottom: 1px solid #111; padding-bottom: 4px; }
        .muted { color: #444; text-align: center; margin-bottom: 18px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { padding: 4px 6px; vertical-align: top; text-align: left; }
        .label { width: 32%; font-weight: bold; }
        .firma { margin-top: 48px; width: 100%; }
        .firma th { text-align: center; padding-top: 36px; font-weight: normal; }
        .linea { border-top: 1px solid #111; width: 70%; margin: 0 auto 6px; }
        .nota { font-size: 10px; margin-top: 20px; color: #333; }
        p { text-align: justify; }
    </style>
</head>
<body>
    <h1>Municipalidad Distrital de El Tambo</h1>
    <div class="muted">EcoCiudad · Formato de contrato de personal</div>
    <h1>Contrato de {{ $datos['tipo'] }} N.º {{ $datos['numero'] }}</h1>

    <p>
        Conste por el presente documento el contrato que celebran, de una parte, la <strong>Municipalidad Distrital de El Tambo</strong>,
        y de la otra parte <strong>{{ $nombreCompleto }}</strong>, identificado(a) con documento N.º <strong>{{ $datos['documento'] }}</strong>,
        quien declara haber leído las condiciones que se detallan a continuación y se obliga a firmar el presente formato
        para su posterior registro institucional.
    </p>

    <h2>Datos personales del colaborador</h2>
    <table>
        <thead>
            <tr><th scope="col">Campo</th><th scope="col">Dato</th></tr>
        </thead>
        <tbody>
            <tr><th class="label" scope="row">Nombres y apellidos</th><td>{{ $nombreCompleto }}</td></tr>
            <tr><th class="label" scope="row">Documento</th><td>{{ $datos['documento'] }}</td></tr>
            <tr><th class="label" scope="row">Sexo</th><td>{{ $datos['sexo'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Estado civil</th><td>{{ $datos['estado_civil'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Nacionalidad</th><td>{{ $datos['nacionalidad'] ?? 'Peruana' }}</td></tr>
            <tr><th class="label" scope="row">Fecha de nacimiento</th><td>{{ $datos['fecha_nacimiento'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Teléfono</th><td>{{ $datos['telefono'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Correo</th><td>{{ $datos['email_personal'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Dirección</th><td>{{ $datos['direccion'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Ubigeo</th><td>{{ collect([$datos['departamento'] ?? null, $datos['provincia'] ?? null, $datos['distrito'] ?? null])->filter()->join(' / ') ?: '—' }}</td></tr>
            <tr><th class="label" scope="row">Contacto de emergencia</th><td>{{ $datos['contacto_emergencia'] ?? '—' }} {{ !empty($datos['telefono_emergencia']) ? '· '.$datos['telefono_emergencia'] : '' }}</td></tr>
        </tbody>
    </table>

    <h2>Estudios</h2>
    <table>
        <thead>
            <tr><th scope="col">Campo</th><th scope="col">Dato</th></tr>
        </thead>
        <tbody>
            <tr><th class="label" scope="row">Nivel educativo</th><td>{{ $datos['nivel_educativo'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Institución</th><td>{{ $datos['institucion_estudios'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Especialidad / carrera</th><td>{{ $datos['especialidad'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Grado o título</th><td>{{ $datos['grado_titulo'] ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Año de egreso</th><td>{{ $datos['anio_egreso'] ?? '—' }}</td></tr>
        </tbody>
    </table>

    <h2>Condiciones del contrato</h2>
    <table>
        <thead>
            <tr><th scope="col">Campo</th><th scope="col">Dato</th></tr>
        </thead>
        <tbody>
            <tr><th class="label" scope="row">Modalidad</th><td>{{ $datos['tipo'] }}</td></tr>
            <tr><th class="label" scope="row">Cargo</th><td>{{ $cargo?->nombre ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Área / unidad</th><td>{{ $area?->nombre ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Sede de trabajo</th><td>{{ $sede?->nombre ?? '—' }}</td></tr>
            <tr><th class="label" scope="row">Vigencia</th><td>{{ $datos['fecha_inicio'] }} al {{ $datos['fecha_fin'] }}</td></tr>
            <tr><th class="label" scope="row">Remuneración mensual</th><td>{{ isset($datos['remuneracion']) && $datos['remuneracion'] !== '' ? 'S/ '.number_format((float) $datos['remuneracion'], 2) : '—' }}</td></tr>
            <tr><th class="label" scope="row">Jornada semanal</th><td>{{ $datos['jornada_horas'] ?? '—' }} horas</td></tr>
            <tr><th class="label" scope="row">Observaciones</th><td>{{ $datos['observaciones'] ?? '—' }}</td></tr>
        </tbody>
    </table>

    <p>
        El/la contratado(a) declara que la información consignada es veraz y se compromete a prestar servicios en las
        condiciones señaladas, sujetándose a la normativa laboral municipal vigente. Este documento se entrega para su
        lectura y firma, y deberá devolverse firmado para su archivo.
    </p>

    <table class="firma">
        <thead>
            <tr>
                <th scope="col">Empleador</th>
                <th scope="col">Contratado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="linea"></div>
                    Municipalidad Distrital de El Tambo<br>Empleador
                </td>
                <td>
                    <div class="linea"></div>
                    {{ $nombreCompleto }}<br>DNI {{ $datos['documento'] }}
                </td>
            </tr>
        </tbody>
    </table>

    <p class="nota">Huancayo, {{ now()->translatedFormat('d \d\e F \d\e Y') }}. Conserve una copia firmada. El PDF firmado se adjunta al registro institucional.</p>
</body>
</html>
