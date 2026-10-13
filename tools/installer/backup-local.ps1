$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
$localAppData = if ($env:LOCALAPPDATA) { $env:LOCALAPPDATA } else { [Environment]::GetFolderPath('LocalApplicationData') }
$dataRoot = Join-Path $localAppData 'InventorySystem'
$mysqlDump = Join-Path $PSScriptRoot '..\..\runtime\mysql\bin\mysqldump.exe'
$backupRoot = Join-Path $dataRoot 'backups'
$rootClient = Join-Path $dataRoot 'secure\mysql-root.cnf'
$portsPath = Join-Path $dataRoot 'secure\ports.json'
if (-not (Test-Path $rootClient) -or -not (Test-Path $mysqlDump) -or -not (Test-Path $portsPath)) { [System.Windows.Forms.MessageBox]::Show('Start Inventory System before creating a backup.','Inventory System',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Warning) | Out-Null; exit 1 }
$ports = Get-Content $portsPath -Raw | ConvertFrom-Json
New-Item -ItemType Directory -Force -Path $backupRoot | Out-Null
$dialog = New-Object System.Windows.Forms.SaveFileDialog
$dialog.Title = 'Back up Inventory System'
$dialog.Filter = 'SQL database backup (*.sql)|*.sql'
$dialog.InitialDirectory = $backupRoot
$dialog.FileName = 'InventorySystem-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.sql'
if ($dialog.ShowDialog() -ne [System.Windows.Forms.DialogResult]::OK) { exit 0 }
$errFile = Join-Path $dataRoot 'logs\backup.err'
$arguments = @("--defaults-extra-file=$rootClient",'--protocol=TCP','--host=127.0.0.1',"--port=$($ports.mysql)",'--single-transaction','--routines','--triggers','--databases','inventory_sys_db')
$process = Start-Process -FilePath $mysqlDump -ArgumentList $arguments -PassThru -Wait -WindowStyle Hidden -RedirectStandardOutput $dialog.FileName -RedirectStandardError $errFile
if ($process.ExitCode -ne 0 -or -not (Test-Path $dialog.FileName) -or (Get-Item $dialog.FileName).Length -lt 100) {
    Remove-Item $dialog.FileName -Force -ErrorAction SilentlyContinue
    throw "Backup failed. Review $errFile."
}
[System.Windows.Forms.MessageBox]::Show("Backup saved and verified:`r`n$($dialog.FileName)",'Inventory System',[System.Windows.Forms.MessageBoxButtons]::OK,[System.Windows.Forms.MessageBoxIcon]::Information) | Out-Null
