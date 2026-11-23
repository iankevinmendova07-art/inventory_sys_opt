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
$schoolSyncUrl = 'https://script.google.com/macros/s/AKfycbx1DbWEEP66pn6RQbTIJeTwxI1ra8o5qy6HYvImZxCHvC5hmVmRannMVZpVX3MHHmxA/exec'
$schoolSyncSecret = ''
$existingEnvPath = Join-Path $installRoot '.env'
if (Test-Path $existingEnvPath -PathType Leaf) {
    foreach ($line in (Get-Content $existingEnvPath)) {
        if ($line -match '^\s*SCHOOL_DETAILS_WEBHOOK_URL\s*=\s*(.*?)\s*$') {
            $schoolSyncUrl = $matches[1].Trim('"',"'")
        } elseif ($line -match '^\s*SCHOOL_DETAILS_WEBHOOK_SECRET\s*=\s*(.*?)\s*$') {
            $schoolSyncSecret = $matches[1].Trim('"',"'")
        }
    }
}
$programsMenu = Join-Path $appData 'Microsoft\Windows\Start Menu\Programs'
$startMenuFolder = Join-Path $programsMenu 'Inventory System'
$desktopFolder = if ($env:INVENTORY_DESKTOP_PATH) { $env:INVENTORY_DESKTOP_PATH } else { [Environment]::GetFolderPath([Environment+SpecialFolder]::DesktopDirectory) }
New-Item -ItemType Directory -Force -Path $desktopFolder | Out-Null
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

if (Test-Path $installRoot) {
    Remove-Item -LiteralPath $installRoot -Recurse -Force
    if (Test-Path $installRoot) {
        throw "The existing application files could not be removed from $installRoot. Close the app and retry the installation."
    }
}

New-Item -ItemType Directory -Force -Path $installRoot, $startMenuFolder | Out-Null
Expand-Archive -Path $payloadPath -DestinationPath $installRoot -Force

# A build may optionally carry the school sync secret in its payload. Read it
# after extraction so it overrides old per-install settings and skips prompting.
$bundledSyncSecret = ''
$bundledEnvPath = Join-Path $installRoot '.env'
if (Test-Path $bundledEnvPath -PathType Leaf) {
    foreach ($line in Get-Content $bundledEnvPath) {
        if ($line -match '^\s*SCHOOL_DETAILS_WEBHOOK_URL\s*=\s*(.*?)\s*$') {
            $schoolSyncUrl = $matches[1].Trim('"',"'")
        } elseif ($line -match '^\s*SCHOOL_DETAILS_WEBHOOK_SECRET\s*=\s*(.*?)\s*$') {
            $bundledSyncSecret = $matches[1].Trim('"',"'")
        }
    }
}
if (-not [string]::IsNullOrWhiteSpace($bundledSyncSecret)) {
    $schoolSyncSecret = $bundledSyncSecret
}

