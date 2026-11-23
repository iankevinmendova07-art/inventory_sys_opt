# Google Sheets school profile sync

The application always saves school details to its local database first. When sync settings are present, initial administrator setup and later school-details saves also send the profile to Google Sheets. A sync failure does not undo the local save; the UI reports that the sheet was not updated. Each save appends a profile unless its non-empty School ID already exists, in which case that row is updated. A blank School ID always creates a new row. Other rows and columns are left untouched.

## 1. Prepare the spreadsheet

Open the Google spreadsheet and choose or create a tab for the school profile. The receiver can create headings if row 1 is empty. Otherwise, row 1 must contain these exact headings (extra columns are allowed):

`Region`, `School Division`, `Office/School Type`, `School Name`, `School ID`, `School Address`, `School Head/Supply Officer`, `Property Custodian Name`

## 2. Deploy the receiver

1. Open [script.google.com](https://script.google.com/) while signed in to an account that can edit the spreadsheet. Create a standalone Apps Script project.
2. Copy the contents of `tools/google_sheets_school_details_sync.gs` into the script editor. The receiver source remains under `tools`; this guide is stored in `MD FILES`.
3. In **Project Settings → Script Properties**, add:
   - `SCHOOL_DETAILS_SPREADSHEET_ID`: the ID between `/d/` and `/edit` in the spreadsheet URL.
   - `SCHOOL_DETAILS_SHEET_NAME`: the exact tab name.
   - `SCHOOL_DETAILS_WEBHOOK_SECRET`: a long random secret. Keep it private.
4. Choose **Deploy → New deployment → Web app**. Set **Execute as** to your account, and access to **Anyone**. The shared secret verifies requests. Authorize the script when prompted.
5. Copy the deployment URL ending in `/exec`.

When changing an existing receiver, update its code to match `tools/google_sheets_school_details_sync.gs`, then choose **Deploy â†’ Manage deployments â†’ Edit** and select **New version** before deploying. The current application requires receiver schema version 2, which confirms the School ID written to the sheet. An older deployment may accept requests without writing or confirming School ID; the application now reports that sync as failed instead of showing a false success. Check Apps Script execution logs if the write confirmation fails.

## 3. Configure the installer

The Inventory System installer includes the deployment URL supplied for this project. During a normal install, it asks for the shared secret configured in Apps Script Script Properties. Enter the same secret to enable sync. Choose **Skip** to install without sync. Quiet or automated installs do not show the prompt; configure `.env` manually in that case.

For manual configuration, on each computer open `%LOCALAPPDATA%\Programs\InventorySystem`. Create a file named `.env` (or edit it if present) containing:

```ini
SCHOOL_DETAILS_WEBHOOK_URL=https://script.google.com/macros/s/DEPLOYMENT_ID/exec
SCHOOL_DETAILS_WEBHOOK_SECRET=the-same-random-secret
```

Use the `/exec` URL shown in step 2 and the same secret set in Script Properties. Save the file, then save School Details; configuration is read when the profile is saved, so a restart is not required. The app should report that the sheet was updated. Initial administrator setup sends the profile when sync is configured too. If sync fails, local data remains saved; check the app logs under `%LOCALAPPDATA%\InventorySystem\logs` and the Apps Script execution log, then save again to retry.

Keep the `.env` file private. Do not put the secret in source control or share it with users who should not be able to send updates to the sheet. The installer preserves the configured URL and secret when upgrading. If a new script deployment URL is issued, update the `.env` file on each computer.
