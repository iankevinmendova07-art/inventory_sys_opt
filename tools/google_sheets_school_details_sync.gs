/**
 * Project IAN school profile receiver.
 * Deploy as a web app that executes as the owner. Configure Script Properties:
 *   SCHOOL_DETAILS_SPREADSHEET_ID, SCHOOL_DETAILS_SHEET_NAME, SCHOOL_DETAILS_WEBHOOK_SECRET
 */
function doPost(e) {
  try {
    var properties = PropertiesService.getScriptProperties();
    var secret = properties.getProperty('SCHOOL_DETAILS_WEBHOOK_SECRET') || '';
    var spreadsheetId = properties.getProperty('SCHOOL_DETAILS_SPREADSHEET_ID') || '';
    var sheetName = properties.getProperty('SCHOOL_DETAILS_SHEET_NAME') || '';
    if (!secret || !spreadsheetId || !sheetName) {
      throw new Error('missing_script_properties');
    }
    if (!e || !e.postData || !e.postData.contents) {
      throw new Error('missing_request_body');
    }

    var envelope = JSON.parse(e.postData.contents);
    var payloadJson = Utilities.newBlob(Utilities.base64Decode(envelope.payload)).getDataAsString('UTF-8');
    var expected = toHex_(Utilities.computeHmacSha256Signature(payloadJson, secret));
    if (!constantTimeEquals_(expected, String(envelope.signature || ''))) {
      throw new Error('invalid_signature');
    }

    var payload = JSON.parse(payloadJson);
    var data = payload.data || {};
    var headers = [
      'Region', 'School Division', 'Office/School Type', 'School Name',
      'School ID', 'School Address', 'School Head/Supply Officer', 'Property Custodian Name'
    ];
    var values = headers.map(function(header) {
      if (!Object.prototype.hasOwnProperty.call(data, header)) {
        throw new Error('missing_field_' + header.replace(/[^A-Za-z0-9_]/g, '_'));
      }
      return String(data[header]);
    });

    var sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    if (!sheet) throw new Error('sheet_tab_not_found');

    var lastColumn = Math.max(sheet.getLastColumn(), headers.length);
    var currentHeaders = sheet.getRange(1, 1, 1, lastColumn).getDisplayValues()[0];
    var emptyHeader = currentHeaders.every(function(value) { return value === ''; });
    if (emptyHeader) {
      sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
      currentHeaders = headers;
    }
    var columns = headers.map(function(header) { return currentHeaders.indexOf(header) + 1; });
    if (columns.some(function(column) { return column === 0; })) {
      throw new Error('required_headers_missing');
    }

    var schoolIdIndex = headers.indexOf('School ID');
    var schoolIdColumn = columns[schoolIdIndex];
    var schoolId = values[schoolIdIndex].trim();
    var targetRow = 0;
    var action = 'inserted';

    var lock = LockService.getScriptLock();
    lock.waitLock(10000);
    try {
      var lastRow = sheet.getLastRow();
      if (schoolId !== '' && lastRow >= 2) {
        var existingSchoolIds = sheet.getRange(2, schoolIdColumn, lastRow - 1, 1).getDisplayValues();
        for (var index = 0; index < existingSchoolIds.length; index++) {
          if (String(existingSchoolIds[index][0]).trim() === schoolId) {
            targetRow = index + 2;
            action = 'updated';
            break;
          }
        }
      }
      if (targetRow === 0) targetRow = lastRow + 1;

      columns.forEach(function(column, index) {
        sheet.getRange(targetRow, column).setValue(values[index]);
      });
      SpreadsheetApp.flush();
      var savedSchoolId = String(sheet.getRange(targetRow, schoolIdColumn).getDisplayValue()).trim();
      if (savedSchoolId !== schoolId) {
        throw new Error('school_id_write_not_confirmed');
      }
    } finally {
      lock.releaseLock();
    }
    return json_({ok: true, schemaVersion: 2, schoolId: schoolId, action: action, row: targetRow});
  } catch (error) {
    console.error(String(error && error.stack || error));
    return json_({ok: false, error: String(error && error.message || 'sync_failed')});
  }
}

function json_(value) {
  return ContentService.createTextOutput(JSON.stringify(value))
    .setMimeType(ContentService.MimeType.JSON);
}

function toHex_(bytes) {
  return bytes.map(function(value) {
    var byte = value < 0 ? value + 256 : value;
    return ('0' + byte.toString(16)).slice(-2);
  }).join('');
}

function constantTimeEquals_(left, right) {
  if (left.length !== right.length) return false;
  var difference = 0;
  for (var index = 0; index < left.length; index++) {
    difference |= left.charCodeAt(index) ^ right.charCodeAt(index);
  }
  return difference === 0;
}