if ([string]::IsNullOrWhiteSpace($bundledSyncSecret) -and -not $NoBrowser) {
    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
    $syncForm = New-Object Windows.Forms.Form
    $syncForm.Text = 'Connect Google Sheets'
    $syncForm.StartPosition = 'CenterScreen'
    $syncForm.Size = New-Object Drawing.Size(520,220)
    $syncForm.FormBorderStyle = 'FixedDialog'
    $syncForm.MaximizeBox = $false
    $syncForm.MinimizeBox = $false

    $syncLabel = New-Object Windows.Forms.Label
    $syncLabel.Location = New-Object Drawing.Point(16,16)
    $syncLabel.Size = New-Object Drawing.Size(475,72)
    $syncLabel.Text = "Enter the shared secret configured in your Google Apps Script properties. On upgrades, enter a replacement secret if it changed.`r`n`r`nChoose Skip to keep the existing secret, or continue without sync on a new install."
    $syncForm.Controls.Add($syncLabel)

    $syncInput = New-Object Windows.Forms.TextBox
    $syncInput.Location = New-Object Drawing.Point(18,96)
    $syncInput.Size = New-Object Drawing.Size(468,24)
    $syncInput.UseSystemPasswordChar = $true
    $syncForm.Controls.Add($syncInput)

    $connectButton = New-Object Windows.Forms.Button
    $connectButton.Text = 'Connect'
    $connectButton.Location = New-Object Drawing.Point(302,134)
    $connectButton.Size = New-Object Drawing.Size(88,30)
    $connectButton.DialogResult = [Windows.Forms.DialogResult]::OK
    $syncForm.Controls.Add($connectButton)

    $skipButton = New-Object Windows.Forms.Button
    $skipButton.Text = 'Skip'
    $skipButton.Location = New-Object Drawing.Point(398,134)
    $skipButton.Size = New-Object Drawing.Size(88,30)
    $skipButton.DialogResult = [Windows.Forms.DialogResult]::Cancel
    $syncForm.Controls.Add($skipButton)
    $syncForm.AcceptButton = $connectButton
    $syncForm.CancelButton = $skipButton

    if ($syncForm.ShowDialog() -eq [Windows.Forms.DialogResult]::OK) {
        $enteredSecret = $syncInput.Text.Trim()
        if ([string]::IsNullOrWhiteSpace($enteredSecret)) {
            $syncForm.Dispose()
            $syncInput.Dispose()
            throw 'Enter the Apps Script shared secret, or choose Skip to keep the existing setting.'
        }
        $schoolSyncSecret = $enteredSecret
    }
    $syncForm.Dispose()
    $syncInput.Dispose()
}

if ($schoolSyncSecret -match '[\r\n]' -or $schoolSyncUrl -notmatch '^https://script\.google\.com/macros/s/[A-Za-z0-9_-]+/exec$') {
    throw 'Google Sheets connection settings are invalid. Review the deployment URL and shared secret.'
}
$syncConfig = "SCHOOL_DETAILS_WEBHOOK_URL=$schoolSyncUrl`r`nSCHOOL_DETAILS_WEBHOOK_SECRET=$schoolSyncSecret`r`n"
[IO.File]::WriteAllText((Join-Path $installRoot '.env'), $syncConfig, [Text.UTF8Encoding]::new($false))

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

$desktopShortcutPath = Join-Path $desktopFolder 'Inventory System.lnk'
# Remove the previous link first so Windows picks up the new target and icon
# when an existing installation is upgraded in place.
Remove-Item -LiteralPath $desktopShortcutPath -Force -ErrorAction SilentlyContinue
$desktopShortcut = $shell.CreateShortcut($desktopShortcutPath)
$desktopShortcut.TargetPath = $powerShellPath
$desktopShortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + (Join-Path $installRoot 'tools\installer\start-local.ps1') + '"'
$desktopShortcut.WorkingDirectory = $installRoot
$desktopShortcut.IconLocation = Join-Path $installRoot 'assets\img\inventory-system.ico'
$desktopShortcut.Save()
if (-not (Test-Path $desktopShortcutPath -PathType Leaf)) {
    throw "The desktop shortcut could not be created at $desktopShortcutPath."
}

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
    $diagnosticPaths = @(
        (Join-Path $logs 'mysql-init.err'),
        (Join-Path $logs 'mysql.err'),
        (Join-Path $logs 'php.err'),
        (Join-Path $logs 'schema-import.log'),
        (Join-Path $logs 'schema-migration.log'),
        (Join-Path $logs 'startup-steps.log')
    )
    $runtimeDiagnostics = foreach ($diagnosticPath in $diagnosticPaths) {
        if (Test-Path $diagnosticPath -PathType Leaf) {
            "`r`n--- $diagnosticPath ---`r`n" + (Get-Content $diagnosticPath -Raw)
        }
    }
    throw "The app runtime could not start. $startupDetails`r`n$($runtimeDiagnostics -join '')`r`nReview the logs in $logs."
}
if (-not $NoBrowser) {
    $desktopLaunch = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + $startScript + '"'
    Start-Process -FilePath $powerShellPath -ArgumentList $desktopLaunch -WorkingDirectory $installRoot -WindowStyle Hidden
}
Write-Host 'Inventory System was installed for the current Windows user.'
