<?php

namespace App\Services;

use App\Support\Uuid;

/**
 * Shared upload validation for daily_log_attachments and leave_requests.attachment_path
 * (RULE-FILE-01/02/03) — used by DailyLogService and LeaveService so the same real-MIME-type
 * check (not just extension) and UUID renaming happens exactly once, not duplicated per caller.
 * Throws AuthException (not ApiException) because every caller here is a regular multipart
 * form POST that redirects-with-flash on error, not a JSON fetch() endpoint like Phase 5's.
 */
final class FileUploadService
{
    private SupabaseClient $client;
    private SettingsService $settings;
    private GoogleDriveStorageClient $drive;

    public function __construct(?SupabaseClient $client = null, ?GoogleDriveStorageClient $drive = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
        $this->drive = $drive ?? new GoogleDriveStorageClient();
    }

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file one $_FILES entry
     * @return array{path:string,name:string,type:string,size_kb:int}
     */
    public function validateAndUpload(array $file, string $bucket, string $subpath): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new AuthException('VALIDATION_ERROR', 'อัปโหลดไฟล์ไม่สำเร็จ กรุณาลองใหม่');
        }

        $maxKb = $this->settings->getInt('max_upload_size_kb', 1024);
        $sizeKb = (int) ceil($file['size'] / 1024);
        if ($sizeKb > $maxKb) {
            throw new AuthException('FILE_TOO_LARGE', "ไฟล์มีขนาดเกิน {$maxKb} KB", ['size_kb' => $sizeKb, 'max_kb' => $maxKb]);
        }

        // RULE-FILE-02: the REAL MIME type via finfo (magic bytes), never the client-supplied
        // Content-Type or file extension — TC-SEC-TEST-002: a renamed .exe must still be rejected.
        $allowed = json_decode(
            $this->settings->getString('allowed_file_types', '["image/jpeg","image/png","application/pdf"]'),
            true
        ) ?: ['image/jpeg', 'image/png', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($realMime, $allowed, true)) {
            throw new AuthException('INVALID_FILE_TYPE', 'ชนิดไฟล์ไม่ได้รับอนุญาต (รองรับเฉพาะ JPG, PNG, PDF)', ['detected_type' => $realMime]);
        }

        $ext = match ($realMime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
        // RULE-FILE-03: rename to a UUID before storing — never keep the client's original filename on disk.
        // $subpath (e.g. "internship-11") is folded into the filename itself rather than a nested
        // Drive folder, since google-apps-script/Code.gs keeps one flat folder per $bucket category.
        $storedName = rtrim($subpath, '/') . '-' . Uuid::v4() . '.' . $ext;

        $binary = file_get_contents($file['tmp_name']);
        $path = $this->drive->upload($bucket, $storedName, $binary, $realMime);

        return ['path' => $path, 'name' => $file['name'], 'type' => $realMime, 'size_kb' => $sizeKb];
    }
}
