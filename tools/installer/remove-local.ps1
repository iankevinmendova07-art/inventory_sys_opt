param([switch]$RetainData,[switch]$RemoveData)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
$appRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$localAppData = if ($env:LOCALAPPDATA) { $env:LOCALAPPDATA } else { [Environment]::GetFolderPath('LocalApplicationData') }
$appData = if ($env:APPDATA) { $env:APPDATA } else { [Environment]::GetFolderPath('ApplicationData') }
$desktop = [Environment]::GetFolderPath([Environment+SpecialFolder]::DesktopDirectory)
$dataRoot = Join-Path $localAppData 'InventorySystem'
$menuFolder = Join-Path $appData 'Microsoft\Windows\Start Menu\Programs\Inventory System'
if ($RemoveData) { $removeData = $true }
elseif ($RetainData) { $removeData = $false }
else {
    $confirm = [System.Windows.Forms.MessageBox]::Show("Remove Inventory System?`r`n`r`nChoose Yes to also remove its local database, backups, logs, and settings. Choose No to retain them for a later reinstall.",'Uninstall Inventory System',[System.Windows.Forms.MessageBoxButtons]::YesNoCancel,[System.Windows.Forms.MessageBoxIcon]::Warning,[System.Windows.Forms.MessageBoxDefaultButton]::Button2)
    if ($confirm -eq [System.Windows.Forms.DialogResult]::Cancel) { exit 0 }
    $removeData = ($confirm -eq [System.Windows.Forms.DialogResult]::Yes)
}
$stopScript = Join-Path $PSScriptRoot 'stop-local.ps1'
$powerShell = Join-Path $env:WINDIR 'System32\WindowsPowerShell\v1.0\powershell.exe'
$stop = Start-Process -FilePath $powerShell -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$stopScript`"" -PassThru -Wait -WindowStyle Hidden
if ($stop.ExitCode -ne 0) { throw 'The application services could not be stopped. Data was retained; close the app and retry.' }

Remove-Item 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\InventorySystem' -Recurse -Force -ErrorAction SilentlyContinue
$parentPid = $PID
$encodedRoot = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($appRoot))
$encodedMenu = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($menuFolder))
$encodedDesktop = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes((Join-Path $desktop 'Inventory System.lnk')))
$encodedData = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($dataRoot))
$removeDataLiteral = if ($removeData) { '$true' } else { '$false' }
$cleanup = @"
Wait-Process -Id $parentPid -ErrorAction SilentlyContinue
`$root=[Text.Encoding]::Unicode.GetString([Convert]::FromBase64String('$encodedRoot'))
`$menu=[Text.Encoding]::Unicode.GetString([Convert]::FromBase64String('$encodedMenu'))
`$desktopLink=[Text.Encoding]::Unicode.GetString([Convert]::FromBase64String('$encodedDesktop'))
`$data=[Text.Encoding]::Unicode.GetString([Convert]::FromBase64String('$encodedData'))
`$removeData=$removeDataLiteral
Remove-Item -LiteralPath `$root -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath `$menu -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item -LiteralPath `$desktopLink -Force -ErrorAction SilentlyContinue
if (`$removeData) { Remove-Item -LiteralPath `$data -Recurse -Force -ErrorAction SilentlyContinue }
"@
$encodedCleanup = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($cleanup))
Start-Process -FilePath $powerShell -ArgumentList "-NoProfile -ExecutionPolicy Bypass -EncodedCommand $encodedCleanup" -WorkingDirectory $env:TEMP -WindowStyle Hidden
