<?php

namespace App\Controllers;

use App\Services\SupabaseClient;

/**
 * GET /super-admin/permissions — read-only view of the seeded `permissions` RBAC matrix
 * (PERMISSIONS.md §2, DATABASE.md §2.3). Deliberately NOT an editor: real authorization at
 * runtime is enforced by the `role` column already declared per route in config/routes.php and
 * config/actions.php (App\Middleware\AuthGuard), not by querying this table dynamically — see the
 * architecture note on `permissions` in DATABASE.md §2.3. Building a live-editable matrix that
 * actually changes enforcement would be a much larger, undocumented refactor with no Phase 10
 * DoD/test case behind it, so this page only ever reflects what's seeded, for reference.
 */
final class SuperAdminPermissionController
{
    public function listData(array $params): array
    {
        $rows = (new SupabaseClient())->restGet('permissions', 'select=*&order=role.asc,module.asc,action.asc');
        return ['permissions' => $rows];
    }
}
