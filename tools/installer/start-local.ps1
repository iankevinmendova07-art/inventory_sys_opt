param([switch]$NoBrowser,[switch]$QuietErrors)

$ErrorActionPreference = 'Stop'
$sid = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
$instanceMutex = [System.Threading.Mutex]::new($false,"Local\InventorySystem_$sid")
$ownsInstance = $instanceMutex.WaitOne(0)
if (-not $ownsInstance) {
    if (-not $QuietErrors) {
        Add-Type -AssemblyName System.Windows.Forms
        [System.Windows.Forms.MessageBox]::Show('Inventory System is already running. Use its open window.','Inventory System',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Information) | Out-Null
    }
    $instanceMutex.Dispose()
    exit 2
}
try {
$appRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$localAppData = if ($env:LOCALAPPDATA) { $env:LOCALAPPDATA } else { [Environment]::GetFolderPath('LocalApplicationData') }
if (-not $localAppData) { throw 'Could not locate the current Windows user data directory.' }
$dataRoot = Join-Path $localAppData 'InventorySystem'
$mysqlRoot = Join-Path $appRoot 'runtime\mysql'
$mysqlExe = Join-Path $mysqlRoot 'bin\mysql.exe'
$mysqlAdminExe = Join-Path $mysqlRoot 'bin\mysqladmin.exe'
$mysqlDumpExe = Join-Path $mysqlRoot 'bin\mysqldump.exe'
$mysqldExe = Join-Path $mysqlRoot 'bin\mysqld.exe'
$phpExe = Join-Path $appRoot 'runtime\php\php.exe'
$routerPath = Join-Path $appRoot 'local_router.php'
$schemaPath = Join-Path $appRoot 'database_schema.sql'
$migrationPath = Join-Path $appRoot 'tools\migrations\apply_inventory_indexes.php'
$logRoot = Join-Path $dataRoot 'logs'
$backupRoot = Join-Path $dataRoot 'backups'
$mysqlData = Join-Path $dataRoot 'mysql'
$secureRoot = Join-Path $dataRoot 'secure'
$portsPath = Join-Path $secureRoot 'ports.json'
$rootClientFile = Join-Path $secureRoot 'mysql-root.cnf'
$appClientFile = Join-Path $secureRoot 'mysql-app.cnf'
$credentialsPath = Join-Path $secureRoot 'credentials.json'
$myIni = Join-Path $dataRoot 'my.ini'
$mysqlPidPath = Join-Path $dataRoot 'mysql.pid'
$phpPidPath = Join-Path $dataRoot 'php.pid'
$databaseName = 'inventory_sys_db'

foreach ($requiredFile in @($mysqlExe,$mysqlAdminExe,$mysqldExe,$mysqlDumpExe,$phpExe,$schemaPath,$migrationPath,$routerPath)) {
    if (-not (Test-Path $requiredFile -PathType Leaf)) { throw "Required packaged file is missing: $requiredFile" }
}
New-Item -ItemType Directory -Force -Path $dataRoot,$logRoot,$backupRoot,$mysqlData,$secureRoot | Out-Null
$identity = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$grant = "${identity}:(OI)(CI)F"
& icacls.exe $dataRoot /inheritance:r /grant:r $grant /C | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Could not secure the application data directory.' }
& icacls.exe $dataRoot /T /grant:r "${identity}:F" /C | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Could not secure existing application data files.' }
& icacls.exe $dataRoot /grant:r $grant /C | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Could not configure inherited access for application data.' }
function Write-StartupStage([string]$Stage) {
    [IO.File]::AppendAllText((Join-Path $logRoot 'startup-steps.log'),("{0:o} {1}`r`n" -f [DateTime]::Now,$Stage),[Text.UTF8Encoding]::new($false))
}
Write-StartupStage 'created data directories'

function New-LocalSecret {
    $bytes = New-Object byte[] 48
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    return [Convert]::ToBase64String($bytes).TrimEnd('=').Replace('+','A').Replace('/','B')
}

function Get-FreePort([int]$Preferred) {
    if ($Preferred -gt 0) {
        $probe = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback,$Preferred)
        try { $probe.Start(); return $Preferred } catch { } finally { if ($probe.Server.IsBound) { $probe.Stop() } }
    }
    $probe = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback,0)
    $probe.Start()
    try { return ([Net.IPEndPoint]$probe.LocalEndpoint).Port } finally { $probe.Stop() }
}

