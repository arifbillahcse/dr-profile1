param(
    [ValidateSet('serve', 'status', 'migrate', 'test', 'format', 'import', 'create-admin')]
    [string]$Action = 'status'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$localPhp = Join-Path $projectRoot '.local/php/php.exe'
$phpExecutable = if (Test-Path -LiteralPath $localPhp) { $localPhp } else { (Get-Command php -ErrorAction Stop).Source }

Push-Location (Join-Path $projectRoot 'backend')
try {
    switch ($Action) {
        'serve' { & $phpExecutable artisan serve --host=127.0.0.1 --port=8000 --no-interaction }
        'status' { & $phpExecutable artisan project:status --no-interaction }
        'migrate' { & $phpExecutable artisan migrate --seed --no-interaction }
        'import' { & $phpExecutable artisan website:import --no-interaction }
        'test' { & $phpExecutable artisan test --compact }
        'format' { & $phpExecutable vendor/bin/pint --dirty --format agent }
        'create-admin' { & $phpExecutable artisan admin:create }
    }
    if ($LASTEXITCODE -ne 0) { throw "Backend command failed with exit code $LASTEXITCODE." }
} finally {
    Pop-Location
}
