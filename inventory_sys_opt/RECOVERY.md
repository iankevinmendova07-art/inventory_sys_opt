# Admin Password Recovery

If you forget the admin password for this system, **do not** build a web
page to reset it. Any page reachable by URL is reachable by anyone who
finds or guesses that URL — this is exactly what happened with the old
`eme.php` file (removed in this update): it had no login check at all,
so anyone who found it could take over the admin account, no password
required.

Because you have phpMyAdmin access, here's the safe way instead:

## Steps

1. **Generate a new password hash on your own computer** (this works
   even if the live site is on different hosting — you don't need
   terminal access to the production server, just any machine with PHP,
   like your Laragon install).

   Open a terminal in the project folder and run:
   ```
   php tools/generate_password_hash.php
   ```
   Type the new password when prompted. It will print a hash that looks
   like `$2y$10$......................................`

2. **Open phpMyAdmin** on whichever server hosts the live database.

3. Go to the `inventory_sys` database → the `admin` table → find your
   admin's row → click **Edit**.

4. Paste the generated hash into the **`password`** column, replacing
   whatever is there. Leave every other column alone.

5. Click **Go** / **Save**.

6. Log in with the new password you typed in step 1.

## Why this is safe

- The hash by itself is useless to an attacker — bcrypt is one-way, so
  it can't be turned back into your password.
- Nothing about this process is reachable over the web. Someone would
  need actual phpMyAdmin/database access to reset the password this
  way — the same level of access they'd need to do far more damage
  anyway, so this doesn't introduce a new weak point.
- No new endpoint, no new attack surface, nothing to forget to secure
  later.

## If you truly have zero database access (lost phpMyAdmin login too)

That's a hosting-account recovery problem, not an app problem — contact
whoever manages your hosting/cPanel account. No application-level
password reset feature can help you at that point, and any that claims
to would itself be a security hole (see: `eme.php`).

## My Personal Reset of admin
1. Open Laragon Terminal
2. Run this cd C:\laragon\www\inventory_sys_opt
3. Run this php tools\generate_password_hash.php
