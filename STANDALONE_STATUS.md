# Standalone Release Status

Reviewed 12 October 2026. This is a tested release candidate, not a production release certification.

## Architecture shipped

The package preserves the PHP application and runs bundled PHP 8.3.30 and MySQL Community Server 8.4.3. A PowerShell lifecycle launcher starts the services on available loopback ports, verifies readiness, and opens an Edge app-mode window with a private profile. Closing the window stops package-owned services. If Edge is absent, it opens the default browser and exposes a Stop shortcut. This minimizes changes to business logic and avoids an Electron runtime.

The setup file was rebuilt from the current source at `tools/installer/output/InventorySystemSetup.exe`. It is a self-extracting .NET Framework installer built with the C# fallback because Inno Setup is not installed in this environment. The installer registers in Windows Apps, adds Start Menu and desktop shortcuts, includes a branded icon and version metadata, and creates a data-preserving uninstaller.

## Inspection findings

- PHP uses PDO MySQL and native prepared statements. The bundled runtime includes `pdo_mysql`, `curl`, `fileinfo`, `gd`, `mbstring`, `mysqli`, `openssl`, and `zip`.
- The schema has 13 application tables, all InnoDB; no foreign keys, triggers, or stored procedures were declared. `schema_migrations` records migration version 1. The additive migration creates school details/checkout tables and indexes.
- PHP sessions use strict mode, HTTP-only cookies, and SameSite=Lax. The app and MySQL bind to loopback. User data and plaintext client secret files are outside the install and web directories, with current-Windows-user-only NTFS ACLs.
- Bootstrap, jQuery, SweetAlert2, DataTables, Chart.js, Bootstrap Icons, and Font Awesome are pinned and served locally. No UI CDN references remain in application pages.
- Routine inventory operations are offline-capable in architecture. This run verified local assets and services, but did not exercise every function with the internet physically disconnected.

## Test report

| Check | Result | Evidence / limit |
|---|---|---|
| PHP syntax | Passed | All 160 project PHP files passed `php -l` against the bundled PHP runtime. |
| PowerShell syntax | Passed | All installer `.ps1` scripts parsed successfully. |
| Installer rebuild | Passed | Setup executable rebuilt from current sources, with version 1.1.0. |
| Isolated fresh installation | Passed | New temporary user profile; empty MySQL data directory; no production records. |
| Initial setup page and local UI | Passed | `setup_admin.php` returned successfully; Bootstrap, jQuery, Chart.js, and Font Awesome asset requests returned 200 over loopback. |
| Database security and loopback | Passed | Installer test checked restricted `inventory_app` grants, MySQL loopback bind, protected data directory, and a PHP listener on `127.0.0.1`. |
| Port conflict | Passed | The test reserved preferred web/MySQL ports where available; launcher selected other ports. |
| Backup and restore | Passed | Test inserted an isolated dummy administrator, dumped the DB, changed its row, restored the dump, and verified the original row value. |
| Migration backup | Passed | Test removed the migration marker in its test DB; startup made a non-empty pre-migration dump before reapplying version 1. |
| Upgrade preservation | Passed | A dummy administrator row survived an in-place installer upgrade. |
| Desktop window lifecycle | Passed | Edge opened with `--app` and a dedicated profile; Stop closed the window and stopped owned PHP/MySQL services. |
| Uninstall retention | Passed | Program files and shortcuts were removed while the test database directory remained. |
| CRUD, release accuracy, personnel/UOM, reports/printing | Untested | Full authenticated business workflow regression remains outstanding. |
| Windows reboot/power-loss recovery | Untested | MySQL is configured to recover its InnoDB data; no reboot/power-cut test was run. |
| Remove-data uninstall choice | Untested | The retention option was verified; destructive removal was not run. |
| Clean Windows 10/11 VM without dev tools | Blocked | No clean VM was available. Smoke tests used the bundled runtime in isolated temp profiles on the build host. |
| Digital signature | Blocked | No trusted publisher code-signing certificate is available; SmartScreen may warn. |

## Remaining release work

1. Run the authenticated CRUD, cart/release quantity, personnel, units, reports, printing, logout, and disconnected-network regression matrix with dummy records.
2. Run the installer on clean Windows 10 and 11 VMs without Laragon/XAMPP; test reboot, unexpected shutdown recovery, and remove-data uninstall.
3. Obtain a trusted code-signing certificate and sign the final installer before broad distribution.
4. Complete redistribution/source-compliance review for MySQL Community Server GPLv2 and the bundled runtime. Component license files and notices are in `THIRD_PARTY_NOTICES.md` and `assets/vendor`.

## Known limitations

- This is a single-PC application, not a LAN/multi-user server. Access is limited to the current Windows account and local loopback. Processes running as that same Windows user can read the user's files and credentials.
- Database passwords are stored in protected per-user files, not encrypted with DPAPI. SQL backups are plain text and should be stored privately.
- The local PHP built-in server is used to avoid bundling a separate web server. Keep it bound to loopback.
- App-level SQL import/schema-migration features require database-scoped CREATE/DROP/ALTER/INDEX grants in addition to row access. The account has no global grants.
- If Edge is unavailable, the default-browser fallback does not detect browser close; use the Stop shortcut.
- Installer is unsigned. MySQL component/runtime redistribution notices are included, but legal release clearance is still required.
