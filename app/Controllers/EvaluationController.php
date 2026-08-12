<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\EvaluationService;
use App\Services\SettingsService;
use App\Services\SupabaseClient;
use App\Support\Session;

/** evaluations/evaluation_scores/digital_signatures (Phase 7). */
final class EvaluationController
{
    private EvaluationService $evaluations;

    public function __construct()
    {
        $this->evaluations = new EvaluationService();
    }

    // -----------------------------------------------------------------
    // Company — weekly
    // -----------------------------------------------------------------

    public function weeklyListData(array $params): array
    {
        return ['students' => $this->evaluations->listEligibleForCompanyWeekly($this->resolveCompanyId()), 'weekly' => true, 'listTitle' => 'ประเมินรายสัปดาห์', 'linkBase' => '/company/evaluations/weekly'];
    }

    public function weeklyFormData(array $params): array
    {
        return $this->buildFormData((int) $params['id'], 'company_weekly');
    }

    public function submitWeekly(array $params): void
    {
        $this->doSubmit((int) $params['id'], 'company_weekly', '/company/evaluations/weekly');
    }

    // -----------------------------------------------------------------
    // Company — final
    // -----------------------------------------------------------------

    public function companyFinalListData(array $params): array
    {
        return ['students' => $this->evaluations->listEligibleForCompanyFinal($this->resolveCompanyId()), 'weekly' => false, 'listTitle' => 'ประเมินปลายภาค (ผู้ประกอบการ)', 'linkBase' => '/company/evaluations/final'];
    }

    public function companyFinalFormData(array $params): array
    {
        return $this->buildFormData((int) $params['id'], 'company_final', 'ประเมินปลายภาค (ผู้ประกอบการ)', '/company/evaluations/final/' . (int) $params['id']);
    }

    public function submitCompanyFinal(array $params): void
    {
        $this->doSubmit((int) $params['id'], 'company_final', '/company/evaluations/final');
    }

    // -----------------------------------------------------------------
    // Teacher — final
    // -----------------------------------------------------------------

    public function teacherFinalListData(array $params): array
    {
        return ['students' => $this->evaluations->listEligibleForTeacherFinal($this->resolveTeacherId()), 'weekly' => false, 'listTitle' => 'ประเมินปลายภาค (ครูนิเทศ)', 'linkBase' => '/teacher/evaluations/final'];
    }

    public function teacherFinalFormData(array $params): array
    {
        return $this->buildFormData((int) $params['id'], 'teacher_final', 'ประเมินปลายภาค (ครูนิเทศ)', '/teacher/evaluations/final/' . (int) $params['id']);
    }

    public function submitTeacherFinal(array $params): void
    {
        $this->doSubmit((int) $params['id'], 'teacher_final', '/teacher/evaluations/final');
    }

    // -----------------------------------------------------------------
    // Student — history (RULE-EVAL-06)
    // -----------------------------------------------------------------

    /** Shapes onto student/evaluation_history.php's existing $scoreVisible/$weeklyScores/$companyFinal/$teacherFinal vars. */
    public function studentHistoryData(array $params): array
    {
        $scoreVisible = (new SettingsService())->getBool('score_visible_to_student', true);
        if (!$scoreVisible) {
            return ['scoreVisible' => false];
        }

        $userId = (string) Session::user()['id'];
        $client = new SupabaseClient();
        $students = $client->restGet('students', 'user_id=eq.' . $userId . '&select=id');
        $internships = isset($students[0]) ? $client->restGet('internships', 'student_id=eq.' . $students[0]['id'] . '&select=id&order=created_at.desc&limit=1') : [];
        if (!isset($internships[0])) {
            return ['scoreVisible' => true, 'weeklyScores' => [], 'companyFinal' => null, 'teacherFinal' => null];
        }

        $records = $this->evaluations->getHistoryForInternship((int) $internships[0]['id'], true);
        $weeklyScores = [];
        $companyFinal = null;
        $teacherFinal = null;
        foreach ($records as $r) {
            if ($r['template'] === 'company_weekly') {
                $weeklyScores[] = ['week' => $r['week_number'], 'score' => $r['total_score'], 'max' => 20];
            } elseif ($r['template'] === 'company_final') {
                $companyFinal = $r;
            } elseif ($r['template'] === 'teacher_final') {
                $teacherFinal = $r;
            }
        }
        usort($weeklyScores, static fn (array $a, array $b) => $a['week'] <=> $b['week']);

        return ['scoreVisible' => true, 'weeklyScores' => $weeklyScores, 'companyFinal' => $companyFinal, 'teacherFinal' => $teacherFinal];
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    private function buildFormData(int $internshipId, string $evaluatorType, ?string $title = null, ?string $formAction = null): array
    {
        $context = $this->evaluations->getFormContext($internshipId, $evaluatorType);
        if ($context === null) {
            return ['notFound' => true];
        }
        return [
            'notFound' => false,
            'internshipId' => $internshipId,
            'student' => ['name' => $context['student_name']],
            'weekNumber' => $context['week_number'],
            'formTitle' => $title,
            'formAction' => $formAction,
            'criteria' => array_map(static fn (array $c) => ['id' => $c['id'], 'name' => $c['criteria_name'], 'max' => (float) $c['max_score']], $context['template']['criteria']),
            'formError' => Session::pullFlashError(),
        ];
    }

    private function doSubmit(int $internshipId, string $evaluatorType, string $listPath): void
    {
        $user = Session::user();
        try {
            $weekNumber = isset($_POST['week_number']) && $_POST['week_number'] !== '' ? (int) $_POST['week_number'] : null;
            $scores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
            $result = $this->evaluations->submit(
                $internshipId,
                $evaluatorType,
                $weekNumber,
                $scores,
                (string) ($_POST['overall_comment'] ?? ''),
                (string) ($_POST['signature_image'] ?? ''),
                (string) $user['id'],
                (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
            );
            // DoD example action (AI_AGENT_PHASES.md Phase 9): "submit evaluation" must appear in audit_logs.
            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'evaluations', 'evaluations', (int) $result['evaluation_id'], null, ['status' => 'submitted', 'total_score' => $result['total_score']]);
            header('Location: ' . $listPath);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: ' . $listPath . '/' . $internshipId);
        }
        exit;
    }

    private function resolveCompanyId(): int
    {
        $userId = (string) Session::user()['id'];
        $rows = (new SupabaseClient())->restGet('company_supervisors', 'user_id=eq.' . $userId . '&select=company_id');
        return (int) ($rows[0]['company_id'] ?? 0);
    }

    private function resolveTeacherId(): int
    {
        $userId = (string) Session::user()['id'];
        $rows = (new SupabaseClient())->restGet('teachers', 'user_id=eq.' . $userId . '&select=id');
        return (int) ($rows[0]['id'] ?? 0);
    }
}
