$ErrorActionPreference = 'Stop'
$appRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$localAppData = if ($env:LOCALAPPDATA) { $env:LOCALAPPDATA } else { [Environment]::GetFolderPath('LocalApplicationData') }
$dataRoot = Join-Path $localAppData 'InventorySystem'
$mysqlExe = Join-Path $appRoot 'runtime\mysql\bin\mysqld.exe'
$mysqlAdmin = Join-Path $appRoot 'runtime\mysql\bin\mysqladmin.exe'
$phpExe = Join-Path $appRoot 'runtime\php\php.exe'
$myIni = Join-Path $dataRoot 'my.ini'
$rootClient = Join-Path $dataRoot 'secure\mysql-root.cnf'
$portsFile = Join-Path $dataRoot 'secure\ports.json'
$routerPath = Join-Path $appRoot 'local_router.php'
$browserProfile = Join-Path $dataRoot 'browser-profile'
$phpPort = if ($env:INVENTORY_WEB_PORT) { [int]$env:INVENTORY_WEB_PORT } else { 0 }
$mysqlPort = if ($env:INVENTORY_MYSQL_PORT) { [int]$env:INVENTORY_MYSQL_PORT } else { 0 }
if (Test-Path $portsFile) {
    $ports = Get-Content $portsFile -Raw | ConvertFrom-Json
    if (-not $phpPort) { $phpPort = [int]$ports.web }
    if (-not $mysqlPort) { $mysqlPort = [int]$ports.mysql }
}

$appWindows = @(Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
    $_.Name -in @('msedge.exe', 'chrome.exe') -and
    $_.CommandLine -and $_.CommandLine.IndexOf("--user-data-dir=`"$browserProfile`"",[StringComparison]::OrdinalIgnoreCase) -ge 0
})
foreach ($windowProcess in $appWindows) { Stop-Process -Id $windowProcess.ProcessId -Force -ErrorAction SilentlyContinue }
foreach ($windowProcess in $appWindows) { Wait-Process -Id $windowProcess.ProcessId -Timeout 10 -ErrorAction SilentlyContinue }

$processes = @(Get-CimInstance Win32_Process -ErrorAction SilentlyContinue)
$managedPhp = @($processes | Where-Object {
    $_.Name -ieq 'php.exe' -and $_.ExecutablePath -ieq $phpExe -and $_.CommandLine -and
    $_.CommandLine.IndexOf($routerPath,[StringComparison]::OrdinalIgnoreCase) -ge 0 -and
    $_.CommandLine.IndexOf('-S 127.0.0.1:',[StringComparison]::OrdinalIgnoreCase) -ge 0
})
foreach ($process in $managedPhp) { Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue }
foreach ($process in $managedPhp) { Wait-Process -Id $process.ProcessId -Timeout 10 -ErrorAction SilentlyContinue }
if (Test-Path (Join-Path $dataRoot 'php.pid')) { Remove-Item (Join-Path $dataRoot 'php.pid') -Force }

$managedMysql = @($processes | Where-Object {
    $_.Name -ieq 'mysqld.exe' -and $_.ExecutablePath -ieq $mysqlExe -and $_.CommandLine -and
    $_.CommandLine.IndexOf($myIni,[StringComparison]::OrdinalIgnoreCase) -ge 0
})
if ($managedMysql.Count -and (Test-Path $rootClient) -and $mysqlPort) {
    & $mysqlAdmin "--defaults-extra-file=$rootClient" --protocol=TCP --host=127.0.0.1 "--port=$mysqlPort" --user=root shutdown 2>$null | Out-Null
}
$deadline = [DateTime]::UtcNow.AddSeconds(30)
do {
    $remaining = @(Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
        $_.Name -ieq 'mysqld.exe' -and $_.ExecutablePath -ieq $mysqlExe -and $_.CommandLine -and
        $_.CommandLine.IndexOf($myIni,[StringComparison]::OrdinalIgnoreCase) -ge 0
    })
    if ($remaining.Count -eq 0) { break }
    Start-Sleep -Milliseconds 250
} while ([DateTime]::UtcNow -lt $deadline)

if ($remaining.Count -gt 0) {
    foreach ($process in $remaining) { Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue }
    foreach ($process in $remaining) { Wait-Process -Id $process.ProcessId -Timeout 10 -ErrorAction SilentlyContinue }
}
if (Test-Path (Join-Path $dataRoot 'mysql.pid')) { Remove-Item (Join-Path $dataRoot 'mysql.pid') -Force }
