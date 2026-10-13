$ErrorActionPreference = 'Stop'

$outputRoot = Join-Path $PSScriptRoot 'output'
$keepPath = Join-Path $outputRoot 'InventorySystemSetup.exe'

if (-not (Test-Path $keepPath -PathType Leaf)) {
    throw 'The newest installer is missing; nothing was deleted.'
}

$lockedInstallers = @()
Get-ChildItem $outputRoot -Filter 'InventorySystemSetup*.exe' -File |
    Where-Object { $_.FullName -ine $keepPath } |
    ForEach-Object {
        try {
            $lockProbe = [IO.File]::Open($_.FullName, [IO.FileMode]::Open, [IO.FileAccess]::ReadWrite, [IO.FileShare]::None)
            $lockProbe.Dispose()
            Remove-Item -LiteralPath $_.FullName -Force
        } catch {
            $lockedInstallers += $_.FullName
        }
    }

$packagePath = Join-Path $outputRoot 'package'
if (Test-Path $packagePath) {
    Remove-Item -LiteralPath $packagePath -Recurse -Force
}

$directiveFile = Join-Path $outputRoot '~InventorySystemSetup.DDF'
if (Test-Path $directiveFile) {
    Remove-Item -LiteralPath $directiveFile -Force
}

Get-ChildItem $outputRoot -Force | Select-Object Name, Length | Format-Table -AutoSize
if ($lockedInstallers) {
    Write-Warning 'Close the running older installer windows, then rerun this script to remove their executable files.'
    $lockedInstallers | ForEach-Object { Write-Warning $_ }
}
