<?php
/**
 * generate_password_hash.php
 *
 * Admin password recovery tool. Run this from a terminal, NEVER through
 * a browser -- it refuses to run if a web server tries to execute it
 * (see the check below). This replaces eme.php: instead of a web page
 * that can reset the admin password with no login required, this script
 * only works if you already have terminal/CLI access to a machine with
 * PHP installed (your own computer is fine -- it doesn't need to be the
 * production server).
 *
 * USAGE:
 *   php tools/generate_password_hash.php
 *   (it will prompt you to type a new password, then print a hash)
 *
 * WHAT TO DO WITH THE OUTPUT:
 *   1. Copy the hash it prints.
 *   2. Open phpMyAdmin -> your database -> the `admin` table.
 *   3. Edit the row for your admin account.
 *   4. Paste the hash into the `password` column (replacing the old value).
 *   5. Save. Log in with the new password you typed in step 1.
 *
 * See RECOVERY.md for the full walkthrough with screenshots-in-words.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This tool only runs from the command line. It will not run through a web browser.');
}

echo "=== Admin Password Recovery ===\n";
echo "This generates a password hash for your `admin` table.\n";
echo "It does NOT touch your database -- you'll paste the result into phpMyAdmin yourself.\n\n";

echo "Enter the new password: ";
// Works on Windows (Laragon) and Linux/Mac terminals alike.
$newPassword = trim(fgets(STDIN));

if (strlen($newPassword) < 8) {
    fwrite(STDERR, "\nPassword must be at least 8 characters. Nothing was generated.\n");
    exit(1);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "\nDone. Copy the value below into the `password` column of the `admin` row in phpMyAdmin:\n\n";
echo $hash . "\n\n";
echo "(This hash is safe to paste anywhere -- it cannot be reversed back into your password.)\n";
