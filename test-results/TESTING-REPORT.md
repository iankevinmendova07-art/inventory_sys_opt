# System Testing Report

**Test date:** 2026-11-20  
**Application:** PROJECT - IAN (Inventory and Assets Navigation)  
**Environment:** Disposable local copy at `http://127.0.0.1:8765/`; MySQL on port `33127`  
**Browser:** Headed Chromium 150.0.7871.250 (integrated Playwright 1.145.0)  
**Runtime:** PHP 8.3.30; MySQL 8.4.3

## Executive summary

The application was launched and exercised in a visible browser using a blank, isolated database and synthetic QA records. The pre-existing Laragon database was not changed. No application source files were edited for this test.

| Measure | Result |
|---|---:|
| Discovered functionality/test entries | 72 |
| PASS | 64 |
| FAIL | 1 |
| BLOCKED | 2 |
| SKIPPED | 5 |
| Functions actually tested (PASS + FAIL) | 65 / 72 |
| Functionality coverage | **90.3%** |
| Pass rate among tested functions | **98.5%** (64 / 65) |
| Screenshots | 90 PNGs |

Coverage is `(PASS + FAIL) / all matrix entries`; blocked and skipped entries remain in the denominator. The detailed cases and actual outcomes are in [FUNCTIONALITY-MATRIX.csv](./FUNCTIONALITY-MATRIX.csv).

## Project and test setup

- The application is a PHP/MySQL inventory system with local vendor assets.
- Main navigation and tested modules: Home, Consumable Supplies, Non-Consumable Supplies, Science and Math Equipment, TextBooks, and Settings.
- Testing used an isolated copy of the current working tree, a fresh local database, synthetic school details, and a disposable administrator account. The account password is intentionally not recorded.
- The original Laragon database and production-like data were left untouched.
- The session began with the application setup/login flow. Required-field and password-confirmation validation, invalid login, valid login, session persistence, logout, and protected direct URL access were exercised.
- Existing working-tree changes were preserved. Only test evidence/report files were added.

## Modules and workflows exercised

- **Authentication and setup:** initial setup validation, invalid username handling, mismatched passwords, empty and invalid login, successful login, session persistence, forgot-password recovery-PIN validation, logout cancel/confirm, and unauthenticated direct access to Settings.
- **Dashboard:** load, navigation, charts, activity table, and responsive viewport checks.
- **Consumables:** create/read/update, duplicate-name and negative-quantity validation, stock-in, cart and recipient validation, cancel and complete release, stock decrement, transaction log, RIS print view, individual/all stock cards, November 2026 RSMI, and deletion of the designated QA supply.
- **Non-consumables:** create/read/update, search, cancel/confirm deletion, property-card print view, and RPCPPE report.
- **Science and Math equipment:** create records in both tabs, edit Science, delete both QA rows, switch tabs, and generate Science, Math, and combined reports.
- **TextBooks:** create/read/update/delete, required-field validation, search, ascending sort, page-size change, two-page pagination with six temporary rows, individual print slip, and grouped inventory report. Pagination-only rows were deleted afterward.
- **Settings:** employee, unit, and position create/update; school details save; admin form required-field validation; database backup notification; clear-database confirmation and cancel. Deletes of referenced employee/unit/position records were correctly rejected by the application’s referential-integrity checks.

## Findings

