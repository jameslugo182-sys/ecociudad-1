<#
.SYNOPSIS
  Exporta la base de datos del contenedor "db" (estructura + datos) para construir la imagen ecociudad-db.

.DESCRIPTION
  Genera docker/backup/ecociudad_completo.sql, que Dockerfile.db copia a /docker-entrypoint-initdb.d.
  El archivo contiene datos personales: está en .gitignore y .dockerignore. No lo subas a git ni lo
  incluyas en la imagen de la aplicación.
  Requiere el stack levantado:  docker compose --env-file .env.docker up -d
#>
$ErrorActionPreference = 'Stop'
$raiz = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $raiz

$usuario = (Get-Content .env.docker | Where-Object { $_ -match '^DB_USERNAME=' }) -replace '^DB_USERNAME=', ''
$base = (Get-Content .env.docker | Where-Object { $_ -match '^DB_DATABASE=' }) -replace '^DB_DATABASE=', ''

New-Item -ItemType Directory -Force docker\backup | Out-Null
$tmp = Join-Path $env:TEMP 'ecociudad_completo.sql'

# Sesiones, caché y colas no se exportan (son datos transitorios).
docker compose --env-file .env.docker exec -T db pg_dump -U $usuario -d $base --no-owner --no-privileges `
    --exclude-table-data=sessions --exclude-table-data=cache --exclude-table-data=cache_locks `
    --exclude-table-data=jobs --exclude-table-data=job_batches --exclude-table-data=failed_jobs |
    Out-File -FilePath $tmp -Encoding utf8
if ($LASTEXITCODE -ne 0) { throw 'pg_dump falló. ¿Está levantado el contenedor db?' }

# Quita las directivas \restrict / \unrestrict (solo las entienden versiones recientes de psql).
$limpio = Get-Content $tmp -Encoding UTF8 | Where-Object { $_ -notmatch '^\\(un)?restrict' }
$destino = Join-Path $raiz 'docker\backup\ecociudad_completo.sql'
[IO.File]::WriteAllText($destino, (($limpio -join "`n") + "`n"), (New-Object Text.UTF8Encoding($false)))
Remove-Item $tmp -Force

$tablas = (Select-String -Path $destino -Pattern '^CREATE TABLE ').Count
Write-Host "Exportado: $destino ($tablas tablas, $([math]::Round((Get-Item $destino).Length / 1KB)) KB)"
