/**
 * IMS THAI — Google Drive storage proxy (Google Apps Script Web App).
 *
 * Replaces Supabase Storage as the primary file store for:
 *   - attendance-photos   (check-in/check-out photos, ATTENDANCE_GPS.md)
 *   - daily-logs          (daily log attachments, RULE-FILE-01/02/03)
 *   - leave-certificates  (medical certificates, RULE-LEAVE-01)
 *   - signatures          (digital signature PNGs, RULE-SIG-01/02)
 *
 * PHP side calls this via POST as JSON: {secret, category, filename, mimeType, base64Data}.
 * See app/Services/GoogleDriveStorageClient.php for the caller, and DEPLOYMENT.md §9 for the
 * full deploy walkthrough (this comment only covers what the script itself does).
 *
 * Every successful upload is also logged to a "backup" Google Sheet (auto-created on first run)
 * as a lightweight audit trail — category, filename, Drive file ID/URL, uploader info if
 * provided, and timestamp — independent of whatever's in the app's own Supabase audit_logs.
 *
 * SECURITY: this Web App must be deployed with "Who has access: Anyone" (Apps Script requires
 * that for a server like PHP, with no Google login, to be able to POST to it) — which means the
 * URL is reachable by anyone who has it. SHARED_SECRET (set via Script Properties, NOT hardcoded
 * here) is the only thing standing between a random internet request and writing files into your
 * Drive. Set it to a long random string, and put the SAME value in config/google_drive.php on
 * the PHP side. Never commit the actual secret value anywhere.
 */

var ROOT_FOLDER_NAME = 'IMS_THAI_Storage';
var BACKUP_SHEET_NAME = 'IMS_THAI_Backup_Log';
var ALLOWED_CATEGORIES = ['attendance-photos', 'daily-logs', 'leave-certificates', 'signatures'];

function doPost(e) {
  try {
    var body = JSON.parse(e.postData.contents);
    checkSecret_(body.secret);

    var category = String(body.category || '');
    if (ALLOWED_CATEGORIES.indexOf(category) === -1) {
      return jsonResponse_({ success: false, error: 'INVALID_CATEGORY', message: 'category must be one of: ' + ALLOWED_CATEGORIES.join(', ') });
    }
    var filename = String(body.filename || '');
    var mimeType = String(body.mimeType || 'application/octet-stream');
    var base64Data = String(body.base64Data || '');
    if (!filename || !base64Data) {
      return jsonResponse_({ success: false, error: 'VALIDATION_ERROR', message: 'filename and base64Data are required' });
    }

    var bytes = Utilities.base64Decode(base64Data);
    var blob = Utilities.newBlob(bytes, mimeType, filename);

    var folder = getOrCreateCategoryFolder_(category);
    var file = folder.createFile(blob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);

    var fileId = file.getId();
    var url = 'https://drive.google.com/uc?export=view&id=' + fileId;

    appendBackupLogRow_(category, filename, fileId, url, String(body.uploadedBy || ''));

    return jsonResponse_({ success: true, fileId: fileId, url: url });
  } catch (err) {
    return jsonResponse_({ success: false, error: 'SERVER_ERROR', message: String(err && err.message ? err.message : err) });
  }
}

/** Simple unauthenticated health check — visit the deployed URL in a browser to confirm it's live. */
function doGet(e) {
  return jsonResponse_({ success: true, message: 'IMS THAI Google Drive storage proxy is running.' });
}

function checkSecret_(providedSecret) {
  var expected = PropertiesService.getScriptProperties().getProperty('SHARED_SECRET');
  if (!expected) {
    throw new Error('SHARED_SECRET script property is not set — see DEPLOYMENT.md §9 step 3.');
  }
  if (String(providedSecret || '') !== expected) {
    throw new Error('Invalid secret.');
  }
}

function getOrCreateCategoryFolder_(category) {
  var root = getOrCreateFolder_(DriveApp.getRootFolder(), ROOT_FOLDER_NAME);
  return getOrCreateFolder_(root, category);
}

function getOrCreateFolder_(parent, name) {
  var existing = parent.getFoldersByName(name);
  if (existing.hasNext()) {
    return existing.next();
  }
  return parent.createFolder(name);
}

function appendBackupLogRow_(category, filename, fileId, url, uploadedBy) {
  var sheet = getOrCreateBackupSheet_();
  sheet.appendRow([new Date(), category, filename, fileId, url, uploadedBy]);
}

function getOrCreateBackupSheet_() {
  var props = PropertiesService.getScriptProperties();
  var cachedId = props.getProperty('BACKUP_SPREADSHEET_ID');
  if (cachedId) {
    try {
      return SpreadsheetApp.openById(cachedId).getSheets()[0];
    } catch (e) {
      // Fall through and recreate if the cached id no longer resolves (e.g. spreadsheet deleted).
    }
  }
  var root = getOrCreateFolder_(DriveApp.getRootFolder(), ROOT_FOLDER_NAME);
  var ss = SpreadsheetApp.create(BACKUP_SHEET_NAME);
  var file = DriveApp.getFileById(ss.getId());
  root.addFile(file);
  DriveApp.getRootFolder().removeFile(file); // move out of My Drive root, keep only in ROOT_FOLDER_NAME
  var sheet = ss.getSheets()[0];
  sheet.appendRow(['uploaded_at', 'category', 'filename', 'file_id', 'url', 'uploaded_by']);
  props.setProperty('BACKUP_SPREADSHEET_ID', ss.getId());
  return sheet;
}

function jsonResponse_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
