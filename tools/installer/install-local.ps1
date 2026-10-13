param([switch]$NoBrowser)

$ErrorActionPreference = 'Stop'

$payloadPath = Join-Path $PSScriptRoot 'InventorySystemPayload.zip'
$localAppData = $env:LOCALAPPDATA
if ([string]::IsNullOrWhiteSpace($localAppData)) {
    $localAppData = [Environment]::GetFolderPath([Environment+SpecialFolder]::LocalApplicationData)
}
$appData = $env:APPDATA
if ([string]::IsNullOrWhiteSpace($appData)) {
    $appData = [Environment]::GetFolderPath([Environment+SpecialFolder]::ApplicationData)
}
$installRoot = Join-Path $localAppData 'Programs\InventorySystem'
$programsMenu = Join-Path $appData 'Microsoft\Windows\Start Menu\Programs'
$startMenuFolder = Join-Path $programsMenu 'Inventory System'
$desktopFolder = [Environment]::GetFolderPath([Environment+SpecialFolder]::DesktopDirectory)
$powerShellPath = Join-Path $env:WINDIR 'System32\WindowsPowerShell\v1.0\powershell.exe'

if (-not (Test-Path $payloadPath -PathType Leaf)) {
    throw 'The installer payload is missing.'
}

$existingStopScript = Join-Path $installRoot 'tools\installer\stop-local.ps1'
if (Test-Path $existingStopScript) {
    $stopProcess = Start-Process -FilePath $powerShellPath -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$existingStopScript`"" -PassThru -Wait -WindowStyle Hidden
    if ($stopProcess.ExitCode -ne 0) {
        throw 'The existing Inventory System services could not be stopped. Close the app and run the installer again.'
    }
}

$runningLauncherDeadline = [DateTime]::UtcNow.AddSeconds(15)
do {
    $runningLaunchers = @(Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
        $_.Name -ieq 'powershell.exe' -and $_.CommandLine -and
        $_.CommandLine.IndexOf((Join-Path $installRoot 'tools\installer\start-local.ps1'),[StringComparison]::OrdinalIgnoreCase) -ge 0
    })
    if ($runningLaunchers.Count -eq 0) { break }
    Start-Sleep -Milliseconds 200
} while ([DateTime]::UtcNow -lt $runningLauncherDeadline)
if ($runningLaunchers.Count -gt 0) { throw 'The current app window is still closing. Close it and retry the installation.' }

function Get-InstalledRuntimeProcesses {
    $installRootPrefix = $installRoot.TrimEnd('\') + '\'
    return @(Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
        $_.Name -in @('mysqld.exe', 'php.exe') -and $_.ExecutablePath -and
        $_.ExecutablePath.StartsWith($installRootPrefix, [StringComparison]::OrdinalIgnoreCase)
    })
}

$stopDeadline = [DateTime]::UtcNow.AddSeconds(30)
do {
    $remainingRuntimeProcesses = Get-InstalledRuntimeProcesses
    if ($remainingRuntimeProcesses.Count -eq 0) { break }
    Start-Sleep -Milliseconds 250
} while ([DateTime]::UtcNow -lt $stopDeadline)

if ($remainingRuntimeProcesses.Count -gt 0) {
    foreach ($runtimeProcess in $remainingRuntimeProcesses) {
        Stop-Process -Id $runtimeProcess.ProcessId -Force -ErrorAction SilentlyContinue
    }
    foreach ($runtimeProcess in $remainingRuntimeProcesses) {
        Wait-Process -Id $runtimeProcess.ProcessId -Timeout 10 -ErrorAction SilentlyContinue
    }
}

$remainingRuntimeProcesses = Get-InstalledRuntimeProcesses
if ($remainingRuntimeProcesses.Count -gt 0) {
    $processIds = ($remainingRuntimeProcesses | ForEach-Object { $_.ProcessId }) -join ', '
    throw "The installer could not stop the existing runtime process(es): $processIds. Close the app or restart Windows, then retry."
}

New-Item -ItemType Directory -Force -Path $installRoot, $startMenuFolder | Out-Null
Expand-Archive -Path $payloadPath -DestinationPath $installRoot -Force

$shell = New-Object -ComObject WScript.Shell
$shortcuts = @(
    @{ Name = 'Inventory System'; Script = 'start-local.ps1' },
    @{ Name = 'Stop Inventory System'; Script = 'stop-local.ps1' },
    @{ Name = 'Back Up Inventory System'; Script = 'backup-local.ps1' },
    @{ Name = 'Restore Inventory System'; Script = 'restore-local.ps1' },
    @{ Name = 'Remove Inventory System'; Script = 'remove-local.ps1' }
)

foreach ($entry in $shortcuts) {
    $shortcutPath = Join-Path $startMenuFolder ($entry.Name + '.lnk')
    $shortcut = $shell.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = $powerShellPath
    $shortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + (Join-Path $installRoot ('tools\installer\' + $entry.Script)) + '"'
    $shortcut.WorkingDirectory = $installRoot
    $shortcut.IconLocation = Join-Path $installRoot 'assets\img\inventory-system.ico'
    $shortcut.Save()
}

$desktopShortcut = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath([Environment+SpecialFolder]::DesktopDirectory)) 'Inventory System.lnk'))
$desktopShortcut.TargetPath = $powerShellPath
$desktopShortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + (Join-Path $installRoot 'tools\installer\start-local.ps1') + '"'
$desktopShortcut.WorkingDirectory = $installRoot
$desktopShortcut.IconLocation = Join-Path $installRoot 'assets\img\inventory-system.ico'
$desktopShortcut.Save()

$uninstallKey = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\InventorySystem'
New-Item -Path $uninstallKey -Force | Out-Null
$uninstallCommand = '"' + $powerShellPath + '" -NoProfile -ExecutionPolicy Bypass -File "' + (Join-Path $installRoot 'tools\installer\remove-local.ps1') + '"'
Set-ItemProperty -Path $uninstallKey -Name DisplayName -Value 'Inventory System'
Set-ItemProperty -Path $uninstallKey -Name DisplayVersion -Value '1.1.0'
Set-ItemProperty -Path $uninstallKey -Name Publisher -Value 'San Roque Elementary School'
Set-ItemProperty -Path $uninstallKey -Name InstallLocation -Value $installRoot
Set-ItemProperty -Path $uninstallKey -Name UninstallString -Value $uninstallCommand
Set-ItemProperty -Path $uninstallKey -Name DisplayIcon -Value (Join-Path $installRoot 'assets\img\inventory-system.ico')
Set-ItemProperty -Path $uninstallKey -Name NoModify -Value 1 -Type DWord
Set-ItemProperty -Path $uninstallKey -Name NoRepair -Value 1 -Type DWord

$startScript = Join-Path $installRoot 'tools\installer\start-local.ps1'
$logs = Join-Path $localAppData 'InventorySystem\logs'
New-Item -ItemType Directory -Force -Path $logs | Out-Null
try {
    # Invoke the service bootstrap in this PowerShell process. Start-Process -Wait
    # also waits for descendants, including the database and web server services.
    & $startScript -NoBrowser -QuietErrors *> (Join-Path $logs 'start.out')
} catch {
    $startLog = Join-Path $logs 'start.out'
    $startupDetails = if (Test-Path $startLog) { Get-Content $startLog -Raw } else { $_.Exception.Message }
    throw "The app runtime could not start. $startupDetails Check $logs for details."
}
if (-not $NoBrowser) {
    $desktopLaunch = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $startScript + '"'
    Start-Process -FilePath $powerShellPath -ArgumentList $desktopLaunch -WorkingDirectory $installRoot -WindowStyle Hidden
}
Write-Host 'Inventory System was installed for the current Windows user.'
