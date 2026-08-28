<?php

namespace App\Services;

/**
 * notifications (AI_AGENT_PHASES.md Phase 9 items 1-2). The RULE-NOTI-01..05 cron checks live
 * here too (used by cron/send_notifications.php) since they're really just "figure out who to
 * notify and call send()" — same domain, same file, matching how AttendanceService/
 * DailyLogService kept their own Phase 9-adjacent cron logic (closeStaleIncompleteDays()/
 * notifyMissedLogs()) alongside their regular CRUD in earlier phases rather than a separate class.
 */
final class NotificationService
{
    private const TYPE_META = [
        'no_checkin_reminder' => ['icon' => 'location_on', 'color' => 'status-error'],
        'no_daily_log_reminder' => ['icon' => 'description', 'color' => 'status-warning'],
        'weekly_eval_overdue' => ['icon' => 'star', 'color' => 'status-warning'],
        'final_eval_overdue' => ['icon' => 'grading', 'color' => 'status-warning'],
        'deadline_approaching' => ['icon' => 'event', 'color' => 'primary'],
        'daily_log_missed' => ['icon' => 'description', 'color' => 'status-warning'],
        'leave_days_exceeded' => ['icon' => 'event_busy', 'color' => 'status-error'],
    ];

    private SupabaseClient $client;
    private SettingsService $settings;

    public function __construct(?SupabaseClient $client = null)
    {
        $this->client = $client ?? new SupabaseClient();
        $this->settings = new SettingsService($this->client);
    }