| ID | Severity | Finding | Evidence / impact |
|---|---|---|---|
| F-01 | Medium | Setup username HTML pattern is invalid in Chromium’s current regular-expression mode. | [setup_admin.php](../setup_admin.php#L230) uses `pattern="[A-Za-z0-9._-]+"`. Chromium logged a pattern syntax error for the invalid-input test. Server-side validation still rejected the invalid value, but browser validation emitted an error. |
| F-02 | Medium | Dashboard horizontal overflow at the requested mobile size. | [responsive-dashboard-mobile.png](./screenshots/responsive-dashboard-mobile.png). With `390 × 844` requested, the integrated browser reported a CSS viewport of `468 × 1036`; document width was `543px`, producing horizontal overflow. The dashboard activity table and chart canvas extended beyond the viewport. |
| F-03 | Low | Missing favicon request. | The isolated PHP access log recorded `GET /favicon.ico` as 404. Add the asset or configure the page to use an existing icon. |

The setup regex issue is the single FAIL in the functionality matrix. The mobile layout issue is recorded in the separate responsive results below. A second 404 in the log was caused by a test-only request to a guessed, nonexistent endpoint while checking an equipment update; it is not counted as an application defect.

### Responsive results

The requested sizes were set in Playwright. The integrated browser’s reported CSS viewport did not exactly match the requested dimensions, so results are reported with that harness limitation.

| Requested viewport | Observed result |
|---|---|
| 1920 × 1080 desktop | No document-level horizontal overflow observed |
| 1366 × 768 laptop | No document-level horizontal overflow observed |
| 768 × 1024 tablet | No document-level horizontal overflow observed |
| 390 × 844 mobile | Horizontal overflow observed; document width exceeded reported CSS viewport by about 75 CSS pixels |

## Errors and performance observations

- The isolated PHP access log contained 1,179 HTTP 200, 12 HTTP 302, 2 HTTP 303, and 2 HTTP 404 responses. The redirects occurred in setup/authentication workflows. The 404s were the missing favicon and the test-generated endpoint probe noted above.
- No HTTP 500 response, PHP fatal error, warning, or notice was found in the isolated runtime log.
- Reports exercised returned HTTP 200 and contained the synthetic test records where expected.
- No formal load, concurrency, or performance benchmark was run. Page responsiveness was observed manually; no quantitative latency claim is made.
- Browser testing is not a security audit and does not establish that the system is secure or production-ready.

## Skipped and blocked work

- Consumable and Science/Math table search/sort/pagination were not exercised; the isolated lists did not have enough rows for meaningful multi-page validation.
- The non-consumable table’s sort/pagination remained blocked by its single-row dataset.
- The top-bar icon on Consumables was not activated because its purpose was not confirmed before logout.
- Clearing all database records and importing a SQL dump were intentionally not executed because those operations are destructive. The clear confirmation was opened and canceled.
- Optional Google Sheets synchronization was blocked because it is not configured in the isolated environment.
- Employee, unit, and position cleanup could not be completed: the application correctly prevented deletion while those synthetic records were referenced by transaction/inventory history. Those referenced QA setup records remain only in the isolated database.

## Evidence and automation limitations

- Screenshots are in [screenshots/](./screenshots/).
- [playwright-trace.zip](./playwright-trace.zip) is a Playwright trace of a final login-page reload. Tracing was started after the main live session to avoid capturing authentication data; it is not a full-session trace.
- The integrated headed Playwright browser was available and used for the live demonstration. No standalone Playwright runner or Node/npm executable was available; the Python launcher also could not run. Therefore, a portable `.spec` test script and the official Playwright HTML reporter could not be produced or executed.
- [HTML-REPORT.html](./HTML-REPORT.html) is a browsable summary generated from the recorded results, not the native Playwright HTML reporter. Video recording was not available in the already-running browser context.
- The application displayed a “Backup downloaded” success notice with a generated SQL filename. The browser harness did not expose a local download path, so no backup contents were inspected or included in evidence.

## Recommendations

1. Escape the hyphen in the setup username pattern or express it as a literal with a Chromium-compatible pattern, then retest client and server validation.
2. Make dashboard charts responsive and constrain wide tables to a horizontally scrollable container on mobile.
3. Add a favicon or remove the missing favicon request.
4. Add a repeatable Playwright runner and CI configuration when Node/npm is available; retain non-production fixtures for testing sort and pagination across every module.
5. Continue using database backups before any import or clear operation and add integration tests for those destructive workflows using disposable fixtures only.
