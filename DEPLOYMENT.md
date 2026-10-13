# Windows Standalone Deployment

## Architecture

The release package keeps the existing PHP application and adds bundled PHP 8.3.30, MySQL Community Server 8.4.3, a PowerShell service launcher, and an Edge app-mode window. It installs per Windows user and needs no Laragon, XAMPP, Apache, or separate database installation. Core UI libraries are stored locally, so inventory functions do not depend on CDN access.

The launcher binds PHP and MySQL to loopback only. It selects available ports and saves them in `%LOCALAPPDATA%\InventorySystem\secure\ports.json`. The app window has a dedicated Edge profile. Closing that window stops the PHP and MySQL processes started for this installation. If Edge is unavailable, the launcher uses the default browser; use the Start Menu Stop shortcut to stop services in that fallback case.

## Build

Build on 64-bit Windows with PowerShell 5.1+, the .NET Framework 4 C# compiler, and compatible PHP and MySQL runtime folders. By default, the build copies runtime inputs from Laragon without bundling Laragon itself. Override the paths for a controlled build machine:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\installer\build.ps1 `
  -PhpRuntimeRoot 'C:\build\php-8.3.30' `
  -MySqlRuntimeRoot 'C:\build\mysql-8.4.3-winx64'
```

The build stages source, local UI assets and license texts, the empty schema, runtime binaries, and the matching MySQL component/ICU overlay. It produces `tools\installer\output\InventorySystemSetup.exe`. If Inno Setup's compiler is supplied with `-InnoCompilerPath`, the same staged payload uses the Inno Setup script; otherwise the build uses the bundled self-extracting .NET installer. The current tested setup uses the .NET installer.

Run the isolated installer integration test after building:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\installer\test-local.ps1
```

It uses a temporary Windows user-data root and empty database, checks port conflicts, local assets, backups/restores, migration backups, upgrade preservation, Edge app mode, and uninstall data retention. It is not a substitute for a clean Windows 10/11 virtual-machine test or the full business-function regression matrix.

## Install and first launch

Run `InventorySystemSetup.exe`. It installs to `%LOCALAPPDATA%\Programs\InventorySystem`, creates a desktop shortcut and Start Menu entries, and creates the data folder `%LOCALAPPDATA%\InventorySystem`. On first launch, create the administrator account on the local setup page.

The setup includes no existing inventory, employee, administrator, or transaction records. Do not copy a production data directory into the test harness. To migrate an existing MySQL installation, use a separate cold backup and a test copy before using the release package.

## Persistent data and security

- Program files: `%LOCALAPPDATA%\Programs\InventorySystem`
- Database, secrets, local Edge profile, backups, and logs: `%LOCALAPPDATA%\InventorySystem`
- PHP/MySQL listeners: `127.0.0.1` only, on saved available ports
- Database login used by PHP: `inventory_app`, scoped to the application schema
- MySQL administrator password and app password: stored outside the web root with current-user-only NTFS permissions

The app's import and migration features require schema-level `CREATE`, `DROP`, `ALTER`, and `INDEX` grants in addition to row operations. No global database privileges are granted. A person or process running as the same Windows user can access that user's application data; SQL backups are not encrypted. This is a single-PC deployment, not a multi-user server.

The standalone installer is currently unsigned. Windows SmartScreen may show a publisher warning until the publisher signs the executable with a trusted code-signing certificate.

## Backup and restore

Use **Back Up Inventory System** from the Start Menu to create a MySQL SQL dump. Use **Restore Inventory System** to choose a dump; restore first creates a safety backup in `%LOCALAPPDATA%\InventorySystem\backups` and asks before replacing the current database. Keep backup files private. Copy them to separate protected storage for disaster recovery.

When a versioned schema migration is needed, the launcher writes a verified pre-migration SQL dump before applying the migration. Migration version 1 is recorded in `schema_migrations`. The installer does not remove or replace the database during upgrade.

## Update and uninstall

Close the Edge app window before installing an update. The installer stops the package-owned services and preserves the separate data directory. After an update, start from the desktop or Start Menu shortcut.

Uninstall from Windows Apps or the Start Menu. The default choice retains the database, backups, secrets, and logs. The explicit remove-data choice deletes `%LOCALAPPDATA%\InventorySystem` after stopping the services.

## Offline and external functions

Login, inventory queries and edits, cart/release transactions, personnel and unit management, local reports, and printing use the bundled local services. Bootstrap, jQuery, SweetAlert2, DataTables, Chart.js, Bootstrap Icons, and Font Awesome are packaged under `assets\vendor`.

SMS notifications still need a reachable SMS gateway/device and may use the internet or local network. The project has no email delivery integration. A connection is also required for operating-system or external services outside the application.

## Logs and troubleshooting

Logs are in `%LOCALAPPDATA%\InventorySystem\logs`.

- **Startup reports a database or web-port problem:** the launcher checks the preferred port and chooses a free loopback port. If all local ports are unavailable, close the conflicting local service and retry.
- **The app window does not appear:** open the Start Menu Inventory System shortcut and inspect `startup-steps.log`, `php.err`, and `mysql.err`. If Edge is unavailable, use the default browser and Stop Inventory System shortcut.
- **Database startup fails after an unexpected power loss:** launch again and allow MySQL crash recovery to finish; do not delete the `mysql` data directory. Restore the most recent SQL backup if recovery fails.
- **Forgot the administrator password:** use the existing database recovery procedure in `RECOVERY.md`. Do not expose a password-reset endpoint.

See `WINDOWS_USER_GUIDE.md`, `STANDALONE_STATUS.md`, and `THIRD_PARTY_NOTICES.md` for the end-user instructions, test status, and license inventory.
