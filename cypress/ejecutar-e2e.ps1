# Ejecuta las pruebas Cypress contra la aplicación real, con una base de datos SQLite propia
# (no modifica la base PostgreSQL de desarrollo).
#   powershell -ExecutionPolicy Bypass -File cypress/ejecutar-e2e.ps1
param([int]$Puerto = 8001, [string]$Spec = 'cypress/e2e/**/*.cy.js')

$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz

$bd = Join-Path $env:TEMP 'ecociudad-e2e.sqlite'
New-Item -ItemType Directory -Force (Split-Path $bd) | Out-Null
Remove-Item $bd -ErrorAction SilentlyContinue
New-Item -ItemType File $bd | Out-Null

$env:APP_ENV = 'local'
$env:APP_DEBUG = 'false'
$env:APP_URL = "http://127.0.0.1:$Puerto"
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $bd
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'log'
$env:ADMIN_EMAIL = 'admin.e2e@ecociudad.pe'
$env:ADMIN_PASSWORD = 'E2e-Admin2026!'

php artisan migrate --force --seed
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Push-Location (Join-Path $raiz 'public')
$servidor = Start-Process php -ArgumentList '-S', "127.0.0.1:$Puerto", '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php' -PassThru -WindowStyle Hidden
Pop-Location

try {
    Start-Sleep -Seconds 3
    $env:CYPRESS_BASE_URL = "http://127.0.0.1:$Puerto"
    npx cypress run --config-file cypress/cypress.config.js --spec $Spec *>&1 | Tee-Object -FilePath (Join-Path $raiz 'docs/pruebas/evidencias/cypress_ejecucion.txt')
    $codigo = $LASTEXITCODE
}
finally {
    Stop-Process -Id $servidor.Id -Force -ErrorAction SilentlyContinue
}
exit $codigo
