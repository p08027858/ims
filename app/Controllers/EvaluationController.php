<?php

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\AuthException;
use App\Services\EvaluationService;
use App\Services\SettingsService;
use App\Services\SupabaseClient;
use App\Support\Session;
use Throwable;

final class EvaluationController
{
    private EvaluationService $evaluations;

    public function __construct()
    {
        $this->evaluations = new EvaluationService();
    }

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

            (new AuditLogger())->log((string) $user['id'], (string) $user['role'], 'create', 'evaluations', 'evaluations', (int) $result['evaluation_id'], null, ['status' => 'submitted', 'total_score' => $result['total_score']]);
            header('Location: ' . $listPath);
        } catch (AuthException $e) {
            error_log(sprintf(
                '[evaluation.submit] code=%s internship_id=%d evaluator_type=%s user_id=%s details=%s cause=%s',
                $e->errorCode,
                $internshipId,
                $evaluatorType,
                (string) ($user['id'] ?? ''),
                json_encode($e->details, JSON_UNESCAPED_UNICODE),
                $e->getMessage()
            ));
            Session::flashError($this->friendlySubmitError($e));
            header('Location: ' . $listPath . '/' . $internshipId);
        } catch (Throwable $e) {
            error_log(sprintf(
                '[evaluation.submit] unexpected internship_id=%d evaluator_type=%s user_id=%s message=%s',
                $internshipId,
                $evaluatorType,
                (string) ($user['id'] ?? ''),
                $e->getMessage()
            ));
            Session::flashError('บันทึกแบบประเมินไม่สำเร็จ กรุณาตรวจสอบข้อมูลให้ครบถ้วนและลองใหม่อีกครั้ง');
            header('Location: ' . $listPath . '/' . $internshipId);
        }

        exit;
    }

    private function friendlySubmitError(AuthException $e): string
    {
        return match ($e->errorCode) {
            'SIGNATURE_REQUIRED' => 'กรุณาลงลายเซ็นก่อนส่งแบบประเมิน',
            'VALIDATION_ERROR' => $e->getMessage() !== '' ? $e->getMessage() : 'ข้อมูลแบบประเมินไม่ถูกต้อง',
            'EVALUATION_SAVE_FAILED',
            'EVALUATION_SCORE_SAVE_FAILED',
            'SIGNATURE_SAVE_FAILED',
            'EVALUATION_FINALIZE_FAILED' => $e->getMessage() !== '' ? $e->getMessage() : 'บันทึกแบบประเมินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            default => 'บันทึกแบบประเมินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
        };
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
