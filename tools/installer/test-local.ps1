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
    if ($page.StatusCode -ne 200 -or $page.Content -notmatch 'Initial System Setup') { throw 'The initial administrator setup page did not load.' }
    $mysqlLog = Get-Content (Join-Path $dataRoot 'logs\mysql.err') -Raw
    if ($mysqlLog -match 'Can.t open shared library|Missing data directory for ICU') { throw 'The bundled MySQL runtime is missing a required component or ICU data file.' }

    $tableCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='inventory_sys_db'").Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$tableCount -lt 14) { throw 'The fresh application schema was incomplete or its restricted database account failed.' }
    $jurisdictionColumnCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='inventory_sys_db' AND table_name='school_details' AND column_name IN ('Region','Division')").Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$jurisdictionColumnCount -ne 2) { throw 'The fresh application schema is missing the school Region or Division column.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=INSERT INTO school_details (Region,Division,school_name,school_address,head_name,head_position,custodian_name,custodian_position) VALUES ('Old Region','Old Division','Migration Preservation School','Migration Address','Old Head','Old Position','Old Custodian','Old Position'); DELETE FROM schema_migrations WHERE version=3; ALTER TABLE school_details DROP COLUMN Region, DROP COLUMN Division"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the isolated version-2 schema upgrade.' }
    $startScript = Join-Path $installRoot 'tools\installer\start-local.ps1'
    $migrationUpgrade = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$startScript`" -NoBrowser -QuietErrors" -PassThru -Wait -WindowStyle Hidden
    if ($migrationUpgrade.ExitCode -ne 0) { throw 'The isolated version-2 database did not migrate to the Region/Division schema.' }
    $upgradedJurisdictionColumnCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='inventory_sys_db' AND table_name='school_details' AND column_name IN ('Region','Division')").Trim()
    $preservedSchool = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT school_name FROM school_details WHERE school_name='Migration Preservation School'").Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$upgradedJurisdictionColumnCount -ne 2 -or $preservedSchool -ne 'Migration Preservation School') { throw 'The version-2 migration did not add jurisdiction fields while preserving existing school rows.' }
    Write-Host 'PASS: version-2 migration adds Region/Division and preserves existing school records.'
    $repairUser = 'schema_repair_' + [Guid]::NewGuid().ToString('N')
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=INSERT INTO admin (admin_name,username,password,role) VALUES ('Schema Repair Test','$repairUser','not-a-real-login','Admin'); DROP TABLE transaction_log"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the isolated partial-schema repair check.' }
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

    $backupSmokeUser = 'setup_smoke_' + [Guid]::NewGuid().ToString('N').Substring(0,12)
    $backupSmokePassword = 'Qa1234'
    $backupSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $setupPage = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/setup_admin.php" -WebSession $backupSession -UseBasicParsing -TimeoutSec 10
    if ($setupPage.Content -notmatch 'name="csrf_token" value="([^"]+)"') { throw 'Could not read the setup CSRF token.' }
    $setupToken = $Matches[1]
    $setupResponse = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/setup_admin.php" -Method Post -WebSession $backupSession -Body @{
        csrf_token=$setupToken; username=$backupSmokeUser; password=$backupSmokePassword; password_confirmation=$backupSmokePassword;
        region='Region VIII - Eastern Visayas'; division='Smoke Test Division'; school_name='SMOKE TEST SCHOOL'; school_address='Smoke Test Address'
    } -UseBasicParsing -TimeoutSec 15
    $setupAdminCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute='SELECT COUNT(*) FROM admin').Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$setupAdminCount -ne 1 -or $setupResponse.StatusCode -ne 200) { throw 'The initial administrator setup form did not create its administrator.' }

    $loginPage = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/login.php" -WebSession $backupSession -UseBasicParsing -TimeoutSec 10
    if ($loginPage.Content -notmatch 'name="csrf_token" value="([^"]+)"') { throw 'Could not read the login CSRF token for the Settings backup endpoint test.' }
    $loginToken = $Matches[1]
    $loginResponse = Invoke-WebRequest -Uri "http://127.0.0.1:$($ports.web)/controllers/login/login_process.php" -Method Post -WebSession $backupSession -Body @{csrf_token=$loginToken;username=$backupSmokeUser;password=$backupSmokePassword} -UseBasicParsing -TimeoutSec 10
    $loginResult = $loginResponse.Content | ConvertFrom-Json
    if ($loginResponse.StatusCode -ne 200 -or $loginResult.status -ne 'success') { throw 'Could not authenticate the Settings backup endpoint test account.' }

    $baseUri = "http://127.0.0.1:$($ports.web)/"
    $pagePaths = @('index.php','consup.php','nonconsup.php','sciene_math.php','textbooks.php','setting.php')
    foreach ($pagePath in $pagePaths) {
        $pageResponse = Invoke-WebRequest -Uri ($baseUri + $pagePath) -WebSession $backupSession -UseBasicParsing -TimeoutSec 15
        if ($pageResponse.StatusCode -ne 200 -or $pageResponse.Content -match 'Database Connection Error|Fatal error|Uncaught PDOException') { throw "Authenticated page failed: $pagePath" }
    }
    $settingsPage = Invoke-WebRequest -Uri ($baseUri + 'setting.php') -WebSession $backupSession -UseBasicParsing -TimeoutSec 15
    if ($settingsPage.Content -notmatch 'name="csrf-token" content="([^"]+)"') { throw 'Could not read the application CSRF token.' }
    $appCsrfToken = $Matches[1]
    function Invoke-QaJsonPost([string]$Path, [hashtable]$Body) {
        $Body['csrf_token'] = $appCsrfToken
        $response = Invoke-WebRequest -Uri ($baseUri + $Path) -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken;'Accept'='application/json'} -Body $Body -UseBasicParsing -TimeoutSec 20
        $result = $response.Content | ConvertFrom-Json
        if ($result.status -ne 'success') { throw "POST $Path failed: $($result.message)" }
        return $result
    }
    function Get-QaScalar([string]$Sql) {
        $value = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names "--execute=$Sql").Trim()
        if ($LASTEXITCODE -ne 0) { throw "Test database query failed: $Sql" }
        return $value
    }

    $testAdminDbId = Get-QaScalar "SELECT id FROM admin WHERE username='$backupSmokeUser' LIMIT 1"
    $updatedTestPassword = 'Qa5678'
    [void](Invoke-QaJsonPost 'controllers/admin/process_admin.php' @{admin_id=$testAdminDbId;admin_name='QA Administrator Updated';username=$backupSmokeUser;old_password=$backupSmokePassword;new_password=$updatedTestPassword;confirm_password=$updatedTestPassword})
    $backupSmokePassword = $updatedTestPassword
    $badAdminUpdate = Invoke-WebRequest -Uri ($baseUri + 'controllers/admin/process_admin.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken;'Accept'='application/json'} -Body @{admin_id=$testAdminDbId;admin_name='Should Not Save';username=$backupSmokeUser;old_password='wrong-password';csrf_token=$appCsrfToken} -UseBasicParsing -TimeoutSec 15
    if (($badAdminUpdate.Content | ConvertFrom-Json).status -ne 'error' -or (Get-QaScalar "SELECT admin_name FROM admin WHERE id=$testAdminDbId") -ne 'QA Administrator Updated') { throw 'Admin profile accepted an incorrect current password or changed data after rejection.' }
    $newLoginSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $newLoginPage = Invoke-WebRequest -Uri ($baseUri + 'login.php') -WebSession $newLoginSession -UseBasicParsing -TimeoutSec 10
    if ($newLoginPage.Content -notmatch 'name="csrf_token" value="([^"]+)"') { throw 'Could not read CSRF token for updated-password login.' }
    $newLogin = Invoke-WebRequest -Uri ($baseUri + 'controllers/login/login_process.php') -Method Post -WebSession $newLoginSession -Body @{csrf_token=$Matches[1];username=$backupSmokeUser;password=$backupSmokePassword} -UseBasicParsing -TimeoutSec 10
    if (($newLogin.Content | ConvertFrom-Json).status -ne 'success') { throw 'Updated admin password could not be used to log in.' }
    $invalidLoginSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $invalidLoginPage = Invoke-WebRequest -Uri ($baseUri + 'login.php') -WebSession $invalidLoginSession -UseBasicParsing -TimeoutSec 10
    if ($invalidLoginPage.Content -notmatch 'name="csrf_token" value="([^"]+)"') { throw 'Could not read CSRF token for invalid-login test.' }
    $invalidLogin = Invoke-WebRequest -Uri ($baseUri + 'controllers/login/login_process.php') -Method Post -WebSession $invalidLoginSession -Body @{csrf_token=$Matches[1];username=$backupSmokeUser;password='wrong-password'} -UseBasicParsing -TimeoutSec 10
    if (($invalidLogin.Content | ConvertFrom-Json).status -ne 'error') { throw 'Invalid credentials were accepted.' }

    $testUnit = 'Qa Unit'
    [void](Invoke-QaJsonPost 'controllers/category/process_category.php' @{unit_name=$testUnit})
    $testUnitId = Get-QaScalar "SELECT id FROM unit_measure WHERE unit_name='$testUnit' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/category/process_edit_category.php' @{id=$testUnitId;old_category_name=$testUnit;unit_name='Qa Unit Updated'})
    $testUnit = 'Qa Unit Updated'
    $unusedUnit = 'Qa Unused Unit'
    [void](Invoke-QaJsonPost 'controllers/category/process_category.php' @{unit_name=$unusedUnit})
    $unusedUnitId = Get-QaScalar "SELECT id FROM unit_measure WHERE unit_name='$unusedUnit' LIMIT 1"

    $testPosition = 'Qa Position'
    [void](Invoke-QaJsonPost 'controllers/position/process_position.php' @{position=$testPosition})
    $testPositionId = Get-QaScalar "SELECT id FROM position WHERE position_name='$testPosition' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/position/process_edit_position.php' @{id=$testPositionId;old_position_name=$testPosition;position='Qa Position Updated'})
    $testPosition = 'Qa Position Updated'
    $unusedPosition = 'Qa Unused Position'
    [void](Invoke-QaJsonPost 'controllers/position/process_position.php' @{position=$unusedPosition})
    $unusedPositionId = Get-QaScalar "SELECT id FROM position WHERE position_name='$unusedPosition' LIMIT 1"

    $testEmployeeId = 'QA-' + [Guid]::NewGuid().ToString('N').Substring(0,8)
    [void](Invoke-QaJsonPost 'controllers/Employee/process_employee.php' @{employee_id=$testEmployeeId;name='Qa Smoke Employee';position=$testPosition;emp_phone='+639000000001'})
    $employeeDbId = Get-QaScalar "SELECT id FROM employee WHERE emp_id='$testEmployeeId' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/Employee/process_edit_employee.php' @{id=$employeeDbId;employee_id=$testEmployeeId;name='Qa Smoke Employee Updated';position=$testPosition;emp_phone='+639000000001'})
    $testEmployeeName = 'Qa Smoke Employee Updated'
    $unusedEmployeeId = 'QA-' + [Guid]::NewGuid().ToString('N').Substring(0,8)
    [void](Invoke-QaJsonPost 'controllers/Employee/process_employee.php' @{employee_id=$unusedEmployeeId;name='Qa Unused Employee';position=$unusedPosition;emp_phone='+639000000002'})
    $unusedEmployeeDbId = Get-QaScalar "SELECT id FROM employee WHERE emp_id='$unusedEmployeeId' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/Employee/process_delete_employee.php' @{id=$unusedEmployeeDbId})

    $schoolForm = @{
        csrf_token=$appCsrfToken; Region='Region VIII - Eastern Visayas'; Division='QA Schools Division'; school_name='QA SCHOOL'; school_address='QA Test Address';
        head_name=$testEmployeeName; head_position=$testPosition; custodian_name=$testEmployeeName; custodian_position=$testPosition
    }
    $schoolSave = Invoke-WebRequest -Uri ($baseUri + 'controllers/setting/save_school_details.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body $schoolForm -UseBasicParsing -TimeoutSec 15
    if ($schoolSave.StatusCode -ne 200 -or (Get-QaScalar "SELECT COUNT(*) FROM school_details WHERE Region='Region VIII - Eastern Visayas' AND Division='QA Schools Division'") -ne '1') { throw 'School details did not save the Region and Division values.' }

    $supplyCode = 'QA-' + [Guid]::NewGuid().ToString('N').Substring(0,8)
    [void](Invoke-QaJsonPost 'controllers/supplies/consumable/add_supply.php' @{itemCode=$supplyCode;itemName='QA Smoke Paper';qty='8';unit=$testUnit;reference='QA opening stock'})
    $supplyDbId = Get-QaScalar "SELECT id FROM supplies WHERE supply_code='$supplyCode' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/supplies/consumable/update_supply.php' @{id=$supplyDbId;itemCode=$supplyCode;itemName='QA Smoke Paper Updated';unit=$testUnit;reference='QA edited item'})
    [void](Invoke-QaJsonPost 'controllers/supplies/consumable/update_quantity.php' @{id=$supplyDbId;qty='12';reference='QA stock increase'})
    $supplyPage = Invoke-WebRequest -Uri ($baseUri + 'controllers/supplies/consumable/get_supplies_paginated.php?draw=1&start=0&length=10&search%5Bvalue%5D=QA') -WebSession $backupSession -UseBasicParsing -TimeoutSec 15
    if (($supplyPage.Content | ConvertFrom-Json).recordsFiltered -lt 1) { throw 'Consumable supply search/pagination did not return the test item.' }
    foreach ($dataPath in @(
        'controllers/supplies/consumable/get_quantity_list.php?draw=1&start=0&length=10',
        'controllers/supplies/consumable/get_stock_card_items.php?draw=1&start=0&length=10',
        'controllers/supplies/consumable/get_rsmi_logs_paginated.php?draw=1&start=0&length=10',
        'controllers/supplies/consumable/get_transactions_paginated.php?draw=1&start=0&length=10',
        'controllers/cart/get_employees.php',
        'controllers/cart/search_cart.php?q=QA'
    )) {
        $dataResponse = Invoke-WebRequest -Uri ($baseUri + $dataPath) -WebSession $backupSession -UseBasicParsing -TimeoutSec 15
        if ($dataResponse.StatusCode -ne 200 -or $dataResponse.Content -match 'Fatal error|Uncaught PDOException') { throw "Data endpoint failed: $dataPath" }
    }

    $releaseBody = @{
        requestKey=[Guid]::NewGuid().ToString('N'); recipients=@($testEmployeeName);
        items=@(@{supplyId=[int]$supplyDbId;itemCode=$supplyCode;itemName='QA Smoke Paper Updated';unit=$testUnit;qty=2})
    } | ConvertTo-Json -Depth 6 -Compress
    $releaseResponse = Invoke-WebRequest -Uri ($baseUri + 'controllers/cart/process_release.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -ContentType 'application/json' -Body $releaseBody -UseBasicParsing -TimeoutSec 20
    $releaseResult = $releaseResponse.Content | ConvertFrom-Json
    if ($releaseResult.status -ne 'success' -or (Get-QaScalar "SELECT supply_qty FROM supplies WHERE id=$supplyDbId") -ne '10') { throw 'Cart release did not record the transaction and deduct stock correctly.' }
    $releaseRetry = Invoke-WebRequest -Uri ($baseUri + 'controllers/cart/process_release.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -ContentType 'application/json' -Body $releaseBody -UseBasicParsing -TimeoutSec 20
    if (($releaseRetry.Content | ConvertFrom-Json).status -ne 'success' -or (Get-QaScalar "SELECT supply_qty FROM supplies WHERE id=$supplyDbId") -ne '10') { throw 'Cart retry protection duplicated a successful stock release.' }
    $insufficientBody = @{
        requestKey=[Guid]::NewGuid().ToString('N'); recipients=@($testEmployeeName);
        items=@(@{supplyId=[int]$supplyDbId;itemCode=$supplyCode;itemName='QA Smoke Paper Updated';unit=$testUnit;qty=11})
    } | ConvertTo-Json -Depth 6 -Compress
    $insufficientResponse = Invoke-WebRequest -Uri ($baseUri + 'controllers/cart/process_release.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -ContentType 'application/json' -Body $insufficientBody -UseBasicParsing -TimeoutSec 20
    if (($insufficientResponse.Content | ConvertFrom-Json).status -ne 'error' -or (Get-QaScalar "SELECT supply_qty FROM supplies WHERE id=$supplyDbId") -ne '10') { throw 'An insufficient-stock release was not rejected without changing inventory.' }
    $transactionCode = [string]$releaseResult.trans_codes[0]

    $assetForm = @{
        property_number=('QA-PROP-' + [Guid]::NewGuid().ToString('N').Substring(0,6));description='QA Asset';item_type='Equipment';unit_of_measure=$testUnit;
        unit_cost='100.00';total_cost='100.00';qty_property_card='1';qty_physical_count='1';shortage_overage_qty='0';shortage_overage_value='0.00';remarks='QA test';recipient=$testEmployeeName
    }
    [void](Invoke-QaJsonPost 'controllers/supplies/nonconsumable/add_supply.php' $assetForm)
    $assetDbId = Get-QaScalar "SELECT id FROM nonconsumable WHERE property_number='$($assetForm.property_number)' LIMIT 1"
    $assetForm['id'] = $assetDbId; $assetForm['description'] = 'QA Asset Updated'
    [void](Invoke-QaJsonPost 'controllers/supplies/nonconsumable/update_supply.php' $assetForm)

    [void](Invoke-QaJsonPost 'controllers/supplies/science_math/add_sme.php' @{lr_code='QA-SCI-01';lr_item='QA Science Item';lr_quantity='3';lr_unit=$testUnit;lr_type='Science'})
    $smeDbId = Get-QaScalar "SELECT id FROM lr_sme WHERE lr_code='QA-SCI-01' LIMIT 1"
    [void](Invoke-QaJsonPost 'controllers/supplies/science_math/update_sme.php' @{id=$smeDbId;lr_code='QA-SCI-01';lr_item='QA Science Item Updated';lr_quantity='4';lr_unit=$testUnit;lr_type='Science'})
    [void](Invoke-QaJsonPost 'controllers/supplies/science_math/add_sme.php' @{lr_code='QA-MATH-01';lr_item='QA Math Item';lr_quantity='2';lr_unit=$testUnit;lr_type='Math'})

    $textbookForm = @{lr_item='QA Textbook';grade_level='Grade 1';lr_subject='Mathematics';lr_qty='5';lr_unit=$testUnit;recipient=$testEmployeeName;condition='Good';save_textbook='1';csrf_token=$appCsrfToken}
    [void](Invoke-WebRequest -Uri ($baseUri + 'controllers/supplies/textbooks/insert_textbook.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body $textbookForm -UseBasicParsing -TimeoutSec 15)
    $textbookDbId = Get-QaScalar "SELECT id FROM lr_textbooks WHERE lr_item='QA Textbook' LIMIT 1"
    $textbookForm['id'] = $textbookDbId; $textbookForm['lr_item'] = 'QA Textbook Updated'; $textbookForm['update_textbook'] = '1'
    [void](Invoke-WebRequest -Uri ($baseUri + 'controllers/supplies/textbooks/update_textbook.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body $textbookForm -UseBasicParsing -TimeoutSec 15)

    foreach ($dataPath in @(
        'controllers/supplies/nonconsumable/get_nonconsumable_paginated.php?draw=1&start=0&length=10',
        'controllers/supplies/science_math/get_sme_paginated.php?draw=1&start=0&length=10&type=Science',
        'controllers/supplies/textbooks/get_textbooks_paginated.php?draw=1&start=0&length=10'
    )) {
        $dataResponse = Invoke-WebRequest -Uri ($baseUri + $dataPath) -WebSession $backupSession -UseBasicParsing -TimeoutSec 15
        if ($dataResponse.StatusCode -ne 200 -or $dataResponse.Content -match 'Fatal error|Uncaught PDOException') { throw "Data endpoint failed: $dataPath" }
    }

    $testPages = @(
        @{Path="controllers/supplies/consumable/print_ris.php?trans_code=$transactionCode";Needles=@('QA Schools Division','Region VIII - Eastern Visayas','QA SCHOOL')},
        @{Path='controllers/supplies/consumable/print_rsmi.php';Needles=@('QA SCHOOL')},
        @{Path="controllers/supplies/consumable/print_stock_card.php?id=$supplyDbId";Needles=@('QA Schools Division','Region VIII - Eastern Visayas')},
        @{Path=('controllers/supplies/consumable/print_rsmi_month.php?month_year=' + (Get-Date -Format 'yyyy-MM'));Needles=@('QA Schools Division','Region VIII - Eastern Visayas')},
        @{Path='controllers/supplies/nonconsumable/generate_rpcppe_pdf.php';Needles=@('QA Schools Division','Region VIII - Eastern Visayas')},
        @{Path="controllers/supplies/nonconsumable/print_supply.php?id=$assetDbId";Needles=@('QA Schools Division','Region VIII - Eastern Visayas')},
        @{Path="controllers/supplies/nonconsumable/print_property_card.php?id=$assetDbId";Needles=@('QA Asset Updated')},
        @{Path='controllers/supplies/science_math/print_lr_report.php?type=Science';Needles=@('QA Schools Division','QA Science Item Updated')},
        @{Path='controllers/supplies/science_math/print_lr_report.php?type=Math';Needles=@('QA Schools Division','QA Math Item')},
        @{Path='controllers/supplies/science_math/print_lr_report.php?type=All';Needles=@('QA Schools Division','QA Science Item Updated','QA Math Item')},
        @{Path='controllers/supplies/textbooks/print_textbook_report.php';Needles=@('QA Schools Division','QA Textbook Updated')},
        @{Path="controllers/supplies/textbooks/print_textbook.php?id=$textbookDbId";Needles=@('QA Schools Division','QA Textbook Updated')}
    )
    foreach ($testPage in $testPages) {
        $response = Invoke-WebRequest -Uri ($baseUri + $testPage.Path) -WebSession $backupSession -UseBasicParsing -TimeoutSec 20
        if ($response.StatusCode -ne 200 -or $response.Content -match 'Fatal error|Uncaught PDOException|database error occurred') { throw "Report failed: $($testPage.Path)" }
        foreach ($needle in $testPage.Needles) { if ($response.Content -notmatch [regex]::Escape($needle)) { throw "Report $($testPage.Path) omitted expected text '$needle'." } }
    }

    [void](Invoke-QaJsonPost 'controllers/supplies/science_math/delete_sme.php' @{id=$smeDbId})
    [void](Invoke-QaJsonPost 'controllers/supplies/textbooks/delete_textbook.php' @{id=$textbookDbId})
    [void](Invoke-QaJsonPost 'controllers/supplies/nonconsumable/delete_supply.php' @{id=$assetDbId})
    [void](Invoke-QaJsonPost 'controllers/supplies/consumable/delete_supply.php' @{id=$supplyDbId})
    $usedUnitDelete = Invoke-WebRequest -Uri ($baseUri + 'controllers/category/process_delete_category.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body @{id=$testUnitId;csrf_token=$appCsrfToken} -UseBasicParsing -TimeoutSec 15
    if (($usedUnitDelete.Content | ConvertFrom-Json).status -ne 'error') { throw 'Deleting a unit used by stock history should be rejected.' }
    [void](Invoke-QaJsonPost 'controllers/category/process_delete_category.php' @{id=$unusedUnitId})
    [void](Invoke-QaJsonPost 'controllers/position/process_delete_position.php' @{id=$unusedPositionId})
    $usedEmployeeDelete = Invoke-WebRequest -Uri ($baseUri + 'controllers/Employee/process_delete_employee.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body @{id=$employeeDbId;csrf_token=$appCsrfToken} -UseBasicParsing -TimeoutSec 15
    if (($usedEmployeeDelete.Content | ConvertFrom-Json).status -ne 'error') { throw 'Deleting an employee used by history should be rejected.' }
    Write-Host 'PASS: setup, authenticated pages, personnel/UOM CRUD, school settings, inventory release/idempotency, learning-resource CRUD, dynamic reports, and reference-safe deletes.'

    $clearResponse = Invoke-WebRequest -Uri ($baseUri + 'controllers/setting/clear_database_records.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body @{csrf_token=$appCsrfToken} -UseBasicParsing -TimeoutSec 30
    if (($clearResponse.Content | ConvertFrom-Json).status -ne 'success' -or (Get-QaScalar 'SELECT COUNT(*) FROM transaction_log') -ne '0' -or (Get-QaScalar 'SELECT COUNT(*) FROM lr_sme') -ne '0') { throw 'Clear Records did not remove transactional records from the test database.' }
    if ((Get-QaScalar 'SELECT COUNT(*) FROM admin') -ne '1' -or (Get-QaScalar 'SELECT COUNT(*) FROM school_details') -ne '1') { throw 'Clear Records removed protected administrator or school settings data.' }
    Write-Host 'PASS: Clear Records removes transactional data while retaining protected setup records.'

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
    if ($LASTEXITCODE -ne 0 -or $restoredBackupName -ne 'QA Administrator Updated') { throw 'The Settings backup did not restore the saved database contents.' }
    & $mysql @rootArgs "--database=inventory_sys_db" "--execute=UPDATE admin SET admin_name='Changed Before HTTP Import' WHERE username='$backupSmokeUser'"
    if ($LASTEXITCODE -ne 0) { throw 'Could not prepare the HTTP import endpoint test.' }
    $boundary = '----InventorySmoke' + [Guid]::NewGuid().ToString('N')
    $multipartPrefix = "--$boundary`r`nContent-Disposition: form-data; name=`"csrf_token`"`r`n`r`n$appCsrfToken`r`n--$boundary`r`nContent-Disposition: form-data; name=`"database_file`"; filename=`"settings-endpoint-smoke.sql`"`r`nContent-Type: application/sql`r`n`r`n"
    $multipartSuffix = "`r`n--$boundary--`r`n"
    $prefixBytes = [Text.Encoding]::UTF8.GetBytes($multipartPrefix)
    $fileBytes = [IO.File]::ReadAllBytes($settingsBackupPath)
    $suffixBytes = [Text.Encoding]::UTF8.GetBytes($multipartSuffix)
    $multipartBytes = New-Object byte[] ($prefixBytes.Length + $fileBytes.Length + $suffixBytes.Length)
    [Array]::Copy($prefixBytes,0,$multipartBytes,0,$prefixBytes.Length)
    [Array]::Copy($fileBytes,0,$multipartBytes,$prefixBytes.Length,$fileBytes.Length)
    [Array]::Copy($suffixBytes,0,$multipartBytes,$prefixBytes.Length + $fileBytes.Length,$suffixBytes.Length)
    $importResponse = Invoke-WebRequest -Uri ($baseUri + 'controllers/setting/import_database.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -ContentType "multipart/form-data; boundary=$boundary" -Body $multipartBytes -UseBasicParsing -TimeoutSec 60
    if (($importResponse.Content | ConvertFrom-Json).status -ne 'success') { throw 'The database import endpoint did not accept its SQL backup.' }
    $importedAdminName = (& $mysql @rootArgs "--database=inventory_sys_db" --batch --skip-column-names --execute="SELECT admin_name FROM admin WHERE username='$backupSmokeUser'").Trim()
    if ($LASTEXITCODE -ne 0 -or $importedAdminName -ne 'QA Administrator Updated') { throw 'The HTTP import did not restore the exported database contents.' }
    $logoutResponse = Invoke-WebRequest -Uri ($baseUri + 'controllers/login/logout.php') -Method Post -WebSession $backupSession -Headers @{'X-CSRF-Token'=$appCsrfToken} -Body @{csrf_token=$appCsrfToken} -UseBasicParsing -TimeoutSec 15
    $protectedPageRejected = $false
    try {
        $afterLogoutPage = Invoke-WebRequest -Uri ($baseUri + 'setting.php') -WebSession $backupSession -MaximumRedirection 5 -UseBasicParsing -TimeoutSec 15
        $protectedPageRejected = ($afterLogoutPage.Content -notmatch 'Settings Overview')
    } catch {
        $protectedPageRejected = ($null -ne $_.Exception.Response -and [int]$_.Exception.Response.StatusCode -eq 401)
    }
    if (-not $protectedPageRejected) { throw 'Logout did not revoke access to protected settings.' }
    Write-Host 'PASS: authenticated logout and protected-page redirect.'
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
    $upgradeJurisdictionColumnCount = (& $mysql "--defaults-extra-file=$appClient" --protocol=TCP --host=127.0.0.1 "--port=$($ports.mysql)" --database=inventory_sys_db --batch --skip-column-names --execute="SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='inventory_sys_db' AND table_name='school_details' AND column_name IN ('Region','Division')").Trim()
    if ($LASTEXITCODE -ne 0 -or [int]$upgradeJurisdictionColumnCount -ne 2) { throw 'The migration did not add Region and Division to an upgraded database.' }
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
