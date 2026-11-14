param(
    [string]$OutputPath
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$sourceRoot = (Resolve-Path -LiteralPath (Join-Path $projectRoot 'backend')).Path
if (-not $OutputPath) {
    $releaseName = 'doctor-profile-' + [DateTime]::UtcNow.ToString('yyyyMMdd-HHmmss') + '.zip'
    $OutputPath = Join-Path $projectRoot (Join-Path '.local/releases' $releaseName)
}
$archivePath = [IO.Path]::GetFullPath($OutputPath)
if ([IO.Path]::GetExtension($archivePath) -ne '.zip') { throw 'OutputPath must end in .zip.' }
[IO.Directory]::CreateDirectory((Split-Path -Parent $archivePath)) | Out-Null

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archiveStream = [IO.File]::Open($archivePath, [IO.FileMode]::CreateNew)
$archive = New-Object IO.Compression.ZipArchive($archiveStream, [IO.Compression.ZipArchiveMode]::Create, $false)
$fileCount = 0
$packaged = $false
try {
    $files = @()
    foreach ($directory in @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes')) {
        $files += Get-ChildItem -LiteralPath (Join-Path $sourceRoot $directory) -Recurse -File -Force
    }
    foreach ($filename in @('artisan', 'composer.json', 'composer.lock', 'production.env.example', 'README.md')) {
        $files += Get-Item -LiteralPath (Join-Path $sourceRoot $filename)
    }
    foreach ($file in $files) {
        if ($file.Attributes -band [IO.FileAttributes]::ReparsePoint) { continue }
        if (-not $file.FullName.StartsWith($sourceRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Unexpected source path.' }
        $relative = $file.FullName.Substring($sourceRoot.Length + 1).Replace([char]92, [char]47)
        if ($relative -match '^bootstrap/cache/' -or $relative -match '^public/(storage/|hot$)' -or $relative -match '^database/.*[.]sqlite') { continue }
        if ($relative -match '^vendor/' -or $relative -match '(^|/)([.]env([.].*)?|auth[.]json|node_modules|AGENTS[.]md|[.]git)(/|$)') { throw 'Private or development file detected in package input.' }
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $relative, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
        $fileCount++
    }
    foreach ($directory in @('bootstrap/cache/', 'storage/app/private/', 'storage/app/public/uploads/', 'storage/framework/cache/data/', 'storage/framework/sessions/', 'storage/framework/views/', 'storage/logs/')) {
        $archive.CreateEntry($directory) | Out-Null
    }
    $packaged = $true
} finally {
    $archive.Dispose()
    $archiveStream.Dispose()
    if (-not $packaged) { [IO.File]::Delete($archivePath) }
}
$verification = [IO.Compression.ZipFile]::OpenRead($archivePath)
try {
    if ($verification.Entries.Count -lt 20) { throw 'Incomplete release archive.' }
    foreach ($entry in $verification.Entries) {
        if ($entry.FullName -match '(^|/)[.][.](/|$)' -or $entry.FullName.StartsWith('/') -or $entry.FullName -match '^vendor/' -or $entry.FullName -match '(^|/)([.]env([.].*)?|auth[.]json|tests|node_modules)(/|$)') { throw 'Unexpected archive content.' }
    }
} finally {
    $verification.Dispose()
}
$checksum = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
[IO.File]::WriteAllText($archivePath + '.sha256', $checksum + '  ' + [IO.Path]::GetFileName($archivePath) + [Environment]::NewLine)
Write-Output ('Release package: ' + $archivePath)
Write-Output ('Source files: ' + $fileCount)
Write-Output ('SHA256: ' + $checksum)
Write-Output 'Source-only package: install production Composer dependencies on the host. Credentials, database contents and uploaded images are excluded.'
