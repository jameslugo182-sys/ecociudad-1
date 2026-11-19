<#
.SYNOPSIS
  Despliega EcoCiudad (web + app + db) en Azure Container Instances como un grupo de contenedores.

.DESCRIPTION
  1. Crea el grupo de recursos y el Azure Container Registry (si no existen).
  2. Sube las imágenes ecociudad-web, ecociudad-app y ecociudad-db al registro.
  3. Genera el YAML del grupo con las variables de .env.docker y lo despliega.
  Dentro del grupo los contenedores comparten red (localhost), por eso DB_HOST=127.0.0.1 y
  PHP_UPSTREAM=127.0.0.1:9000.

  Requisitos: Azure CLI (az) con sesión iniciada (az login), Docker y las imágenes construidas:
      npm run build
      docker compose --env-file .env.docker build

.PARAMETER SoloGenerarYaml
  No usa Azure: solo escribe docker/azure/ecociudad-aci.generado.yaml para revisarlo (contiene secretos,
  está en .gitignore; bórralo después).

.EXAMPLE
  .\docker\azure\desplegar-aci.ps1 -ResourceGroup rg-ecociudad -AcrName ecociudadacr -DnsLabel ecociudad-demo
#>
param(
    [Parameter(Mandatory = $true)][string]$ResourceGroup,
    [Parameter(Mandatory = $true)][string]$AcrName,
    [Parameter(Mandatory = $true)][string]$DnsLabel,
    [string]$Location = 'brazilsouth',
    [string]$Tag = 'latest',
    [switch]$SoloGenerarYaml
)
$ErrorActionPreference = 'Stop'
$raiz = Resolve-Path (Join-Path $PSScriptRoot '..\..')
Set-Location $raiz

if (-not (Test-Path .env.docker)) { throw 'Falta .env.docker (cópialo desde .env.docker.example).' }

function Q([string]$valor) { "'" + ($valor -replace "'", "''") + "'" }

# --- Variables de .env.docker -------------------------------------------------
$vars = [ordered]@{}
foreach ($linea in Get-Content .env.docker -Encoding UTF8) {
    if ($linea -match '^\s*([A-Z][A-Z0-9_]*)=(.*)$') {
        $valor = $Matches[2].Trim()
        if ($valor.Length -ge 2 -and $valor.StartsWith('"') -and $valor.EndsWith('"')) { $valor = $valor.Substring(1, $valor.Length - 2) }
        $vars[$Matches[1]] = $valor
    }
}
foreach ($k in @($vars.Keys)) {
    $vars[$k] = [regex]::Replace($vars[$k], '\$\{(\w+)\}', { param($m) if ($vars.Contains($m.Groups[1].Value)) { $vars[$m.Groups[1].Value] } else { '' } })
}
if (-not $vars['APP_KEY'] -or $vars['APP_KEY'] -eq 'base64:') { throw 'APP_KEY está vacío en .env.docker.' }
if (-not $vars['DB_PASSWORD']) { throw 'DB_PASSWORD está vacío en .env.docker.' }

$url = "http://$DnsLabel.$Location.azurecontainer.io:8080"
$vars['APP_URL'] = $url
$vars['APP_ENV'] = 'production'
$vars['APP_DEBUG'] = 'false'
$vars['DB_CONNECTION'] = 'pgsql'
$vars['DB_HOST'] = '127.0.0.1'
$vars['DB_PORT'] = '5432'
$vars['RUN_MIGRATIONS'] = 'true'
$vars['SEED_ADMIN'] = 'false'
$vars['SEED_DEMO'] = 'false'
$vars.Remove('APP_PORT')

$secretas = 'APP_KEY', 'DB_PASSWORD', 'ADMIN_PASSWORD'
$envApp = foreach ($k in $vars.Keys) {
    if ($vars[$k] -eq '') { continue }
    if ($secretas -contains $k) { "        - name: $k`n          secureValue: $(Q $vars[$k])" }
    else { "        - name: $k`n          value: $(Q $vars[$k])" }
}

$servidor = "$AcrName.azurecr.io"

