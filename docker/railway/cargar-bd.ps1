<#
.SYNOPSIS
  Carga el volcado de la base de datos de EcoCiudad en el PostgreSQL de Railway.

.DESCRIPTION
  Usa un contenedor temporal postgres:16-alpine como cliente (no necesitas instalar psql).
  El volcado (docker/backup/ecociudad_completo.sql) trae estructura, datos y la tabla migrations,
  así que debe cargarse en una base VACÍA, ANTES del primer despliegue de la aplicación
  (si la app ya migró, borra y recrea la base o usa -Limpiar).

  Genera el volcado con:  .\docker\azure\exportar-bd.ps1

.PARAMETER DatabaseUrl
  URL pública de PostgreSQL en Railway (variable DATABASE_PUBLIC_URL del servicio Postgres):
  postgresql://postgres:CLAVE@HOST.proxy.rlwy.net:PUERTO/railway

.PARAMETER Limpiar
  Borra el esquema public antes de cargar (destruye lo que haya en esa base).

.EXAMPLE
  .\docker\railway\cargar-bd.ps1 -DatabaseUrl "postgresql://postgres:xxxx@roundhouse.proxy.rlwy.net:12345/railway"
#>
param(
    [Parameter(Mandatory = $true)][string]$DatabaseUrl,
    [string]$Archivo = 'docker\backup\ecociudad_completo.sql',
    [switch]$Limpiar,
    [string]$Red = ''
)
$ErrorActionPreference = 'Stop'
$raiz = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $raiz

$ruta = Resolve-Path $Archivo -ErrorAction Stop
$carpeta = Split-Path $ruta -Parent
$nombre = Split-Path $ruta -Leaf

$redArgs = @()
if ($Red) { $redArgs = @('--network', $Red) }

if ($Limpiar) {
    docker run --rm @redArgs postgres:16-alpine psql $DatabaseUrl -v ON_ERROR_STOP=1 -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;'
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo limpiar el esquema.' }
}

docker run --rm @redArgs -v "${carpeta}:/datos:ro" postgres:16-alpine `
    psql $DatabaseUrl -v ON_ERROR_STOP=1 --single-transaction -q -o /dev/null -f "/datos/$nombre"
if ($LASTEXITCODE -ne 0) { throw 'La carga falló (¿la base ya tenía tablas? usa -Limpiar).' }

Write-Host 'Base de datos cargada.'
