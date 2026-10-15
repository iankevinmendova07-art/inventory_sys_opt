# Inventory System for Windows

## Install and start

Run `InventorySystemSetup.exe` and follow the installer. It installs for the current Windows user, creates a desktop shortcut and Start Menu entries, and stores the inventory database separately from the program. On the first start, use the local setup page to create the administrator account. Keep that account password private.

Open **Inventory System** from the desktop or Start Menu. The launcher starts its local services and opens an application window. Close that window to stop the services. If the system opens in your default browser, use **Stop Inventory System** in the Start Menu when finished.

For a step-by-step walkthrough with screen captures and annotated examples, see the [printable Project IAN User Manual](docs/Project-IAN-User-Manual.pdf).

## Back up and restore

- Choose **Back Up Inventory System** in the Start Menu and select a location for the SQL backup. Store a copy somewhere separate from this PC.
- Choose **Restore Inventory System**, select a backup, and confirm replacement. The application creates a safety backup before restoring.
- Backup files contain inventory and account data in readable SQL form. Protect them like other sensitive records.

## Updates and uninstall

Close the app before running a newer installer. The installer preserves the existing database and user files. After it completes, open the desktop or Start Menu shortcut.

Uninstall from Windows Settings or the Start Menu. Choose to retain application data if you may reinstall or need the database later. Choose removal only when you intend to erase the database, backups, logs, and local configuration.

## Offline use

The local inventory application and its packaged interface assets run without internet access. No email delivery feature is included.

## Data and logs

Program files are in `%LOCALAPPDATA%\Programs\InventorySystem`. Persistent database data, settings, and logs are in `%LOCALAPPDATA%\InventorySystem`. Do not manually edit or delete the MySQL data files while the application is running.

For startup diagnostics, inspect `%LOCALAPPDATA%\InventorySystem\logs\startup-steps.log`, `php.err`, and `mysql.err`. If startup fails, close conflicting local software and try again. If database recovery fails after an unexpected shutdown, preserve the data directory and restore a known-good backup.

## Release status

This build has passed the installer integration checks listed in `STANDALONE_STATUS.md`. Full authenticated business-workflow testing and clean Windows 10/11 virtual-machine testing remain outstanding. The installer is unsigned, so Windows may display a publisher warning.
