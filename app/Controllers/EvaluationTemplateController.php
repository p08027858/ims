<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\EvaluationService;
use App\Support\Session;

/** Admin console for evaluation_templates/evaluation_criteria (Phase 8 item 5, RULE-EVAL-03). */
final class EvaluationTemplateController
{
    private EvaluationService $evaluations;

    public function __construct()
    {
        $this->evaluations = new EvaluationService();
    }

    public function listData(array $params): array
    {
        $labels = ['company_weekly' => 'ประเมินรายสัปดาห์ (ผู้ประกอบการ)', 'company_final' => 'ประเมินปลายภาค (ผู้ประกอบการ)', 'teacher_final' => 'ประเมินปลายภาค (ครูนิเทศ)'];
        $templates = $this->evaluations->listAllTemplatesWithCriteria();
        return [
            'templates' => array_map(static fn (array $t) => [
                'id' => $t['id'],
                'label' => $labels[$t['evaluator_type']] ?? $t['name'],
                'max_score' => $t['max_score'],
                'criteria_count' => count($t['criteria']),
            ], $templates),
        ];
    }

    public function formData(array $params): array
    {
        $template = $this->evaluations->getTemplateWithCriteriaById((int) $params['id']);
        if ($template === null) {
            return ['notFound' => true];
        }
        return [
            'notFound' => false,
            'templateId' => $template['id'],
            'templateName' => $template['name'],
            'maxScore' => $template['max_score'],
            'criteria' => $template['criteria'],
            'formError' => Session::pullFlashError(),
        ];
    }

    public function update(array $params): void
    {
        $templateId = (int) $params['id'];
        try {
            $newMax = (float) ($_POST['max_score'] ?? 0);
            $names = is_array($_POST['criteria_name'] ?? null) ? $_POST['criteria_name'] : [];
            $scores = is_array($_POST['criteria_max'] ?? null) ? $_POST['criteria_max'] : [];
            $updates = [];
            foreach ($names as $criteriaId => $name) {
                $updates[(int) $criteriaId] = ['name' => (string) $name, 'max_score' => (float) ($scores[$criteriaId] ?? 0)];
            }
            $this->evaluations->updateTemplateCriteria($templateId, $newMax, $updates);
            header('Location: /admin/evaluation-templates');
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
            header('Location: /admin/evaluation-templates/' . $templateId);
        }
        exit;
    }
}
