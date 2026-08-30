<?php

namespace App\Services;

use App\Support\Uuid;

/**
 * Handles immutable digital signature records.
 */
final class SignatureService
{
    private SupabaseClient $client;
    private GoogleDriveStorageClient $drive;

    public function __construct(?SupabaseClient $client = null, ?GoogleDriveStorageClient $drive = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->drive = $drive ?? new GoogleDriveStorageClient();
    }

    /**
     * @param string $documentType one of signed_document_type enum
     * @return array{id:int,signed_at:string}
     */
    public function create(string $userId, string $documentType, int $documentId, string $base64Png, string $ip, string $userAgent): array
    {
        if (trim($base64Png) === '') {
            throw new AuthException('SIGNATURE_REQUIRED', 'กรุณาลงลายเซ็นก่อนส่งเอกสาร');
        }

        $data = $base64Png;
        if (preg_match('/^data:image\/png;base64,(.+)$/', $base64Png, $m)) {
            $data = $m[1];
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            throw new AuthException('VALIDATION_ERROR', 'ข้อมูลลายเซ็นไม่ถูกต้อง');
        }

        $filename = $documentType . '-' . Uuid::v4() . '.png';
        $signatureUrl = $this->drive->upload('signatures', $filename, $binary, 'image/png', $userId);
        if ($signatureUrl === '') {
            $signatureUrl = $base64Png;
        }

        $rows = $this->client->restInsert('digital_signatures', [
            'user_id' => $userId,
            'signature_url' => $signatureUrl,
        ]);

        return ['id' => $rows[0]['id'], 'signed_at' => (string) ($rows[0]['created_at'] ?? '')];
    }
}
