param(
    [string]$PhpRuntimeRoot = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64',
    [string]$MySqlRuntimeRoot = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64',
    [string]$InnoCompilerPath = ''
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$installerRoot = $PSScriptRoot
$repositoryRoot = (Resolve-Path (Join-Path $installerRoot '..\..')).Path
$stageRoot = Join-Path $installerRoot 'stage'
$outputRoot = Join-Path $installerRoot 'output'
$mysqlOverlayRoot = Join-Path $installerRoot 'runtime-overlay\mysql'
$bundledSchoolSyncSecret = [Environment]::GetEnvironmentVariable('INVENTORY_SCHOOL_DETAILS_WEBHOOK_SECRET')
$schoolSyncUrl = 'https://script.google.com/macros/s/AKfycbx1DbWEEP66pn6RQbTIJeTwxI1ra8o5qy6HYvImZxCHvC5hmVmRannMVZpVX3MHHmxA/exec'
if (-not [string]::IsNullOrWhiteSpace($bundledSchoolSyncSecret)) {
    if ($bundledSchoolSyncSecret -match '[\r\n]') {
        throw 'INVENTORY_SCHOOL_DETAILS_WEBHOOK_SECRET must be a single-line value.'
    }
    if ($schoolSyncUrl -notmatch '^https://script\.google\.com/macros/s/[A-Za-z0-9_-]+/exec$') {
        throw 'The configured Google Apps Script deployment URL is invalid.'
    }
}

foreach ($requiredPath in @(
    (Join-Path $PhpRuntimeRoot 'php.exe'),
    (Join-Path $PhpRuntimeRoot 'ext\php_pdo_mysql.dll'),
    (Join-Path $MySqlRuntimeRoot 'bin\mysqld.exe'),
    (Join-Path $MySqlRuntimeRoot 'bin\mysql.exe'),
    (Join-Path $MySqlRuntimeRoot 'bin\mysqldump.exe'),
    (Join-Path $MySqlRuntimeRoot 'bin\mysqladmin.exe'),
    (Join-Path $MySqlRuntimeRoot 'share'),
    (Join-Path $mysqlOverlayRoot 'lib\plugin\component_reference_cache.dll'),
    (Join-Path $mysqlOverlayRoot 'lib\private\icudt73l'),
    (Join-Path $installerRoot 'runtime-overlay\php\cacert.pem'),
    (Join-Path $installerRoot 'runtime-overlay\php\CA-BUNDLE-NOTICE.txt'),
    (Join-Path $repositoryRoot 'database_schema.sql'),
    (Join-Path $installerRoot 'InventorySystemSetup.manifest'),
    (Join-Path $repositoryRoot 'assets\vendor\bootstrap\bootstrap.min.css'),
    (Join-Path $repositoryRoot 'assets\vendor\jquery\jquery.min.js'),
    (Join-Path $repositoryRoot 'assets\img\inventory-system.ico')
)) {
    if (-not (Test-Path $requiredPath)) {
        throw "Required build input not found: $requiredPath"
    }
}

if (Test-Path $stageRoot) {
    Remove-Item $stageRoot -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $stageRoot | Out-Null

Get-ChildItem $repositoryRoot -Force |
    Where-Object {
        $_.Name -notin @('.git', '.vscode', '.idea', 'tools', 'env', '.env', 'dist') -and
        -not $_.Name.StartsWith('.env.')
    } |
    ForEach-Object { Copy-Item $_.FullName -Destination $stageRoot -Recurse -Force }

# Keep the Chart.js license beside the staged vendor assets after Markdown files
# have been consolidated into MD FILES in the source repository.
$chartLicense = Join-Path $stageRoot 'assets\vendor\chartjs\LICENSE.md'
if (Test-Path (Join-Path $repositoryRoot 'MD FILES\LICENSE.md')) {
    New-Item -ItemType Directory -Force -Path (Split-Path $chartLicense) | Out-Null
    Copy-Item (Join-Path $repositoryRoot 'MD FILES\LICENSE.md') $chartLicense -Force
}

$sourceTools = Join-Path $repositoryRoot 'tools'
$stagedTools = Join-Path $stageRoot 'tools'
New-Item -ItemType Directory -Force -Path $stagedTools | Out-Null
Get-ChildItem $sourceTools -Force |
    Where-Object { $_.Name -ne 'installer' } |
    ForEach-Object { Copy-Item $_.FullName -Destination $stagedTools -Recurse -Force }

if (-not [string]::IsNullOrWhiteSpace($bundledSchoolSyncSecret)) {
    $stagedSyncEnv = Join-Path $stageRoot '.env'
    $stagedSyncConfig = "SCHOOL_DETAILS_WEBHOOK_URL=$schoolSyncUrl`r`nSCHOOL_DETAILS_WEBHOOK_SECRET=$bundledSchoolSyncSecret`r`n"
    [IO.File]::WriteAllText($stagedSyncEnv, $stagedSyncConfig, [Text.UTF8Encoding]::new($false))
}

$stagedInstallerTools = Join-Path $stageRoot 'tools\installer'
if (Test-Path $stagedInstallerTools) {
    Remove-Item $stagedInstallerTools -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $stagedInstallerTools | Out-Null
Copy-Item (Join-Path $installerRoot 'start-local.ps1') $stagedInstallerTools
Copy-Item (Join-Path $installerRoot 'stop-local.ps1') $stagedInstallerTools
Copy-Item (Join-Path $installerRoot 'remove-local.ps1') $stagedInstallerTools
Copy-Item (Join-Path $installerRoot 'install-local.ps1') $stagedInstallerTools
Copy-Item (Join-Path $installerRoot 'backup-local.ps1') $stagedInstallerTools
Copy-Item (Join-Path $installerRoot 'restore-local.ps1') $stagedInstallerTools

$phpStage = Join-Path $stageRoot 'runtime\php'
$mysqlStage = Join-Path $stageRoot 'runtime\mysql'
New-Item -ItemType Directory -Force -Path $phpStage, $mysqlStage | Out-Null
Copy-Item (Join-Path $PhpRuntimeRoot '*') $phpStage -Recurse -Force
Copy-Item (Join-Path $installerRoot 'PHP-LICENSE.txt') (Join-Path $phpStage 'PHP-LICENSE.txt')
Copy-Item (Join-Path $installerRoot 'runtime-overlay\php\cacert.pem') (Join-Path $phpStage 'cacert.pem') -Force
Copy-Item (Join-Path $installerRoot 'runtime-overlay\php\CA-BUNDLE-NOTICE.txt') (Join-Path $phpStage 'CA-BUNDLE-NOTICE.txt') -Force

foreach ($runtimeDirectory in @('bin', 'lib', 'share')) {
    $sourceDirectory = Join-Path $MySqlRuntimeRoot $runtimeDirectory
    if (Test-Path $sourceDirectory) {
        Copy-Item $sourceDirectory $mysqlStage -Recurse -Force
    }
}
Copy-Item (Join-Path $mysqlOverlayRoot 'lib') $mysqlStage -Recurse -Force

$mysqlLicense = Join-Path $MySqlRuntimeRoot 'LICENSE'
if (Test-Path $mysqlLicense) {
    Copy-Item $mysqlLicense $mysqlStage
}

$phpRedistributionNotice = Join-Path $PhpRuntimeRoot 'readme-redist-bins.txt'
if (Test-Path $phpRedistributionNotice) {
    Copy-Item $phpRedistributionNotice (Join-Path $phpStage 'PHP-REDISTRIBUTION-NOTICE.txt')
}

$phpIni = @(
    '[PHP]'
    'engine=On'
    'short_open_tag=Off'
    'expose_php=Off'
    'display_errors=Off'
    'log_errors=On'
    'error_reporting=E_ALL'
    'max_execution_time=120'
    'max_input_time=60'
    'memory_limit=256M'
    'post_max_size=64M'
    'upload_max_filesize=64M'
    'date.timezone=Asia/Manila'
    'extension_dir="ext"'
    'extension=curl'
    'extension=fileinfo'
    'extension=gd'
    'extension=mbstring'
    'extension=mysqli'
    'extension=openssl'
    'extension=pdo_mysql'
    'extension=zip'
    'cgi.fix_pathinfo=0'
    'session.cookie_httponly=1'
) -join "`r`n"
[IO.File]::WriteAllText((Join-Path $phpStage 'php.ini'), $phpIni, [Text.UTF8Encoding]::new($false))

if ([string]::IsNullOrWhiteSpace($InnoCompilerPath)) {
    $compilerCandidates = @(
        'C:\Program Files (x86)\Inno Setup 6\ISCC.exe',
        'C:\Program Files\Inno Setup 6\ISCC.exe'
    )
    $InnoCompilerPath = $compilerCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
}

New-Item -ItemType Directory -Force -Path $outputRoot | Out-Null
$buildRoot = Join-Path $outputRoot ('.build-' + [Guid]::NewGuid().ToString('N'))
$publishedPath = Join-Path $outputRoot 'InventorySystemSetup.exe'
New-Item -ItemType Directory -Force -Path $buildRoot | Out-Null

try {
    if (-not [string]::IsNullOrWhiteSpace($InnoCompilerPath) -and (Test-Path $InnoCompilerPath)) {
        & $InnoCompilerPath ("/O" + $buildRoot) (Join-Path $installerRoot 'InventorySystem.iss')
        if ($LASTEXITCODE -ne 0) {
            throw "Inno Setup compilation failed with exit code $LASTEXITCODE."
        }
        $setupPath = Join-Path $buildRoot 'InventorySystemSetup.exe'
    } else {
        $csharpCompiler = Join-Path $env:WINDIR 'Microsoft.NET\Framework64\v4.0.30319\csc.exe'
        if (-not (Test-Path $csharpCompiler)) {
            throw 'Neither Inno Setup nor the .NET Framework C# compiler is available to compile the installer.'
        }

        $payloadPath = Join-Path $buildRoot 'InventorySystemPayload.zip'
        $archiveAttempts = 0
        do {
            $archiveAttempts++
            try {
                Compress-Archive -Path (Join-Path $stageRoot '*') -DestinationPath $payloadPath -CompressionLevel Optimal -Force
                break
            } catch {
                if ($archiveAttempts -ge 5) { throw }
                Start-Sleep -Seconds 2
            }
        } while ($archiveAttempts -lt 5)
        $setupPath = Join-Path $buildRoot 'InventorySystemSetup.exe'
        $installerSource = Join-Path $installerRoot 'InstallerBootstrap.cs'
        $installScript = Join-Path $installerRoot 'install-local.ps1'
        $compilerArguments = @(
            '/nologo'
            '/target:winexe'
            '/platform:x64'
            "/win32manifest:$(Join-Path $installerRoot 'InventorySystemSetup.manifest')"
            "/out:$setupPath"
            "/win32icon:$(Join-Path $repositoryRoot 'assets\img\inventory-system.ico')"
            "/resource:$payloadPath,InventorySystemPayload.zip"
            "/resource:$installScript,install-local.ps1"
            '/reference:System.Windows.Forms.dll'
            $installerSource
        )
        & $csharpCompiler @compilerArguments
        if ($LASTEXITCODE -ne 0 -or -not (Test-Path $setupPath)) {
            throw 'The .NET Framework compiler could not create the standalone setup executable.'
        }
    }

    if (-not (Test-Path $setupPath -PathType Leaf)) {
        throw 'The compiler reported success but did not create the setup executable.'
    }

    $oldSetups = @(Get-ChildItem $outputRoot -Filter 'InventorySystemSetup*.exe' -File)
    foreach ($oldSetup in $oldSetups) {
        try {
            $lockProbe = [IO.File]::Open($oldSetup.FullName, [IO.FileMode]::Open, [IO.FileAccess]::ReadWrite, [IO.FileShare]::None)
            $lockProbe.Dispose()
        } catch {
            throw "Close the installer before rebuilding; its executable is in use: $($oldSetup.FullName)"
        }
    }

    if (Test-Path $publishedPath) {
        $previousPath = Join-Path $buildRoot 'previous-installer.exe'
        [IO.File]::Replace($setupPath, $publishedPath, $previousPath)
        Remove-Item -LiteralPath $previousPath -Force
    } else {
        Move-Item -LiteralPath $setupPath -Destination $publishedPath
    }

    Get-ChildItem $outputRoot -Filter 'InventorySystemSetup*.exe' -File |
        Where-Object { $_.FullName -ine $publishedPath } |
        Remove-Item -Force

    Write-Host "Setup package created: $publishedPath"
} finally {
    $stagedSyncEnv = Join-Path $stageRoot '.env'
    if (Test-Path $stagedSyncEnv -PathType Leaf) {
        Remove-Item -LiteralPath $stagedSyncEnv -Force
    }
    if (Test-Path $buildRoot) {
        Remove-Item -LiteralPath $buildRoot -Recurse -Force
    }
}
