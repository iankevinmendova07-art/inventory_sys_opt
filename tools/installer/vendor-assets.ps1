$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$vendor = Join-Path $root 'assets\vendor'

function Get-Asset([string]$Uri, [string]$RelativePath) {
    $destination = Join-Path $vendor $RelativePath
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $destination) | Out-Null
    & curl.exe --fail --location --silent --show-error --retry 3 --output $destination $Uri
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $destination) -or (Get-Item $destination).Length -lt 100) {
        throw "Could not fetch pinned UI dependency $Uri"
    }
}

$assets = @(
    @('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'),
    @('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'),
    @('https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js', 'jquery/jquery.min.js'),
    @('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css', 'sweetalert2/sweetalert2.min.css'),
    @('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js', 'sweetalert2/sweetalert2.min.js'),
    @('https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css', 'datatables/dataTables.bootstrap5.min.css'),
    @('https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', 'datatables/jquery.dataTables.min.js'),
    @('https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js', 'datatables/dataTables.bootstrap5.min.js'),
    @('https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js', 'chartjs/chart.umd.min.js'),
    @('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css', 'bootstrap-icons/font/bootstrap-icons.min.css'),
    @('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/fonts/bootstrap-icons.woff2', 'bootstrap-icons/font/fonts/bootstrap-icons.woff2'),
    @('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/fonts/bootstrap-icons.woff', 'bootstrap-icons/font/fonts/bootstrap-icons.woff'),
    @('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', 'fontawesome/css/all.min.css')
)
foreach ($asset in $assets) { Get-Asset $asset[0] $asset[1] }

$faCssPath = Join-Path $vendor 'fontawesome\css\all.min.css'
$faCss = [IO.File]::ReadAllText($faCssPath).Replace('../webfonts/', '../webfonts/')
[IO.File]::WriteAllText($faCssPath, $faCss, [Text.UTF8Encoding]::new($false))
foreach ($font in @('fa-brands-400.woff2','fa-regular-400.woff2','fa-solid-900.woff2','fa-v4compatibility.woff2')) {
    Get-Asset "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/$font" "fontawesome/webfonts/$font"
}

$licenses = @(
    @('https://raw.githubusercontent.com/twbs/bootstrap/v5.3.0/LICENSE', 'bootstrap/LICENSE'),
    @('https://raw.githubusercontent.com/jquery/jquery/3.7.0/LICENSE.txt', 'jquery/LICENSE.txt'),
    @('https://raw.githubusercontent.com/sweetalert2/sweetalert2/v11.22.0/LICENSE', 'sweetalert2/LICENSE'),
    @('https://raw.githubusercontent.com/chartjs/Chart.js/v4.5.0/LICENSE.md', 'chartjs/LICENSE.md'),
    @('https://raw.githubusercontent.com/DataTables/DataTablesSrc/1.13.6/license.txt', 'datatables/LICENSE.txt'),
    @('https://raw.githubusercontent.com/twbs/icons/v1.10.5/LICENSE', 'bootstrap-icons/LICENSE'),
    @('https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.4.0/LICENSE.txt', 'fontawesome/LICENSE.txt')
)
foreach ($license in $licenses) { Get-Asset $license[0] $license[1] }
Write-Host "Pinned offline UI libraries and license texts are present under $vendor"
