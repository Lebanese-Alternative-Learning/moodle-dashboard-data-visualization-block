<?php
namespace block_dashboard_data_visualization;

defined('MOODLE_INTERNAL') || die();

/**
 * Data provider for the Dashboard Data Visualization block.
 * All static methods query Moodle core tables — no custom tables needed.
 * All queries use cross-DB compatible syntax (no MySQL-specific functions).
 */
class data_provider {

    // ─── KPI: AVERAGE GRADE ──────────────────────────────────────
    public static function get_average_grade(int $userid): ?float {
        global $DB;
        $sql = "SELECT AVG(gg.finalgrade / gg.rawgrademax * 100) AS avggrade
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                 WHERE gg.userid = :userid
                   AND gi.itemtype = 'course'
                   AND gg.finalgrade IS NOT NULL
                   AND gg.rawgrademax > 0";
        $result = $DB->get_record_sql($sql, ['userid' => $userid]);
        return $result && $result->avggrade !== null ? round((float)$result->avggrade, 1) : null;
    }

    // ─── KPI: COURSES COMPLETED ──────────────────────────────────
    public static function get_courses_completed(int $userid): array {
        global $DB;
        $completed = $DB->count_records_select('course_completions',
            'userid = :userid AND timecompleted IS NOT NULL', ['userid' => $userid]);

        $sql = "SELECT COUNT(DISTINCT e.courseid) AS cnt
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid";
        $enrolled = $DB->get_record_sql($sql, ['userid' => $userid]);
        $total = $enrolled ? (int)$enrolled->cnt : 0;

        return ['completed' => (int)$completed, 'total' => $total];
    }

    // ─── KPI: STUDY SESSIONS (current month) ─────────────────────
    public static function get_study_sessions(int $userid): int {
        global $DB;
        $monthstart = mktime(0, 0, 0, date('n'), 1, date('Y'));
        $now = time();

        // Use get_recordset_sql to avoid unique-key requirement.
        $sql = "SELECT id, timecreated
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND timecreated >= :start
                   AND timecreated <= :end
              ORDER BY timecreated ASC";
        $rs = $DB->get_recordset_sql($sql, ['userid' => $userid, 'start' => $monthstart, 'end' => $now]);

        $sessions = 0;
        $lasttime = 0;
        $gap = 30 * 60;
        foreach ($rs as $r) {
            if ($r->timecreated - $lasttime > $gap) {
                $sessions++;
            }
            $lasttime = $r->timecreated;
        }
        $rs->close();
        return $sessions;
    }

    // ─── KPI: LEARNING STREAK (consecutive days) ─────────────────
    public static function get_learning_streak(int $userid): int {
        global $DB;
        // Cross-DB compatible: fetch raw timestamps and compute dates in PHP.
        $sql = "SELECT id, timecreated
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
              ORDER BY timecreated DESC";
        $rs = $DB->get_recordset_sql($sql, ['userid' => $userid]);

        // Collect distinct dates.
        $dates = [];
        foreach ($rs as $r) {
            $date = date('Y-m-d', $r->timecreated);
            $dates[$date] = true;
        }
        $rs->close();

        // Count consecutive days from today backwards.
        $streak = 0;
        $expected = new \DateTime('today');
        while (isset($dates[$expected->format('Y-m-d')])) {
            $streak++;
            $expected->modify('-1 day');
        }
        return $streak;
    }

    // ─── KPI: ENGAGEMENT SCORE ───────────────────────────────────
    public static function get_engagement_score(int $userid): int {
        global $DB;
        $monthstart = mktime(0, 0, 0, date('n'), 1, date('Y'));
        $now = time();

        $actions = $DB->count_records_select('logstore_standard_log',
            'userid = :userid AND timecreated >= :start AND timecreated <= :end',
            ['userid' => $userid, 'start' => $monthstart, 'end' => $now]);
        $activityscore = min(100, (int)($actions / 200 * 100));

        $cc = self::get_courses_completed($userid);
        $completionscore = $cc['total'] > 0 ? (int)($cc['completed'] / $cc['total'] * 100) : 0;

        $grade = self::get_average_grade($userid);
        $gradescore = $grade !== null ? (int)$grade : 0;

        return (int)(0.4 * $activityscore + 0.3 * $completionscore + 0.3 * $gradescore);
    }