    public function send(string $userId, string $type, string $title, string $message, ?string $linkUrl = null): void
    {
        $this->client->restInsert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link_url' => $linkUrl,
        ]);
    }

    public function countUnread(string $userId): int
    {
        return count($this->client->restGet('notifications', 'user_id=eq.' . $userId . '&is_read=eq.false&select=id'));
    }

    /** Shapes onto partials/notification_dropdown.php's expected array (group/icon/color/title/desc/time/unread). */
    public function listRecentForDropdown(string $userId, int $limit = 8): array
    {
        $rows = $this->client->restGet('notifications', 'user_id=eq.' . $userId . '&order=created_at.desc&limit=' . $limit . '&select=*');
        return array_map(fn (array $n) => $this->shapeForDisplay($n), $rows);
    }

    public function listAllForUser(string $userId): array
    {
        $rows = $this->client->restGet('notifications', 'user_id=eq.' . $userId . '&order=created_at.desc&select=*');
        return array_map(fn (array $n) => $this->shapeForDisplay($n), $rows);
    }

    public function markRead(int $id, string $userId): void
    {
        $this->client->restUpdate('notifications', 'id=eq.' . $id . '&user_id=eq.' . $userId, [
            'is_read' => true,
            'read_at' => date('c'),
        ]);
    }

    public function markAllRead(string $userId): void
    {
        $this->client->restUpdate('notifications', 'user_id=eq.' . $userId . '&is_read=eq.false', [
            'is_read' => true,
            'read_at' => date('c'),
        ]);
    }

    private function shapeForDisplay(array $n): array
    {
        $meta = self::TYPE_META[$n['type']] ?? ['icon' => 'notifications', 'color' => 'primary'];
        $createdTs = strtotime($n['created_at']);
        return [
            'id' => $n['id'],
            'group' => date('Y-m-d', $createdTs) === date('Y-m-d') ? 'วันนี้' : 'ก่อนหน้านี้',
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'title' => $n['title'],
            'desc' => $n['message'],
            'time' => $this->relativeTime($createdTs),
            'unread' => !$n['is_read'],
            'link_url' => $n['link_url'],
        ];
    }

    private function relativeTime(int $ts): string
    {
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'เมื่อสักครู่';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . ' นาทีที่แล้ว';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
        }
        if ($diff < 172800) {
            return 'เมื่อวานนี้';
        }
        return (int) floor($diff / 86400) . ' วันที่แล้ว';
    }

    // -----------------------------------------------------------------------
    // RULE-NOTI-01..05 — cron/send_notifications.php (DEPLOYMENT.md §7, every 30 min).
    // Two dedup strategies: alreadySentToday() for daily-recurring conditions (01/02/05, where
    // re-reminding once a day while the condition holds is expected UX); alreadySentEver() for
    // 03/04 where the `type` string is made unique per (internship[,week]) so a single missed
    // evaluation only ever notifies once instead of spamming daily until someone acts on it.
    // -----------------------------------------------------------------------

    /**
     * `notifications.created_at` is a UTC timestamptz, but "today" here means the Bangkok
     * calendar day. A bare `date('Y-m-d')."T00:00:00"` string carries no offset, so PostgREST
     * reads it as UTC — 7 hours off from actual Bangkok midnight — which let same-day duplicates
     * slip through in Phase 9 live testing. Converting Bangkok midnight to its UTC instant first
     * removes the ambiguity.
     */
    private function alreadySentToday(string $userId, string $type): bool
    {
        $todayStartUtc = (new \DateTimeImmutable('today', new \DateTimeZone('Asia/Bangkok')))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s');
        $rows = $this->client->restGet(
            'notifications',
            'user_id=eq.' . $userId . '&type=eq.' . rawurlencode($type) . '&created_at=gte.' . $todayStartUtc . '&select=id&limit=1'
        );
        return !empty($rows);
    }

    private function alreadySentEver(string $userId, string $type): bool
    {
        $rows = $this->client->restGet('notifications', 'user_id=eq.' . $userId . '&type=eq.' . rawurlencode($type) . '&select=id&limit=1');
        return !empty($rows);
    }

    /** RULE-NOTI-01: not checked in by settings.notification_no_checkin_time on a working day. */
    public function runNoCheckinReminders(): int
    {
        $cutoff = $this->settings->getString('notification_no_checkin_time', '09:00');
        if (date('H:i') < $cutoff) {
            return 0;
        }
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
        $internships = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,student_id');
        $sent = 0;
        foreach ($internships as $i) {
            $attendance = $this->client->restGet(
                'attendance',
                'internship_id=eq.' . $i['id']
                . '&created_at=gte.' . rawurlencode($today . 'T00:00:00')
                . '&created_at=lt.' . rawurlencode($tomorrow . 'T00:00:00')
                . '&select=check_in_at,status'
            );
            if (isset($attendance[0]) && ($attendance[0]['check_in_at'] !== null || in_array($attendance[0]['status'], ['leave', 'holiday'], true))) {
                continue; // already checked in, or excused today
            }
            $student = $this->client->restGet('students', 'id=eq.' . $i['student_id'] . '&select=user_id');
            $userId = $student[0]['user_id'] ?? null;
            if ($userId === null || $this->alreadySentToday($userId, 'no_checkin_reminder')) {
                continue;
            }
            $this->send($userId, 'no_checkin_reminder', 'ยังไม่ได้ลงเวลาเข้างานวันนี้', "กรุณาลงเวลาเข้างานให้เรียบร้อย (เลยเวลา {$cutoff} น. แล้ว)", '/student/attendance');
            $sent++;
        }
        return $sent;
    }

    /**
     * RULE-NOTI-02: checked in today but no daily_logs row for today yet, reminded before
     * midnight. The blueprint says "by midnight" but never names an exact start hour for the
     * reminder itself — 20:00 is a reasonable simplification, documented in ISSUES.md.
     */
    public function runNoDailyLogReminders(): int
    {
        if ((int) date('H') < 20) {
            return 0;
        }
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
        $internships = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,student_id');
        $sent = 0;
        foreach ($internships as $i) {
            $checkedIn = $this->client->restGet(
                'attendance',
                'internship_id=eq.' . $i['id']
                . '&created_at=gte.' . rawurlencode($today . 'T00:00:00')
                . '&created_at=lt.' . rawurlencode($tomorrow . 'T00:00:00')
                . '&check_in_at=not.is.null&select=id'
            );
            if (empty($checkedIn)) {
                continue; // not checked in at all today — RULE-NOTI-01's concern, not this one
            }
            $logs = $this->client->restGet('daily_logs', 'internship_id=eq.' . $i['id'] . '&log_date=eq.' . $today . '&select=id');
            if (!empty($logs)) {
                continue; // already has a log (draft or submitted) for today
            }
            $student = $this->client->restGet('students', 'id=eq.' . $i['student_id'] . '&select=user_id');
            $userId = $student[0]['user_id'] ?? null;
            if ($userId === null || $this->alreadySentToday($userId, 'no_daily_log_reminder')) {
                continue;
            }
            $this->send($userId, 'no_daily_log_reminder', 'ยังไม่ได้บันทึกงานวันนี้', 'กรุณาบันทึกงานประจำวันให้เรียบร้อยก่อนเที่ยงคืน', '/student/daily-logs/new');
            $sent++;
        }
        return $sent;
    }

    /** RULE-NOTI-03: company hasn't submitted a weekly eval within notification_eval_pending_days of that week ending. */
    public function runWeeklyEvalOverdueReminders(): int
    {
        $pendingDays = $this->settings->getInt('notification_eval_pending_days', 3);
        $template = (new EvaluationService($this->client))->getTemplateByType('company_weekly');
        if ($template === null) {
            return 0;
        }
        $today = strtotime(date('Y-m-d'));
        $internships = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,company_id,start_date');
        $admins = $this->client->restGet('users', 'role=in.(admin,super_admin)&status=eq.active&select=id');

        $sent = 0;
        foreach ($internships as $i) {
            $startTs = strtotime($i['start_date']);
            $completedWeeks = intdiv((int) floor(($today - $startTs) / 86400), 7);
            for ($week = 1; $week <= $completedWeeks; $week++) {
                $weekEndTs = $startTs + $week * 7 * 86400;
                if ((int) floor(($today - $weekEndTs) / 86400) < $pendingDays) {
                    continue;
                }
                $existing = $this->client->restGet(
                    'evaluations',
                    'internship_id=eq.' . $i['id'] . '&template_id=eq.' . $template['id'] . '&week_number=eq.' . $week . '&status=eq.submitted&select=id'
                );
                if (!empty($existing)) {
                    continue;
                }
                $type = 'weekly_eval_overdue_i' . $i['id'] . '_w' . $week;
                $supervisors = $this->client->restGet('company_supervisors', 'company_id=eq.' . $i['company_id'] . '&select=user_id');
                foreach ($supervisors as $s) {
                    if ($this->alreadySentEver($s['user_id'], $type)) {
                        continue;
                    }
                    $this->send($s['user_id'], $type, 'ค้างประเมินรายสัปดาห์', "ยังไม่ได้ประเมินรายสัปดาห์ที่ {$week} ของนักศึกษาในความดูแล", '/company/evaluations/weekly');
                    $sent++;
                }
                foreach ($admins as $a) {
                    if ($this->alreadySentEver($a['id'], $type)) {
                        continue;
                    }
                    $this->send($a['id'], $type, 'สำเนา: ค้างประเมินรายสัปดาห์', "สถานประกอบการยังไม่ได้ประเมินรายสัปดาห์ที่ {$week} (internship #{$i['id']})", '/admin/reports');
                    $sent++;
                }
            }
        }
        return $sent;
    }

    /** RULE-NOTI-04: teacher hasn't submitted the final eval within deadline_warning_days-equivalent of end_date (uses a fixed 7 days per the rule itself). */
    public function runFinalEvalOverdueReminders(): int
    {
        $template = (new EvaluationService($this->client))->getTemplateByType('teacher_final');
        if ($template === null) {
            return 0;
        }
        $today = strtotime(date('Y-m-d'));
        $internships = $this->client->restGet('internships', 'status=in.(active,completed)&deleted_at=is.null&select=id,teacher_id,end_date');
        $admins = $this->client->restGet('users', 'role=in.(admin,super_admin)&status=eq.active&select=id');

        $sent = 0;
        foreach ($internships as $i) {
            if ((int) floor(($today - strtotime($i['end_date'])) / 86400) < 7) {
                continue;
            }
            $existing = $this->client->restGet(
                'evaluations',
                'internship_id=eq.' . $i['id'] . '&template_id=eq.' . $template['id'] . '&status=eq.submitted&select=id'
            );
            if (!empty($existing)) {
                continue;
            }
            $type = 'final_eval_overdue_i' . $i['id'];
            $teacher = $this->client->restGet('teachers', 'id=eq.' . $i['teacher_id'] . '&select=user_id');
            $teacherUserId = $teacher[0]['user_id'] ?? null;
            if ($teacherUserId !== null && !$this->alreadySentEver($teacherUserId, $type)) {
                $this->send($teacherUserId, $type, 'ค้างประเมินปลายภาค', 'ยังไม่ได้ประเมินปลายภาคของนักศึกษาที่ครบกำหนดฝึกงานแล้ว', '/teacher/evaluations/final');
                $sent++;
            }
            foreach ($admins as $a) {
                if ($this->alreadySentEver($a['id'], $type)) {
                    continue;
                }
                $this->send($a['id'], $type, 'สำเนา: ค้างประเมินปลายภาค', "ครูนิเทศยังไม่ได้ประเมินปลายภาค (internship #{$i['id']})", '/admin/reports');
                $sent++;
            }
        }
        return $sent;
    }

    /** RULE-NOTI-05: ≤ settings.deadline_warning_days remaining before end_date. */
    public function runDeadlineApproachingReminders(): int
    {
        $warningDays = $this->settings->getInt('deadline_warning_days', 7);
        $today = strtotime(date('Y-m-d'));
        $internships = $this->client->restGet('internships', 'status=eq.active&deleted_at=is.null&select=id,student_id,company_id,end_date');

        $sent = 0;
        foreach ($internships as $i) {
            $daysLeft = (int) floor((strtotime($i['end_date']) - $today) / 86400);
            if ($daysLeft < 0 || $daysLeft > $warningDays) {
                continue;
            }
            $student = $this->client->restGet('students', 'id=eq.' . $i['student_id'] . '&select=user_id');
            $studentUserId = $student[0]['user_id'] ?? null;
            if ($studentUserId !== null && !$this->alreadySentToday($studentUserId, 'deadline_approaching')) {
                $this->send($studentUserId, 'deadline_approaching', 'ใกล้ครบกำหนดฝึกงาน', "เหลืออีก {$daysLeft} วันก่อนครบกำหนดการฝึกงาน", '/student/dashboard');
                $sent++;
            }
            $supervisors = $this->client->restGet('company_supervisors', 'company_id=eq.' . $i['company_id'] . '&select=user_id');
            foreach ($supervisors as $s) {
                if ($this->alreadySentToday($s['user_id'], 'deadline_approaching')) {
                    continue;
                }
                $this->send($s['user_id'], 'deadline_approaching', 'ใกล้ครบกำหนดฝึกงาน', "เหลืออีก {$daysLeft} วันก่อนนักศึกษาครบกำหนดการฝึกงาน", '/company/dashboard');
                $sent++;
            }
        }
        return $sent;
    }
}
