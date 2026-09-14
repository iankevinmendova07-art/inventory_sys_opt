# Deployment Checklist

## 1. Server requirements

- PHP 8.3+ with PDO MySQL, cURL, and required PDF extensions.
- MySQL 8.0+.
- Apache with `mod_rewrite`, `mod_headers`, and `.htaccess` support, or equivalent web-server rules.
- HTTPS enabled.

## 2. Application configuration

Set these environment variables on the server. Do not commit real credentials:

```text
APP_ENV=production
DB_HOST=127.0.0.1
DB_NAME=inventory_sys_db
DB_USER=inventory_app
DB_PASS=<strong-unique-password>
SMS_GATEWAY_URL=https://api.sms-gate.app/3rdparty/v1/message
SMS_GATEWAY_USERNAME=<sms-gateway-username>
SMS_GATEWAY_PASSWORD=<sms-gateway-password>
```

The application rejects production configuration that uses `root` or an empty database password.
The SMS variables use the Basic Auth credentials shown by SMS Gateway for Android. Leave them unset to release inventory without sending SMS notifications.

## 3. Database installation

1. Create the database and restricted application user.
2. Import `database_schema.sql` into the new database.
3. For an existing installation, apply the additive indexes and generated normalized supply columns from the `supplies`, `transaction_log`, `stock_card`, and related `ALTER TABLE` statements in `database_schema.sql`.
4. Verify with `SHOW INDEX` and `EXPLAIN` before opening the application to users.
5. Back up the database before applying schema changes.

For an existing installation, add the mobile number column before using SMS notifications:

```sql
ALTER TABLE employee ADD COLUMN emp_phone varchar(30) NULL AFTER emp_position;
```

## 4. Web-server configuration

- Point the document root at this project directory.
- Keep `.htaccess` enabled, or reproduce its directory blocking and security headers in the server configuration.
- Do not expose `.env`, SQL dumps, logs, backups, or the `tools` directory.
- Ensure the web user can write only to required runtime locations, never to PHP source or configuration files.

## 5. Validation

From the project directory:

```text
php -l <each PHP file>
```

Then verify:

- Login succeeds with a valid administrator and fails with invalid credentials.
- Unauthenticated requests redirect to `login.php`.
- CRUD operations, cart release, reports, backups, and logout work with an authenticated test account.
- CSRF-protected POST requests reject missing or cross-origin requests.
- Database backup and restore work before production cutover.

Run the scenarios in `qa_test_matrix_inventory_sys_opt.csv` and record the result before release.