function Get-OwnedProcess([string]$Name,[string]$Executable,[string[]]$Markers) {
    foreach ($proc in Get-CimInstance Win32_Process -Filter "Name='$Name'" -ErrorAction SilentlyContinue) {
        if (-not $proc.CommandLine) { continue }
        $sameExe = $proc.ExecutablePath -and $proc.ExecutablePath.Equals($Executable,[StringComparison]::OrdinalIgnoreCase)
        $matches = $true
        foreach ($marker in $Markers) { if ($proc.CommandLine.IndexOf($marker,[StringComparison]::OrdinalIgnoreCase) -lt 0) { $matches = $false; break } }
        if ($sameExe -and $matches) { return Get-Process -Id $proc.ProcessId -ErrorAction SilentlyContinue }
    }
    return $null
}

$ports = if (Test-Path $portsPath) { Get-Content $portsPath -Raw | ConvertFrom-Json } else { $null }
$mysqlPort = if ($ports -and $ports.mysql) { [int]$ports.mysql } else { Get-FreePort 33127 }
$webPort = if ($ports -and $ports.web) { [int]$ports.web } else { Get-FreePort 8765 }
$mysqlProcess = $null
if (Test-Path $mysqlPidPath) {
    $trackedId = 0
    if ([int]::TryParse((Get-Content $mysqlPidPath -Raw),[ref]$trackedId)) {
        $candidate = Get-Process -Id $trackedId -ErrorAction SilentlyContinue
        $details = if ($candidate) { Get-CimInstance Win32_Process -Filter "ProcessId=$trackedId" -ErrorAction SilentlyContinue }
        if ($candidate -and $details.ExecutablePath -ieq $mysqldExe -and $details.CommandLine.IndexOf($myIni,[StringComparison]::OrdinalIgnoreCase) -ge 0) { $mysqlProcess = $candidate }
    }
}
if (-not $mysqlProcess) { $mysqlProcess = Get-OwnedProcess 'mysqld.exe' $mysqldExe @($myIni) }
if (-not $mysqlProcess) { $mysqlPort = Get-FreePort $mysqlPort }

$phpProcess = $null
if (Test-Path $phpPidPath) {
    $trackedId = 0
    if ([int]::TryParse((Get-Content $phpPidPath -Raw),[ref]$trackedId)) {
        $candidate = Get-Process -Id $trackedId -ErrorAction SilentlyContinue
        $details = if ($candidate) { Get-CimInstance Win32_Process -Filter "ProcessId=$trackedId" -ErrorAction SilentlyContinue }
        if ($candidate -and $details.ExecutablePath -ieq $phpExe -and $details.CommandLine.IndexOf($routerPath,[StringComparison]::OrdinalIgnoreCase) -ge 0 -and $details.CommandLine.IndexOf('-S 127.0.0.1:',[StringComparison]::OrdinalIgnoreCase) -ge 0) { $phpProcess = $candidate }
    }
}
if (-not $phpProcess) { $webPort = Get-FreePort $webPort }
$ports = [ordered]@{ mysql=$mysqlPort; web=$webPort }
[IO.File]::WriteAllText($portsPath,($ports | ConvertTo-Json),[Text.UTF8Encoding]::new($false))