# --- Registro (credenciales) --------------------------------------------------
if ($SoloGenerarYaml) {
    $acrUsuario = $AcrName
    $acrClave = 'SOLO-PARA-REVISAR'
}
else {
    az group create --name $ResourceGroup --location $Location --output none
    az acr show --name $AcrName --resource-group $ResourceGroup --output none 2>$null
    if ($LASTEXITCODE -ne 0) {
        az acr create --name $AcrName --resource-group $ResourceGroup --sku Basic --admin-enabled true --output none
        if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el registro (el nombre debe ser único y solo alfanumérico).' }
    }
    az acr update --name $AcrName --admin-enabled true --output none
    & (Join-Path $PSScriptRoot 'publicar-acr.ps1') -AcrName $AcrName -Tag $Tag
    $acrUsuario = az acr credential show --name $AcrName --query username --output tsv
    $acrClave = az acr credential show --name $AcrName --query 'passwords[0].value' --output tsv
}

# --- YAML del grupo de contenedores -------------------------------------------
$yaml = @"
apiVersion: '2021-10-01'
name: ecociudad
location: $Location
properties:
  osType: Linux
  restartPolicy: Always
  imageRegistryCredentials:
    - server: $servidor
      username: $(Q $acrUsuario)
      password: $(Q $acrClave)
  ipAddress:
    type: Public
    dnsNameLabel: $DnsLabel
    ports:
      - protocol: TCP
        port: 8080
  volumes:
    - name: storage
      emptyDir: {}
    - name: pgdata
      emptyDir: {}
  containers:
    - name: db
      properties:
        image: $servidor/ecociudad-db:$Tag
        resources:
          requests:
            cpu: 1
            memoryInGB: 1.5
        environmentVariables:
        - name: POSTGRES_DB
          value: $(Q $vars['DB_DATABASE'])
        - name: POSTGRES_USER
          value: $(Q $vars['DB_USERNAME'])
        - name: POSTGRES_PASSWORD
          secureValue: $(Q $vars['DB_PASSWORD'])
        volumeMounts:
          - name: pgdata
            mountPath: /var/lib/postgresql/data
    - name: app
      properties:
        image: $servidor/ecociudad-app:$Tag
        resources:
          requests:
            cpu: 1
            memoryInGB: 1.5
        environmentVariables:
$($envApp -join "`n")
        volumeMounts:
          - name: storage
            mountPath: /var/www/html/storage
    - name: web
      properties:
        image: $servidor/ecociudad-web:$Tag
        ports:
          - protocol: TCP
            port: 8080
        resources:
          requests:
            cpu: 0.5
            memoryInGB: 0.5
        environmentVariables:
        - name: PHP_UPSTREAM
          value: '127.0.0.1:9000'
        volumeMounts:
          - name: storage
            mountPath: /var/www/html/storage
            readOnly: true
"@

if ($SoloGenerarYaml) {
    $salida = Join-Path $PSScriptRoot 'ecociudad-aci.generado.yaml'
    [IO.File]::WriteAllText($salida, $yaml + "`n", (New-Object Text.UTF8Encoding($false)))
    Write-Host "YAML generado: $salida (contiene secretos; bórralo después de revisarlo)"
    return
}

$tmp = Join-Path $env:TEMP 'ecociudad-aci.yaml'
try {
    [IO.File]::WriteAllText($tmp, $yaml + "`n", (New-Object Text.UTF8Encoding($false)))
    az container create --resource-group $ResourceGroup --file $tmp --output none
    if ($LASTEXITCODE -ne 0) { throw 'az container create falló.' }
}
finally {
    Remove-Item $tmp -Force -ErrorAction SilentlyContinue
}

Write-Host ''
Write-Host "Despliegue iniciado. Dirección: $url"
Write-Host 'El primer arranque tarda 1-2 minutos (la base de datos se inicializa sola).'
Write-Host "Logs:    az container logs --resource-group $ResourceGroup --name ecociudad --container-name app"
Write-Host "Estado:  az container show --resource-group $ResourceGroup --name ecociudad --query instanceView.state"
