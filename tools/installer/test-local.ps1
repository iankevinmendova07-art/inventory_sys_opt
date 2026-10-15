param([switch]$NoBrowser)

$ErrorActionPreference = 'Stop'
$testRoot = Join-Path ([IO.Path]::GetTempPath()) ('inventory-local-test-' + [Guid]::NewGuid().ToString('N'))
$env:LOCALAPPDATA = Join-Path $testRoot 'Local'
$env:APPDATA = Join-Path $testRoot 'Roaming'
$env:TEMP = Join-Path $testRoot 'Temp'
$env:TMP = $env:TEMP
$env:INVENTORY_MYSQL_PORT = ''
$env:INVENTORY_WEB_PORT = ''
$powerShellPath = Join-Path $env:WINDIR 'System32\WindowsPowerShell\v1.0\powershell.exe'
$installer = Get-ChildItem (Join-Path $PSScriptRoot 'output') -Filter 'InventorySystemSetup*.exe' -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName
$installRoot = Join-Path $env:LOCALAPPDATA 'Programs\InventorySystem'
$setupProcess = $null
if (-not $installer -or -not (Test-Path $installer -PathType Leaf)) { throw 'Build the installer before running this test.' }
New-Item -ItemType Directory -Force -Path $env:LOCALAPPDATA,$env:APPDATA,$env:TEMP | Out-Null
$conflictListeners = New-Object System.Collections.Generic.List[System.Net.Sockets.TcpListener]
$conflictWebPort = $false
$conflictMySqlPort = $false
foreach ($conflict in @(@{Port=8765;Key='web'},@{Port=33127;Key='mysql'})) {
    $listener = [System.Net.Sockets.TcpListener]::new([System.Net.IPAddress]::Loopback,$conflict.Port)
    try {
        $listener.Start()
        $conflictListeners.Add($listener)
        if ($conflict.Key -eq 'web') { $conflictWebPort = $true } else { $conflictMySqlPort = $true }
    } catch {
        $listener.Stop()
    }
}

