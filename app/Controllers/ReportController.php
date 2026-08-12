<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\SupabaseClient;
use App\Support\Session;

/**
 * Admin reports (Phase 8 item 4) + teacher reports and PDF/Excel export for both
 * (AI_AGENT_PHASES.md Phase 11 item 3). Excel export is a CSV download (Excel opens it natively,
 * same approach ImportService already relies on for the reverse direction) — no XLSX-writing
 * library exists in this dependency-free project (MASTER_SPEC.md §4). PDF export is a
 * print-optimized HTML page the browser turns into a PDF via its own print dialog (Ctrl+P → Save
 * as PDF) — there's no PHP PDF-generation library here either and none is being added just for
 * this, so this is the honest, dependency-free equivalent rather than a fake working button.
 */
final class ReportController
{
    private ReportService $reports;

    public function __construct()
    {
        $this->reports = new ReportService();
    }

    public function summaryData(array $params): array
    {
        return ['summary' => $this->reports->getSummary()];
    }

    /** GET /teacher/reports loader (Phase 11 — never wired to real data before). */
    public function teacherReportData(array $params): array
    {
        return ['rows' => $this->reports->getTeacherReport($this->resolveTeacherId((string) Session::user()['id']))];
    }

    /** GET /admin/reports/export.csv */
    public function exportAdminCsv(array $params): void
    {
        $summary = $this->reports->getSummary();
        $this->streamCsv('admin_report.csv', ['รายการ', 'จำนวน'], array_map(
            static fn (string $label, $value) => [$label, $value],
            ['นักศึกษาทั้งหมด', 'กำลังฝึกงาน', 'เสร็จสิ้น', 'ยุติ', 'ถอนตัว', 'ชั่วโมงเฉลี่ย', 'คะแนนปลายภาคเฉลี่ย'],
            [$summary['total_students'], $summary['active'], $summary['completed'], $summary['terminated'], $summary['withdrawn'], $summary['avg_attendance_hours'], $summary['avg_final_score']]
        ));
    }

    /** GET /admin/reports/export.pdf (see class docblock — actually a print-optimized HTML page). */
    public function exportAdminPrint(array $params): void
    {
        $summary = $this->reports->getSummary();
        $this->renderPrintPage('รายงานภาพรวม', ['รายการ', 'จำนวน'], array_map(
            static fn (string $label, $value) => [$label, (string) $value],
            ['นักศึกษาทั้งหมด', 'กำลังฝึกงาน', 'เสร็จสิ้น', 'ยุติ', 'ถอนตัว', 'ชั่วโมงเฉลี่ย', 'คะแนนปลายภาคเฉลี่ย'],
            [$summary['total_students'], $summary['active'], $summary['completed'], $summary['terminated'], $summary['withdrawn'], $summary['avg_attendance_hours'], $summary['avg_final_score']]
        ));
    }

    /** GET /teacher/reports/export.csv */
    public function exportTeacherCsv(array $params): void
    {
        $rows = $this->reports->getTeacherReport($this->resolveTeacherId((string) Session::user()['id']));
        $this->streamCsv('teacher_report.csv', ['ชื่อ', 'บริษัท', 'ชม.สะสม', 'คะแนนเฉลี่ย', 'สถานะ'], array_map(
            static fn (array $r) => [$r['name'], $r['company'], $r['hours'], $r['avg_score'], $r['status']],
            $rows
        ));
    }

    /** GET /teacher/reports/export.pdf (see class docblock). */
    public function exportTeacherPrint(array $params): void
    {
        $rows = $this->reports->getTeacherReport($this->resolveTeacherId((string) Session::user()['id']));
        $this->renderPrintPage('รายงานสรุปผลการฝึกงาน', ['ชื่อ', 'บริษัท', 'ชม.สะสม', 'คะแนนเฉลี่ย', 'สถานะ'], array_map(
            static fn (array $r) => [$r['name'], $r['company'], (string) $r['hours'], (string) $r['avg_score'], $r['status']],
            $rows
        ));
    }

    private function resolveTeacherId(string $userId): int
    {
        $rows = (new SupabaseClient())->restGet('teachers', 'user_id=eq.' . $userId . '&select=id');
        return (int) ($rows[0]['id'] ?? 0);
    }

    /** @param array<int,string> $headers @param array<int,array<int,mixed>> $rows */
    private function streamCsv(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel opens Thai text correctly (mirrors ImportService's own BOM-handling note)
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /** @param array<int,string> $headers @param array<int,array<int,string>> $rows */
    private function renderPrintPage(string $title, array $headers, array $rows): void
    {
        header('Content-Type: text/html; charset=utf-8');
        ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($title) ?></title>
<style>
  body { font-family: 'Sarabun', 'Segoe UI', sans-serif; padding: 2rem; color: #1b1b24; }
  h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
  p.meta { color: #666; margin-top: 0; margin-bottom: 1.5rem; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 0.5rem 0.75rem; text-align: left; font-size: 0.9rem; }
  th { background: #f0ecf9; }
  #print-toolbar { margin-bottom: 1.5rem; }
  #print-toolbar button { padding: 0.6rem 1.2rem; background: #4d44e3; color: #fff; border: none; border-radius: 0.5rem; font-size: 0.95rem; cursor: pointer; }
  @media print { #print-toolbar { display: none; } }
</style>
</head>
<body>
  <div id="print-toolbar">
    <button type="button" onclick="window.print()">พิมพ์ / บันทึกเป็น PDF (Ctrl+P)</button>
  </div>
  <h1><?= htmlspecialchars($title) ?></h1>
  <p class="meta">สร้างเมื่อ <?= date('j M Y H:i') ?> น.</p>
  <table>
    <thead><tr><?php foreach ($headers as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr><?php foreach ($row as $cell): ?><td><?= htmlspecialchars((string) $cell) ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
        <?php
        exit;
    }
}
