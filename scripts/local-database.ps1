param(
    [ValidateSet('start', 'stop', 'status')]
    [string]$Action = 'status',
    [string]$DatabaseBinary = 'C:/xampp/mysql/bin/mysqld.exe'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$runtimeRoot = Join-Path $projectRoot '.local'
$databaseRoot = Join-Path $runtimeRoot 'database'
$pidPath = Join-Path $runtimeRoot 'database.pid'
$dataPath = [IO.Path]::GetFullPath($databaseRoot).Replace('\', '/')
if (-not $dataPath.StartsWith([IO.Path]::GetFullPath($runtimeRoot).Replace('\', '/') + '/', [StringComparison]::OrdinalIgnoreCase)) { throw 'Database folder must stay inside the local project runtime.' }
if (-not (Test-Path -LiteralPath (Join-Path $databaseRoot 'mysql'))) { throw 'The isolated local database is not initialized. Follow docs/backend-phase-1.md.' }

$existingProcess = $null
if (Test-Path -LiteralPath $pidPath) {
    $databaseProcessId = [int](Get-Content -LiteralPath $pidPath)
    $existingProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$databaseProcessId"
    if ($existingProcess -and ($existingProcess.Name -ne 'mysqld.exe' -or -not $existingProcess.CommandLine.Contains($dataPath))) { throw 'Saved process ID belongs to a different process; no action taken.' }
}

if ($Action -eq 'status') {
    if ($existingProcess) { 'Local project database is running on 127.0.0.1:3307.' } else { 'Local project database is stopped.' }
    return
}

if ($Action -eq 'stop') {
    if ($existingProcess) {
        $adminBinary = Join-Path (Split-Path -Parent $DatabaseBinary) 'mysqladmin.exe'
        $privateOptions = Join-Path $runtimeRoot 'database-admin.ini'
        if (-not (Test-Path -LiteralPath $privateOptions)) { throw 'Private local database credentials are missing.' }
        & $adminBinary ('--defaults-extra-file=' + $privateOptions) shutdown
        if ($LASTEXITCODE -ne 0) { throw 'Database shutdown failed.' }
        $shutdownDeadline = [DateTime]::UtcNow.AddSeconds(15)
        while (Get-Process -Id $databaseProcessId -ErrorAction SilentlyContinue) {
            if ([DateTime]::UtcNow -gt $shutdownDeadline) { throw 'Database is still shutting down. Wait before starting it again.' }
            Start-Sleep -Milliseconds 200
        }
        'Local project database stopped.'
    } else { 'Local project database is already stopped.' }
    return
}

if ($existingProcess) { 'Local project database is already running.'; return }
if (Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue) { throw 'Port 3307 is already in use; no database was started.' }
if (-not (Test-Path -LiteralPath $DatabaseBinary)) { throw 'Database executable not found. Supply -DatabaseBinary with the local MariaDB executable path.' }
$engineRoot = (Split-Path -Parent (Split-Path -Parent $DatabaseBinary)).Replace('\', '/')
$databaseProcess = Start-Process -FilePath $DatabaseBinary -ArgumentList @('--no-defaults', ('--basedir="' + $engineRoot + '"'), ('--datadir="' + $dataPath + '"'), '--port=3307', '--bind-address=127.0.0.1', '--skip-name-resolve', '--character-set-server=utf8mb4', '--collation-server=utf8mb4_unicode_ci', '--console') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $runtimeRoot 'database-output.log') -RedirectStandardError (Join-Path $runtimeRoot 'database-errors.log')
Set-Content -LiteralPath $pidPath -Value $databaseProcess.Id
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    Start-Sleep -Milliseconds 200
    if ($databaseProcess.HasExited) { throw 'Database startup failed. Read .local/database-errors.log.' }
    if (Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue) { 'Local project database started on 127.0.0.1:3307.'; return }
}
throw 'Database startup timed out. Read .local/database-errors.log.'