try {
    $setupProcess = Start-Process -FilePath $installer -ArgumentList '--quiet' -PassThru -WindowStyle Hidden
    if (-not $setupProcess.WaitForExit(180000)) {
        $stageLog = Join-Path $env:LOCALAPPDATA 'InventorySystem\logs\startup-steps.log'
        $stages = try { if (Test-Path $stageLog) { Get-Content $stageLog -Raw } else { 'No service startup stage log was written.' } } catch { "Could not read startup log: $($_.Exception.Message)" }
        throw "Installer integration test timed out after 180 seconds. Last startup stages:`r`n$stages"
    }
    if ($setupProcess.ExitCode -ne 0) { throw "Setup exited with code $($setupProcess.ExitCode). Review $env:LOCALAPPDATA\InventorySystem\logs." }

    $dataRoot = Join-Path $env:LOCALAPPDATA 'InventorySystem'
    $ports = Get-Content (Join-Path $dataRoot 'secure\ports.json') -Raw | ConvertFrom-Json
    foreach ($listener in $conflictListeners) { $listener.Stop() }
    $conflictListeners.Clear()
    if (($conflictWebPort -and [int]$ports.web -eq 8765) -or ($conflictMySqlPort -and [int]$ports.mysql -eq 33127)) { throw 'A port conflict was not resolved to an available loopback port.' }
    $appClient = Join-Path $dataRoot 'secure\mysql-app.cnf'
    $rootClient = Join-Path $dataRoot 'secure\mysql-root.cnf'
    $mysql = Join-Path $installRoot 'runtime\mysql\bin\mysql.exe'
    $mysqlDump = Join-Path $installRoot 'runtime\mysql\bin\mysqldump.exe'
    $rootArgs = @("--defaults-extra-file=$rootClient",'--protocol=TCP','--host=127.0.0.1',"--port=$($ports.mysql)",'--user=root')
    $page = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/setup_admin.php" -UseBasicParsing -TimeoutSec 10
    if ($page.StatusCode -ne 200 -or $page.Content -notmatch 'Create administrator') { throw 'The initial administrator setup page did not load.' }
    $mysqlLog = Get-Content (Join-Path $dataRoot 'logs\mysql.err') -Raw
    if ($mysqlLog -match 'Can.t open shared library|Missing data directory for ICU') { throw 'The bundled MySQL runtime is missing a required component or ICU data file.' }

    $tableCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='inventory_sys_db'").Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$tableCount -lt 14) { throw 'The fresh application schema was incomplete or its restricted database account failed.' }
    $repairUser = 'schema_repair_' + [Guid]::NewGuid().ToString('N')
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=INSERT INTO admin (admin_name,username,password,role) VALUES ('Schema Repair Test','$repairUser','not-a-real-login','Admin'); DROP TABLE transaction_log"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the isolated partial-schema repair check.' }
    $startScript = Join-Path $installRoot 'tools\installer\start-local.ps1'
    $repair = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$startScript`" -NoBrowser -QuietErrors" -PassThru -Wait -WindowStyle Hidden
    if ($repair.ExitCode -ne 0) { throw 'Startup did not repair the isolated partial schema.' }
    $repairedTable = (& $mysql @rootArgs "--database=inventory_sys_db" --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='inventory_sys_db' AND table_name='transaction_log'").Trim()
    $preservedUser = (& $mysql @rootArgs "--database=inventory_sys_db" --batch --skip-column-names --execute="SELECT COUNT(*) FROM admin WHERE username='$repairUser'").Trim()
    $schemaRepairBackups = @(Get-ChildItem (Join-Path $dataRoot 'backups') -Filter 'pre-schema-repair-*.sql' -File)
    if ($LASTEXITCODE -ne 0 -or [int]$repairedTable -ne 1 -or [int]$preservedUser -ne 1 -or $schemaRepairBackups.Count -lt 1 -or $schemaRepairBackups[-1].Length -lt 100) {
        throw 'Partial-schema repair did not restore the missing table, preserve existing rows, and create a verified backup.'
    }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=DELETE FROM admin WHERE username='$repairUser'"
    if ($LASTEXITCODE -ne 0) { throw 'Could not remove the isolated partial-schema repair test row.' }
    Write-Host 'PASS: partial schema repair preserved existing rows and created a backup.'
    $grants = (& $mysql @rootArgs --execute="SHOW GRANTS FOR 'inventory_app'@'127.0.0.1'") -join "`n"
    if ($LASTEXITCODE -ne 0 -or $grants -match '(?m)^GRANT (?!USAGE ON).*ON \*\.\*' -or $grants -match 'ALL PRIVILEGES') { throw 'The application database account received excessive privileges.' }
    $acl = (& icacls.exe $dataRoot) -join "`n"
    if ($acl -match 'CodexSandboxUsers|BUILTIN\\Users|Everyone|Authenticated Users|NT AUTHORITY\\SYSTEM') { throw 'Application data is readable by other local Windows users.' }
    $bindAddress = (Select-String -Path (Join-Path $dataRoot 'my.ini') -Pattern '^bind-address=').Line
    if ($bindAddress -ne 'bind-address=127.0.0.1') { throw 'MySQL is not restricted to loopback.' }
    $assets = @('/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css','/assets/vendor/bootstrap-icons/font/fonts/bootstrap-icons.woff2','/assets/vendor/bootstrap/bootstrap.min.css','/assets/vendor/jquery/jquery.min.js','/assets/vendor/chartjs/chart.umd.min.js','/assets/vendor/fontawesome/webfonts/fa-solid-900.woff2')
    foreach ($asset in $assets) {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)$asset" -UseBasicParsing -TimeoutSec 10
        if ($response.StatusCode -ne 200) { throw "Packaged offline asset failed to load: $asset" }
    }

    $dummyUser = 'release_smoke_' + [Guid]::NewGuid().ToString('N')
    $insert = "INSERT INTO admin (admin_name,username,password,role) VALUES ('Release Smoke Test','$dummyUser','not-a-real-login','Admin')"
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=$insert"
    if ($LASTEXITCODE -ne 0) { throw 'Could not insert an isolated backup/restore smoke-test record.' }
    $sqlBackup = Join-Path $dataRoot 'backups\release-smoke.sql'
    $dumpArgs = @("--defaults-extra-file=$rootClient",'--protocol=TCP','--host=127.0.0.1',"--port=$($ports.mysql)",'--single-transaction','--databases','inventory_sys_db')
    $dump = Start-Process -FilePath $mysqlDump -ArgumentList $dumpArgs -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput $sqlBackup -RedirectStandardError (Join-Path $dataRoot 'logs\test-dump.err')
    if ($dump.ExitCode -ne 0 -or (Get-Item $sqlBackup).Length -lt 100) { throw 'Database smoke-test backup was not produced.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=UPDATE admin SET admin_name='Changed During Restore Test' WHERE username='$dummyUser'"
    $restoreSource = 'source "' + $sqlBackup.Replace('\','/') + '"'
    & $mysql @rootArgs "--execute=$restoreSource"
    if ($LASTEXITCODE -ne 0) { throw 'The database smoke-test backup could not be restored.' }
    $restoredName = (& $mysql @rootArgs "--database=inventory_sys_db" --batch --skip-column-names --execute="SELECT admin_name FROM admin WHERE username='$dummyUser'").Trim()
    if ($LASTEXITCODE -ne 0 -or $restoredName -ne 'Release Smoke Test') { throw 'Restored test data did not match the saved database backup.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=DELETE FROM admin WHERE username='$dummyUser'"
    if ($LASTEXITCODE -ne 0) { throw 'Could not remove the dummy backup/restore test row.' }

    $backupSmokeUser = 'settings_backup_' + [Guid]::NewGuid().ToString('N')
    $backupSmokePassword = 'BackupSmoke-2026!'
    $backupSmokeHash = (& (Join-Path $installRoot 'runtime\php\php.exe') -r "echo password_hash('$backupSmokePassword', PASSWORD_DEFAULT);").Trim()
    if ($LASTEXITCODE -ne 0 -or -not $backupSmokeHash) { throw 'Could not prepare the Settings backup endpoint test account.' }
    $backupSmokeInsert = "INSERT INTO admin (admin_name,username,password,role) VALUES ('Settings Backup Test','$backupSmokeUser','$backupSmokeHash','Admin')"
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=$backupSmokeInsert"
    if ($LASTEXITCODE -ne 0) { throw 'Could not create the Settings backup endpoint test account.' }

    $backupSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/login.php" -WebSession $backupSession -UseBasicParsing -TimeoutSec 10
    if ($loginPage.Content -notmatch 'name="csrf_token" value="([^"]+)"') { throw 'Could not read the login CSRF token for the Settings backup endpoint test.' }
    $loginToken = $Matches[1]
    $loginResponse = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/controllers/login/login_process.php" -Method Post -WebSession $backupSession -Body @{csrf_token=$loginToken;username=$backupSmokeUser;password=$backupSmokePassword} -UseBasicParsing -TimeoutSec 10
    $loginResult = $loginResponse.Content | ConvertFrom-Json
    if ($loginResponse.StatusCode -ne 200 -or $loginResult.status -ne 'success') { throw 'Could not authenticate the Settings backup endpoint test account.' }

    $settingsBackupPath = Join-Path $dataRoot 'backups\settings-endpoint-smoke.sql'
    $settingsBackupResponse = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/controllers/setting/backup_database.php" -WebSession $backupSession -OutFile $settingsBackupPath -PassThru -UseBasicParsing -TimeoutSec 30
    $settingsBackupSql = [IO.File]::ReadAllText($settingsBackupPath)
    if ($settingsBackupResponse.StatusCode -ne 200 -or $settingsBackupResponse.Headers['Content-Type'] -notmatch 'application/sql' -or $settingsBackupSql -notmatch 'Backup completed successfully' -or $settingsBackupSql -notmatch [regex]::Escape($backupSmokeUser)) {
        throw 'The Settings database backup download did not return a complete SQL export.'
    }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=UPDATE admin SET admin_name='Changed During Backup Restore Test' WHERE username='$backupSmokeUser'"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the Settings backup restore check.' }
    $settingsBackupSource = 'source "' + $settingsBackupPath.Replace('\','/') + '"'
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=$settingsBackupSource"
    if ($LASTEXITCODE -ne 0) { throw 'The Settings backup SQL file could not be restored.' }
    $restoredBackupName = (& $mysql @rootArgs "--database=inventory_sys_db" --batch --skip-column-names --execute="SELECT admin_name FROM admin WHERE username='$backupSmokeUser'").Trim()
    if ($LASTEXITCODE -ne 0 -or $restoredBackupName -ne 'Settings Backup Test') { throw 'The Settings backup did not restore the saved database contents.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=DELETE FROM admin WHERE username='$backupSmokeUser'"
    if ($LASTEXITCODE -ne 0) { throw 'Could not remove the Settings backup endpoint test account.' }

    & $mysql @rootArgs "--database=inventory_sys_db" '--execute=DROP TABLE schema_migrations'
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the isolated pre-migration backup check.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=INSERT INTO admin (admin_name,username,password,role) VALUES ('Upgrade Preservation Test','upgrade_preservation','not-a-real-login','Admin')"
    if ($LASTEXITCODE -ne 0) { throw 'Could not insert the isolated upgrade-preservation marker.' }
    $upgrade = Start-Process -FilePath $installer -ArgumentList '--quiet' -PassThru -WindowStyle Hidden
    if (-not $upgrade.WaitForExit(120000) -or $upgrade.ExitCode -ne 0) { throw 'Upgrade/reinstall did not finish successfully.' }
    $afterUpgrade = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT admin_name FROM admin WHERE username='upgrade_preservation'").Trim()
    if ($LASTEXITCODE -ne 0 -or $afterUpgrade -ne 'Upgrade Preservation Test') { throw 'The inventory database record did not survive upgrade.' }
    $migrationBackups = @(Get-ChildItem (Join-Path $dataRoot 'backups') -Filter 'pre-migration-*.sql' -File)
    if ($migrationBackups.Count -lt 1 -or $migrationBackups[-1].Length -lt 100) { throw 'A pre-migration backup was not created and verified.' }

    $appBrowser = @(
        (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:LOCALAPPDATA 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'Google\Chrome\Application\chrome.exe'),
        (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
    ) | Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if ($appBrowser -and -not $NoBrowser) {
        $startScript = Join-Path $installRoot 'tools\installer\start-local.ps1'
        $launcher = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$startScript`"" -PassThru -WindowStyle Hidden
        $browserPidFile = Join-Path $dataRoot 'browser.pid'
        $deadline = [DateTime]::UtcNow.AddSeconds(30)
        while (-not (Test-Path $browserPidFile) -and [DateTime]::UtcNow -lt $deadline) { Start-Sleep -Milliseconds 250 }
        if (-not (Test-Path $browserPidFile)) { throw 'The desktop app window did not launch.' }
        $browserPid = [int](Get-Content $browserPidFile -Raw)
        $browserDetails = Get-CimInstance Win32_Process -Filter "ProcessId=$browserPid" -ErrorAction SilentlyContinue
        if ($browserDetails.Name -ine (Split-Path -Leaf $appBrowser) -or $browserDetails.CommandLine -notmatch '--app=' -or $browserDetails.CommandLine.IndexOf((Join-Path $dataRoot 'browser-profile'),[StringComparison]::OrdinalIgnoreCase) -lt 0) { throw 'The launcher did not open the browser in the dedicated app window/profile.' }
        $stopScript = Join-Path $installRoot 'tools\installer\stop-local.ps1'
        $stop = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$stopScript`"" -PassThru -Wait -WindowStyle Hidden
        if ($stop.ExitCode -ne 0) { throw 'The app stop action failed.' }
        $deadline = [DateTime]::UtcNow.AddSeconds(20)
        while (-not $launcher.HasExited -and [DateTime]::UtcNow -lt $deadline) { Start-Sleep -Milliseconds 250 }
        if (-not $launcher.HasExited) { throw 'Closing the desktop window did not finish its launcher.' }
        Write-Host "PASS: $((Split-Path -Leaf $appBrowser)) app window launch and owned-service shutdown."
    }
    Write-Host 'PASS: clean install, local UI assets, restricted account, loopback, SQL backup/restore, Settings SQL download/restore, migration backup, and upgrade preservation.'

    $uninstaller = Join-Path $installRoot 'tools\installer\remove-local.ps1'
    $remove = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$uninstaller`" -RetainData" -PassThru -Wait -WindowStyle Hidden
    if ($remove.ExitCode -ne 0) { throw 'Uninstall with data retention failed.' }
    $deadline = [DateTime]::UtcNow.AddSeconds(30)
    while ((Test-Path $installRoot) -and [DateTime]::UtcNow -lt $deadline) { Start-Sleep -Milliseconds 250 }
    if (Test-Path $installRoot) { throw 'Uninstaller did not remove the application files.' }
    if (-not (Test-Path (Join-Path $dataRoot 'mysql\auto.cnf'))) { throw 'Uninstall did not retain the local database.' }
    Write-Host 'PASS: uninstall removed program files and preserved application data.'
} finally {
    foreach ($listener in $conflictListeners) { if ($listener.Server.IsBound) { $listener.Stop() } }
    $shutdown = Join-Path $installRoot 'tools\installer\stop-local.ps1'
    if (Test-Path $shutdown) { & $powerShellPath -NoProfile -ExecutionPolicy Bypass -File $shutdown }
    if ($launcher -and -not $launcher.HasExited) {
        Stop-Process -Id $launcher.Id -Force -ErrorAction SilentlyContinue
        Wait-Process -Id $launcher.Id -Timeout 10 -ErrorAction SilentlyContinue
    }
    if ($setupProcess -and -not $setupProcess.HasExited) { Stop-Process -Id $setupProcess.Id -Force -ErrorAction SilentlyContinue }
    $resolvedTest = if (Test-Path $testRoot) { (Resolve-Path -LiteralPath $testRoot).Path } else { $null }
    $tempRoot = (Resolve-Path -LiteralPath ([IO.Path]::GetTempPath())).Path
    if ($resolvedTest -and $resolvedTest.StartsWith($tempRoot,[StringComparison]::OrdinalIgnoreCase)) {
        & icacls.exe $resolvedTest /reset /T /C | Out-Null
        Remove-Item -LiteralPath $resolvedTest -Recurse -Force -ErrorAction SilentlyContinue
    }
    Remove-Item Env:\LOCALAPPDATA,Env:\APPDATA,Env:\TEMP,Env:\TMP,Env:\INVENTORY_MYSQL_PORT,Env:\INVENTORY_WEB_PORT -ErrorAction SilentlyContinue
}
