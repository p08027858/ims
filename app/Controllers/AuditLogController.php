<?php

namespace App\Controllers;

use App\Services\SupabaseClient;

/** GET /admin/audit-logs (Phase 9 item 4) — read-only, audit_logs is immutable (SECURITY.md §5). */
final class AuditLogController
{
    public function listData(array $params): array
    {
        $client = new SupabaseClient();
        $rows = $client->restGet(
            'audit_logs',
            'select=id,user_id,action,entity_name,entity_id,details,ip_address,created_at&order=created_at.desc&limit=100'
        );

        $userIds = array_values(array_unique(array_filter(array_column($rows, 'user_id'))));
        $usersById = [];
        if (!empty($userIds)) {
            $users = $client->restGet('users', 'id=in.(' . implode(',', $userIds) . ')&select=id,email');
            $usersById = array_column($users, 'email', 'id');
        }

        return ['logs' => array_map(function (array $r) use ($usersById) {
            $details = $r['details'] ?? null;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            $details = is_array($details) ? $details : [];
            return [
                'time' => date('d/m/y H:i', strtotime((string) ($r['created_at'] ?? 'now'))),
                'user' => $usersById[$r['user_id'] ?? ''] ?? ($details['role'] ?? '-'),
                'action' => (string) ($r['action'] ?? '-'),
                'entity' => (string) ($r['entity_name'] ?? '-') . (($r['entity_id'] ?? null) !== null ? '#' . $r['entity_id'] : ''),
                'diff' => $this->summarizeDiff($details['before'] ?? null, $details['after'] ?? null),
            ];
        }, $rows)];
    }

    private function summarizeDiff(?array $old, ?array $new): string
    {
        if ($old === null && $new === null) {
            return '-';
        }
        if ($old === null) {
            return json_encode($new, JSON_UNESCAPED_UNICODE) ?: '-';
        }
        if ($new === null) {
            return 'deleted: ' . (json_encode($old, JSON_UNESCAPED_UNICODE) ?: '');
        }
        $parts = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;
            if ($oldVal !== $newVal) {
                $parts[] = "{$key}: " . json_encode($oldVal, JSON_UNESCAPED_UNICODE) . ' → ' . json_encode($newVal, JSON_UNESCAPED_UNICODE);
            }
        }
        return $parts !== [] ? implode(', ', $parts) : '-';
    }
}
