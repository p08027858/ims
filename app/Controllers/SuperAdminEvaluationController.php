<?php

namespace App\Controllers;

use App\Middleware\ActionTokenGuard;
use App\Services\AuthException;
use App\Services\EvaluationService;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * RULE-EVAL-05 exception path — Super Admin can edit scores after submission. Gated at the
 * route level to role=super_admin only (config/routes.php + config/actions.php); since Phase 10
 * the actual PIN re-verification step (SECURITY.md §6) is enforced too via ActionTokenGuard —
 * evaluation_override.php's submit button verifies PIN inline via fetch() first and fills a
 * hidden `action_token` field before the form's normal (non-JS) POST.
 */
final class SuperAdminEvaluationController
{
    private EvaluationService $evaluations;

    public function __construct()
    {
        $this->evaluations = new EvaluationService();
    }

    /** GET /super-admin/evaluations/{id}/override loader. */
    public function formData(array $params): array
    {
        $evaluationId = (int) $params['id'];
        $client = new SupabaseClient();
        $rows = $client->restGet('evaluations', 'id=eq.' . $evaluationId . '&select=*');
        if (!isset($rows[0])) {
            return ['notFound' => true];
        }
        $eval = $rows[0];
        $criteria = $client->restGet('evaluation_criteria', 'template_id=eq.' . $eval['template_id'] . '&select=id,criteria_name,max_score&order=sort_order.asc');
        $scores = $client->restGet('evaluation_scores', 'evaluation_id=eq.' . $evaluationId . '&select=criteria_id,score');
        $scoreByCriteria = array_column($scores, 'score', 'criteria_id');

        return [
            'notFound' => false,
            'evaluationId' => $evaluationId,
            'currentTotal' => $eval['total_score'],
            'status' => $eval['status'],
            'criteria' => array_map(static fn (array $c) => [
                'id' => $c['id'], 'name' => $c['criteria_name'], 'max' => (float) $c['max_score'],
                'current' => (float) ($scoreByCriteria[$c['id']] ?? 0),
            ], $criteria),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function override(array $params): void
    {
        $evaluationId = (int) $params['id'];
        $user = Session::user();
        try {
            ActionTokenGuard::require();
            $scores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
            $this->evaluations->adminOverrideScores($evaluationId, $scores, (string) $user['id']);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /super-admin/evaluations/' . $evaluationId . '/override');
        exit;
    }
}
