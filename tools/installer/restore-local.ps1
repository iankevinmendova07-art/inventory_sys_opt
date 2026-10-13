$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
$localAppData = if ($env:LOCALAPPDATA) { $env:LOCALAPPDATA } else { [Environment]::GetFolderPath('LocalApplicationData') }
$dataRoot = Join-Path $localAppData 'InventorySystem'
$mysql = Join-Path $PSScriptRoot '..\..\runtime\mysql\bin\mysql.exe'
$mysqlDump = Join-Path $PSScriptRoot '..\..\runtime\mysql\bin\mysqldump.exe'
$rootClient = Join-Path $dataRoot 'secure\mysql-root.cnf'
$portsPath = Join-Path $dataRoot 'secure\ports.json'
if (-not (Test-Path $rootClient) -or -not (Test-Path $mysql) -or -not (Test-Path $portsPath)) { [System.Windows.Forms.MessageBox]::Show('Start Inventory System before restoring a database backup.','Inventory System',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Warning) | Out-Null; exit 1 }
$ports = Get-Content $portsPath -Raw | ConvertFrom-Json
$dialog = New-Object System.Windows.Forms.OpenFileDialog
$dialog.Title = 'Choose an Inventory System SQL backup'
$dialog.Filter = 'SQL database backup (*.sql)|*.sql'
if ($dialog.ShowDialog() -ne [System.Windows.Forms.DialogResult]::OK) { exit 0 }
$confirm = [System.Windows.Forms.MessageBox]::Show('Restoring replaces the current inventory database. A safety backup will be created first. Continue?','Confirm database restore',[System.Windows.Forms.MessageBoxButtons]::YesNo,[System.Windows.Forms.MessageBoxIcon]::Warning,[System.Windows.Forms.MessageBoxDefaultButton]::Button2)
if ($confirm -ne [System.Windows.Forms.DialogResult]::Yes) { exit 0 }
$backupRoot = Join-Path $dataRoot 'backups'
New-Item -ItemType Directory -Force -Path $backupRoot | Out-Null
$safetyPath = Join-Path $backupRoot ('pre-restore-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.sql')
$errFile = Join-Path $dataRoot 'logs\restore.err'
$dumpArgs = @("--defaults-extra-file=$rootClient",'--protocol=TCP','--host=127.0.0.1',"--port=$($ports.mysql)",'--single-transaction','--routines','--triggers','--databases','inventory_sys_db')
$dump = Start-Process -FilePath $mysqlDump -ArgumentList $dumpArgs -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput $safetyPath -RedirectStandardError $errFile
if ($dump.ExitCode -ne 0 -or (Get-Item $safetyPath).Length -lt 100) { throw "Safety backup failed; restore was cancelled. Review $errFile." }
$source = 'source "' + $dialog.FileName.Replace('\','/') + '"'
$restoreArgs = @("--defaults-extra-file=$rootClient",'--protocol=TCP','--host=127.0.0.1',"--port=$($ports.mysql)",'--user=root',"--execute=$source")
$restore = Start-Process -FilePath $mysql -ArgumentList $restoreArgs -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput (Join-Path $dataRoot 'logs\restore.out') -RedirectStandardError $errFile
if ($restore.ExitCode -ne 0) { throw "Restore failed; the safety backup remains at $safetyPath. Review $errFile." }
[System.Windows.Forms.MessageBox]::Show("Database restored. Safety backup:`r`n$safetyPath",'Inventory System',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Information) | Out-Null
