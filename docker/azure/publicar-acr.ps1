<#
.SYNOPSIS
  Etiqueta y sube las 3 imágenes de EcoCiudad (web, app, db) a Azure Container Registry.

.EXAMPLE
  .\docker\azure\publicar-acr.ps1 -AcrName miregistro
#>
param(
    [Parameter(Mandatory = $true)][string]$AcrName,
    [string]$Tag = 'latest'
)
$ErrorActionPreference = 'Stop'

$servidor = "$AcrName.azurecr.io"
az acr login --name $AcrName
if ($LASTEXITCODE -ne 0) { throw 'No se pudo iniciar sesión en el registro. Ejecuta antes: az login' }

foreach ($nombre in 'web', 'app', 'db') {
    $local = "ecociudad-${nombre}:latest"
    $remoto = "$servidor/ecociudad-${nombre}:$Tag"
    docker image inspect $local *> $null
    if ($LASTEXITCODE -ne 0) { throw "No existe la imagen $local. Construye antes con: docker compose --env-file .env.docker build" }
    docker tag $local $remoto
    docker push $remoto
    if ($LASTEXITCODE -ne 0) { throw "Falló el push de $remoto" }
}
Write-Host "Imágenes publicadas en $servidor (etiqueta $Tag)."
