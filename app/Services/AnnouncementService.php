<?php

namespace App\Services;

/**
 * announcements (DATABASE.md §10.3, SITEMAP.md §5 `/admin/announcements`, Phase 11) — the table
 * existed since Phase 1's schema migration but had no Service/Controller/View at all until now.
 */
final class AnnouncementService
{
    private SupabaseClient $client;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
    }

    /** @return array<int, array<string,mixed>> newest/pinned first */
    public function listAll(): array
    {
        return $this->client->restGet('announcements', 'select=*&order=is_pinned.desc,published_at.desc');
    }

    public function create(array $data, string $publishedByUserId): void
    {
        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        if ($title === '' || $content === '') {
            throw new AuthException('VALIDATION_ERROR', 'กรุณากรอกหัวข้อและเนื้อหาประกาศ');
        }
        $targetRole = (string) ($data['target_role'] ?? 'all');
        if (!in_array($targetRole, ['all', 'student', 'company', 'teacher'], true)) {
            throw new AuthException('VALIDATION_ERROR', 'กลุ่มเป้าหมายไม่ถูกต้อง');
        }
        $expiresAt = trim((string) ($data['expires_at'] ?? ''));

        $this->client->restInsert('announcements', [
            'title' => $title,
            'content' => $content,
            'target_role' => $targetRole,
            'is_pinned' => isset($data['is_pinned']),
            'published_by' => $publishedByUserId,
            'expires_at' => $expiresAt !== '' ? date('c', strtotime($expiresAt)) : null,
        ]);
    }

    public function delete(int $id): void
    {
        $this->client->restDelete('announcements', 'id=eq.' . $id);
    }
}