    // ─── CHART: ENGAGEMENT OVER TIME (last 6 months) ─────────────
    public static function get_engagement_over_time(int $userid): array {
        $labels = [];
        $values = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = new \DateTime("first day of -$i months");
            $labels[] = $month->format('M');
            $start = (int)$month->format('U');
            $end = (new \DateTime("last day of -$i months"))->setTime(23, 59, 59);
            $endts = (int)$end->format('U');
            $values[] = self::get_engagement_score_for_period($userid, $start, $endts);
        }
        return ['labels' => $labels, 'values' => $values];
    }

    private static function get_engagement_score_for_period(int $userid, int $start, int $end): int {
        global $DB;
        $actions = $DB->count_records_select('logstore_standard_log',
            'userid = :userid AND timecreated >= :start AND timecreated <= :end',
            ['userid' => $userid, 'start' => $start, 'end' => $end]);
        $activityscore = min(100, (int)($actions / 200 * 100));

        $cc = self::get_courses_completed($userid);
        $completionscore = $cc['total'] > 0 ? (int)($cc['completed'] / $cc['total'] * 100) : 0;

        $grade = self::get_average_grade($userid);
        $gradescore = $grade !== null ? (int)$grade : 0;

        return (int)(0.4 * $activityscore + 0.3 * $completionscore + 0.3 * $gradescore);
    }

    // ─── CHART: COURSES COMPLETED BY SUBJECT (category) ──────────
    public static function get_completed_by_subject(int $userid): array {
        global $DB;
        $sql = "SELECT cc2.id, cc2.name AS catname, COUNT(*) AS cnt
                  FROM {course_completions} ccomp
                  JOIN {course} c ON c.id = ccomp.course
                  JOIN {course_categories} cc2 ON cc2.id = c.category
                 WHERE ccomp.userid = :userid
                   AND ccomp.timecompleted IS NOT NULL
              GROUP BY cc2.id, cc2.name
              ORDER BY cnt DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    // ─── CHART: COURSES IN PROGRESS BY SUBJECT ───────────────────
    public static function get_in_progress_by_subject(int $userid): array {
        global $DB;
        $sql = "SELECT cc2.id, cc2.name AS catname, COUNT(DISTINCT c.id) AS cnt
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {course_categories} cc2 ON cc2.id = c.category
             LEFT JOIN {course_completions} ccomp
                    ON ccomp.course = c.id AND ccomp.userid = ue.userid AND ccomp.timecompleted IS NOT NULL
                 WHERE ue.userid = :userid
                   AND ccomp.id IS NULL
              GROUP BY cc2.id, cc2.name
              ORDER BY cnt DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    // ─── CHART: ACTIONS BREAKDOWN ────────────────────────────────
    public static function get_actions_breakdown(int $userid): array {
        global $DB;
        $monthstart = mktime(0, 0, 0, date('n'), 1, date('Y'));
        $sql = "SELECT crud, COUNT(*) AS cnt
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND timecreated >= :start
              GROUP BY crud";
        $records = $DB->get_records_sql($sql, ['userid' => $userid, 'start' => $monthstart]);

        $mapping = ['c' => 'learning', 'r' => 'viewed', 'u' => 'assessments', 'd' => 'other'];
        $result = ['learning' => 0, 'viewed' => 0, 'assessments' => 0, 'other' => 0];
        foreach ($records as $r) {
            $key = $mapping[$r->crud] ?? 'other';
            $result[$key] += (int)$r->cnt;
        }
        return $result;
    }

    // ─── CHART: STUDY CONSISTENCY (sessions per weekday, this week)
    public static function get_study_consistency(int $userid): array {
        global $DB;
        $weekstart = strtotime('monday this week');
        $weekend = strtotime('sunday this week 23:59:59');

        // Cross-DB: fetch raw timestamps, compute day-of-week in PHP.
        $sql = "SELECT id, timecreated
                  FROM {logstore_standard_log}
                 WHERE userid = :userid
                   AND timecreated >= :start
                   AND timecreated <= :end
              ORDER BY timecreated ASC";
        $rs = $DB->get_recordset_sql($sql, ['userid' => $userid, 'start' => $weekstart, 'end' => $weekend]);

        // Mon=1..Sun=7 using PHP date('N').
        $days = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
        $lasttime = [];
        $gap = 30 * 60;

        foreach ($rs as $r) {
            $dow = (int)date('N', $r->timecreated); // 1=Mon ... 7=Sun.
            if (!isset($lasttime[$dow]) || ($r->timecreated - $lasttime[$dow]) > $gap) {
                $days[$dow]++;
            }
            $lasttime[$dow] = $r->timecreated;
        }
        $rs->close();

        return [
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'values' => [$days[1], $days[2], $days[3], $days[4], $days[5], $days[6], $days[7]],
        ];
    }

    // ─── CHART: TOP SUBJECTS BY SESSIONS ─────────────────────────
    public static function get_top_subjects_by_sessions(int $userid): array {
        global $DB;
        // Cross-DB: fetch raw data and compute sessions in PHP.
        $sql = "SELECT l.id, l.timecreated, cc.id AS catid, cc.name AS catname
                  FROM {logstore_standard_log} l
                  JOIN {course} c ON c.id = l.courseid
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE l.userid = :userid
                   AND l.courseid > 1
              ORDER BY cc.id, l.timecreated";
        $rs = $DB->get_recordset_sql($sql, ['userid' => $userid]);

        $catsessions = []; // catid => ['name' => ..., 'sessions' => count].
        $lasttime = [];
        $gap = 30 * 60;

        foreach ($rs as $r) {
            $catid = $r->catid;
            if (!isset($catsessions[$catid])) {
                $catsessions[$catid] = (object)['catname' => $r->catname, 'sessions' => 0];
                $lasttime[$catid] = 0;
            }
            if ($r->timecreated - $lasttime[$catid] > $gap) {
                $catsessions[$catid]->sessions++;
            }
            $lasttime[$catid] = $r->timecreated;
        }
        $rs->close();

        // Sort by sessions DESC, take top 5.
        usort($catsessions, function($a, $b) { return $b->sessions - $a->sessions; });
        return array_slice($catsessions, 0, 5);
    }

    // ─── CHART: PERFORMANCE BY SUBJECT ───────────────────────────
    public static function get_performance_by_subject(int $userid): array {
        global $DB;
        $sql = "SELECT cc.id, cc.name AS catname, AVG(gg.finalgrade / gg.rawgrademax * 100) AS avggrade
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                  JOIN {course} c ON c.id = gi.courseid
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE gg.userid = :userid
                   AND gi.itemtype = 'course'
                   AND gg.finalgrade IS NOT NULL
                   AND gg.rawgrademax > 0
              GROUP BY cc.id, cc.name
              ORDER BY avggrade DESC";
        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    // ─── DATA: STRENGTHS & AREAS TO IMPROVE ──────────────────────
    public static function get_strengths_and_weaknesses(int $userid): array {
        $perf = self::get_performance_by_subject($userid);
        $items = [];
        foreach ($perf as $p) {
            $items[] = ['name' => $p->catname, 'grade' => round((float)$p->avggrade)];
        }
        $strengths = array_slice($items, 0, 3);
        $weaknesses = array_reverse(array_slice($items, -3, 3));
        return ['strengths' => $strengths, 'weaknesses' => $weaknesses];
    }

    // ─── DATA: COURSE PROGRESS TABLE ─────────────────────────────
    public static function get_course_progress(int $userid): array {
        global $DB;
        $sql = "SELECT DISTINCT c.id, c.fullname
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.userid = :userid
                   AND c.id > 1
              ORDER BY c.fullname";
        $courses = $DB->get_records_sql($sql, ['userid' => $userid]);

        $rows = [];
        foreach ($courses as $course) {
            $total = $DB->count_records_select('course_modules', 'course = :cid AND completion > 0 AND deletioninprogress = 0',
                ['cid' => $course->id]);
            $done = 0;
            if ($total > 0) {
                $sql2 = "SELECT COUNT(*) AS cnt
                           FROM {course_modules_completion} cmc
                           JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                          WHERE cmc.userid = :userid
                            AND cm.course = :cid
                            AND cm.completion > 0
                            AND cm.deletioninprogress = 0
                            AND cmc.completionstate > 0";
                $r = $DB->get_record_sql($sql2, ['userid' => $userid, 'cid' => $course->id]);
                $done = $r ? (int)$r->cnt : 0;
            }
            $pct = $total > 0 ? round($done / $total * 100) : 0;

            $gsql = "SELECT gg.finalgrade, gg.rawgrademax
                       FROM {grade_grades} gg
                       JOIN {grade_items} gi ON gi.id = gg.itemid
                      WHERE gg.userid = :userid AND gi.courseid = :cid AND gi.itemtype = 'course'";
            $gr = $DB->get_record_sql($gsql, ['userid' => $userid, 'cid' => $course->id]);
            $grade = ($gr && $gr->finalgrade !== null && $gr->rawgrademax > 0)
                ? round($gr->finalgrade / $gr->rawgrademax * 100) . '%' : '-';

            $la = $DB->get_record('user_lastaccess', ['userid' => $userid, 'courseid' => $course->id]);
            $lastaccess = $la ? self::time_ago($la->timeaccess) : '-';

            $comp = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $course->id]);
            if ($comp && $comp->timecompleted) {
                $status = 'completed';
                $statuscls = 'success';
            } else if ($pct > 0) {
                $status = 'in_progress';
                $statuscls = 'warning';
            } else {
                $status = 'not_started';
                $statuscls = 'secondary';
            }

            $rows[] = [
                'name' => $course->fullname,
                'progress' => $pct,
                'grade' => $grade,
                'lastaccess' => $lastaccess,
                'status' => get_string('status_' . $status, 'block_dashboard_data_visualization'),
                'statuscls' => $statuscls,
            ];
        }
        return $rows;
    }

    private static function time_ago(int $timestamp): string {
        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'Just now';
        } else if ($diff < 3600) {
            $m = floor($diff / 60);
            return $m . ' min ago';
        } else if ($diff < 86400) {
            $h = floor($diff / 3600);
            return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago';
        } else {
            $d = floor($diff / 86400);
            return $d . ' day' . ($d > 1 ? 's' : '') . ' ago';
        }
    }
}