$mysqlRootConfig = $mysqlRoot.Replace('\','/')
$mysqlDataConfig = $mysqlData.Replace('\','/')
$logConfig = (Join-Path $logRoot 'mysql.err').Replace('\','/')
$secureConfig = $secureRoot.Replace('\','/')
$serverConfig = @('[mysqld]',"basedir=`"$mysqlRootConfig`"","datadir=`"$mysqlDataConfig`"","port=$mysqlPort",'bind-address=127.0.0.1','mysqlx=0',"secure-file-priv=`"$secureConfig`"",'max_allowed_packet=64M',"log-error=`"$logConfig`"") -join "`r`n"
[IO.File]::WriteAllText($myIni,$serverConfig,[Text.UTF8Encoding]::new($false))

if (-not (Test-Path (Join-Path $mysqlData 'auto.cnf'))) {
    $initialize = Start-Process -FilePath $mysqldExe -ArgumentList "--defaults-file=`"$myIni`" --initialize-insecure" -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logRoot 'mysql-init.out') -RedirectStandardError (Join-Path $logRoot 'mysql-init.err')
    if ($initialize.ExitCode -ne 0) { throw "MySQL data initialization failed. Review $logRoot\mysql-init.err" }
}

if (-not $mysqlProcess) {
    $mysqlProcess = Start-Process -FilePath $mysqldExe -ArgumentList "--defaults-file=`"$myIni`" --console" -PassThru -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logRoot 'mysql.out') -RedirectStandardError (Join-Path $logRoot 'mysql.err')
    [IO.File]::WriteAllText($mysqlPidPath,[string]$mysqlProcess.Id)
}

function Invoke-RootMysql([string[]]$Arguments) {
    $prefix = @('--protocol=TCP','--host=127.0.0.1',"--port=$mysqlPort",'--user=root')
    if (Test-Path $rootClientFile) { $prefix = @("--defaults-extra-file=$rootClientFile") + $prefix }
    & $mysqlExe @prefix @Arguments
    if ($LASTEXITCODE -ne 0) { throw "MySQL administrative operation failed. Review $logRoot\mysql.err" }
}

$ready = $false
for ($attempt=0; $attempt -lt 60; $attempt++) {
    if (Test-Path $rootClientFile) {
        & $mysqlAdminExe "--defaults-extra-file=$rootClientFile" --protocol=TCP --host=127.0.0.1 "--port=$mysqlPort" --user=root ping 2>$null | Out-Null
    } else {
        & $mysqlAdminExe --protocol=TCP --host=127.0.0.1 "--port=$mysqlPort" --user=root ping 2>$null | Out-Null
    }
    if ($LASTEXITCODE -eq 0) { $ready=$true; break }
    if (-not (Get-Process -Id $mysqlProcess.Id -ErrorAction SilentlyContinue)) { break }
    Start-Sleep -Milliseconds 500
}
if (-not $ready) { throw "Bundled MySQL did not become ready. Review $logRoot\mysql.err" }
Write-StartupStage 'MySQL ready'

if (-not (Test-Path $rootClientFile)) {
    $rootPassword = New-LocalSecret
    Invoke-RootMysql @("--execute=ALTER USER 'root'@'localhost' IDENTIFIED BY '$rootPassword'")
    [IO.File]::WriteAllText($rootClientFile,"[client]`r`nuser=root`r`npassword=$rootPassword`r`nhost=127.0.0.1`r`nport=$mysqlPort`r`n",[Text.UTF8Encoding]::new($false))
} else {
    $rootText = Get-Content $rootClientFile -Raw
    $rootText = [Regex]::Replace($rootText,'(?m)^port=.*$',"port=$mysqlPort")
    [IO.File]::WriteAllText($rootClientFile,$rootText,[Text.UTF8Encoding]::new($false))
}
Write-StartupStage 'root database credential file ready'

$client = @("--defaults-extra-file=$rootClientFile",'--protocol=TCP','--host=127.0.0.1',"--port=$mysqlPort",'--user=root')
& $mysqlAdminExe @client ping 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) { throw "The local database credential file cannot authenticate. Preserve the data folder and contact support." }
Write-StartupStage 'root database credential verified'

$credentials = if (Test-Path $credentialsPath) { Get-Content $credentialsPath -Raw | ConvertFrom-Json } else { $null }
if (-not $credentials -or -not $credentials.appPassword) { $credentials = [pscustomobject]@{appPassword=(New-LocalSecret)} }
[IO.File]::WriteAllText($appClientFile,"[client]`r`nuser=inventory_app`r`npassword=$($credentials.appPassword)`r`nhost=127.0.0.1`r`nport=$mysqlPort`r`n",[Text.UTF8Encoding]::new($false))

$tables = (& $mysqlExe @client --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$databaseName'").Trim()
if ($LASTEXITCODE -ne 0) { throw 'Could not inspect the local inventory database.' }
$freshDatabase = ([int]$tables -eq 0)
Write-StartupStage "database inspected; tables=$tables"
if ($freshDatabase) {
    Invoke-RootMysql @("--execute=CREATE DATABASE $databaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
    $source = 'source "' + $schemaPath.Replace('\','/') + '"'
    & $mysqlExe @client "--database=$databaseName" "--execute=$source"
    if ($LASTEXITCODE -ne 0) { throw 'Could not import the initial inventory schema. The database was left in place for recovery.' }
}

$migrationVersion = (& $mysqlExe @client --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$databaseName' AND table_name='schema_migrations'").Trim()
$migrationNeeded = ([int]$migrationVersion -eq 0)
if (-not $migrationNeeded) {
    $migrationDone = (& $mysqlExe @client "--database=$databaseName" --batch --skip-column-names --execute='SELECT COUNT(*) FROM schema_migrations WHERE version=2').Trim()
    $migrationNeeded = ([int]$migrationDone -eq 0)
}
if ($migrationNeeded -and -not $freshDatabase) {
    $backupName = 'pre-migration-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.sql'
    $backupPath = Join-Path $backupRoot $backupName
    $dumpArgs = @("--defaults-extra-file=$rootClientFile",'--protocol=TCP','--host=127.0.0.1',"--port=$mysqlPort",'--single-transaction','--routines','--triggers','--databases',$databaseName)
    $dumpError = Join-Path $logRoot 'pre-migration-backup.err'
    $dumpProcess = Start-Process -FilePath $mysqlDumpExe -ArgumentList $dumpArgs -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput $backupPath -RedirectStandardError $dumpError
    if ($dumpProcess.ExitCode -ne 0 -or -not (Test-Path $backupPath) -or (Get-Item $backupPath).Length -lt 100) { throw "The pre-migration backup could not be verified; migration was not started. Review $dumpError" }
}

if ($migrationNeeded) {
    $env:APP_ENV='local'; $env:DB_HOST='127.0.0.1'; $env:DB_PORT=[string]$mysqlPort; $env:DB_NAME=$databaseName
    $env:DB_USER='root'
    $rootText = Get-Content $rootClientFile -Raw
    $env:DB_PASS = ([Regex]::Match($rootText,'(?m)^password=([^\r\n]*)')).Groups[1].Value
    & $phpExe $migrationPath
    if ($LASTEXITCODE -ne 0) { throw 'The inventory schema migration failed. The automatic pre-migration SQL backup is in the application backups folder.' }
}
Write-StartupStage 'schema migration ready'

$appPassword = [string]$credentials.appPassword
$quotedDb = '`' + $databaseName + '`'
$userSql = @"
CREATE USER IF NOT EXISTS 'inventory_app'@'127.0.0.1' IDENTIFIED BY '$appPassword';
CREATE USER IF NOT EXISTS 'inventory_app'@'localhost' IDENTIFIED BY '$appPassword';
ALTER USER 'inventory_app'@'127.0.0.1' IDENTIFIED BY '$appPassword';
ALTER USER 'inventory_app'@'localhost' IDENTIFIED BY '$appPassword';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, CREATE TEMPORARY TABLES ON $quotedDb.* TO 'inventory_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, CREATE TEMPORARY TABLES ON $quotedDb.* TO 'inventory_app'@'localhost';
FLUSH PRIVILEGES;
"@
Invoke-RootMysql @("--execute=$userSql")
Write-StartupStage 'restricted database account provisioned'
[IO.File]::WriteAllText($credentialsPath,($credentials | ConvertTo-Json),[Text.UTF8Encoding]::new($false))
[IO.File]::WriteAllText($appClientFile,"[client]`r`nuser=inventory_app`r`npassword=$appPassword`r`nhost=127.0.0.1`r`nport=$mysqlPort`r`n",[Text.UTF8Encoding]::new($false))

$env:APP_ENV='local'; $env:DB_HOST='127.0.0.1'; $env:DB_PORT=[string]$mysqlPort; $env:DB_NAME=$databaseName
$env:DB_USER='inventory_app'; $env:DB_PASS=$appPassword
& $mysqlExe "--defaults-extra-file=$appClientFile" --protocol=TCP --host=127.0.0.1 "--port=$mysqlPort" --database=$databaseName --batch --skip-column-names --execute='SELECT COUNT(*) FROM admin' | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'The restricted application database account could not read the inventory schema.' }
Write-StartupStage 'application account verified'

if (-not $phpProcess) {
    $phpArgs = '-S 127.0.0.1:' + $webPort + ' -t "' + $appRoot + '" "' + $routerPath + '"'
    $phpProcess = Start-Process -FilePath $phpExe -ArgumentList $phpArgs -WorkingDirectory $appRoot -PassThru -WindowStyle Hidden -RedirectStandardOutput (Join-Path $logRoot 'php.out') -RedirectStandardError (Join-Path $logRoot 'php.err')
    [IO.File]::WriteAllText($phpPidPath,[string]$phpProcess.Id)
}

$url = "http://127.0.0.1:$webPort/"
$httpReady = $false
for ($attempt=0; $attempt -lt 40; $attempt++) {
    try { $response = Invoke-WebRequest -Uri $url -TimeoutSec 2 -UseBasicParsing; if ($response.StatusCode -ge 200) { $httpReady=$true; break } } catch { }
    if (-not (Get-Process -Id $phpProcess.Id -ErrorAction SilentlyContinue)) { break }
    Start-Sleep -Milliseconds 250
}
if (-not $httpReady) { throw "The local application web server did not become ready. Review $logRoot\php.err" }
Write-StartupStage 'HTTP application ready'

$adminCount = (& $mysqlExe "--defaults-extra-file=$appClientFile" --protocol=TCP --host=127.0.0.1 "--port=$mysqlPort" --database=$databaseName --batch --skip-column-names --execute='SELECT COUNT(*) FROM admin').Trim()
$targetUrl = if ([int]$adminCount -eq 0) { "${url}setup_admin.php" } else { "${url}login.php" }
if (-not $NoBrowser) {
    $edgeCandidates = @(
        (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:LOCALAPPDATA 'Microsoft\Edge\Application\msedge.exe')
    )
    $edge = $edgeCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if ($edge) {
        $profile = Join-Path $dataRoot 'browser-profile'
        $browser = Start-Process -FilePath $edge -ArgumentList @('--no-first-run','--no-default-browser-check',"--user-data-dir=`"$profile`"","--app=$targetUrl") -PassThru
        [IO.File]::WriteAllText((Join-Path $dataRoot 'browser.pid'),[string]$browser.Id)
        $browser.WaitForExit()
        Remove-Item (Join-Path $dataRoot 'browser.pid') -Force -ErrorAction SilentlyContinue
        & (Join-Path $PSScriptRoot 'stop-local.ps1')
    } else {
        # Fallback: try Chrome app mode, then open with explorer (guaranteed on all Windows)
        $chromeCandidates = @(
            (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
            (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
            (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
        )
        $chrome = $chromeCandidates | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
        if ($chrome) {
            $profile = Join-Path $dataRoot 'browser-profile'
            $browser = Start-Process -FilePath $chrome -ArgumentList @('--no-first-run','--no-default-browser-check',"--user-data-dir=`"$profile`"","--app=$targetUrl") -PassThru
            [IO.File]::WriteAllText((Join-Path $dataRoot 'browser.pid'),[string]$browser.Id)
            $browser.WaitForExit()
            Remove-Item (Join-Path $dataRoot 'browser.pid') -Force -ErrorAction SilentlyContinue
            & (Join-Path $PSScriptRoot 'stop-local.ps1')
        } else {
            # Generic fallback: use explorer.exe which is always available and opens default browser
            Start-Process -FilePath "$env:WINDIR\explorer.exe" -ArgumentList $targetUrl
        }
    }
}
} catch {
    if (-not $QuietErrors) {
        Add-Type -AssemblyName System.Windows.Forms
        [System.Windows.Forms.MessageBox]::Show($_.Exception.Message,'Inventory System could not start',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Error) | Out-Null
    }
    throw
} finally {
    if ($ownsInstance) { $instanceMutex.ReleaseMutex() }
    $instanceMutex.Dispose()
}
