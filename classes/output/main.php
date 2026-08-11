<?php
namespace block_dashboard_data_visualization\output;

defined('MOODLE_INTERNAL') || die();

use block_dashboard_data_visualization\data_provider;
use renderable;
use templatable;
use renderer_base;

class main implements renderable, templatable {

    private $config;

    public function __construct($config = null) {
        $this->config = $config;
    }

    public function export_for_template(renderer_base $output): array {
        global $USER;

        $userid = $USER->id;

        // ── KPIs ──
        $engagement = data_provider::get_engagement_score($userid);
        $avggrade = data_provider::get_average_grade($userid);
        $cc = data_provider::get_courses_completed($userid);
        $sessions = data_provider::get_study_sessions($userid);
        $streak = data_provider::get_learning_streak($userid);
        $completionrate = $cc['total'] > 0 ? round($cc['completed'] / $cc['total'] * 100) : 0;

        // ── Chart: Engagement Over Time (Line) ──
        $eot = data_provider::get_engagement_over_time($userid);
        $linechart = new \core\chart_line();
        $linechart->set_smooth(true);
        $series = new \core\chart_series(
            get_string('engagement_score', 'block_dashboard_data_visualization'),
            $eot['values']
        );
        $linechart->add_series($series);
        $linechart->set_labels($eot['labels']);
        $engagement_chart = $output->render_chart($linechart, false);

        // Calculate improvement.
        $improvement = 0;
        if (count($eot['values']) >= 2) {
            $first = $eot['values'][0];
            $last = end($eot['values']);
            $improvement = $first > 0 ? round(($last - $first) / $first * 100) : 0;
        }

        // ── Chart: Completed by Subject (Horizontal Bar) ──
        $cbs = data_provider::get_completed_by_subject($userid);
        $completed_chart = self::build_horizontal_bar($cbs, $output);

        // ── Chart: In Progress by Subject (Horizontal Bar) ──
        $ips = data_provider::get_in_progress_by_subject($userid);
        $inprogress_chart = self::build_horizontal_bar($ips, $output);

        // ── Chart: Actions Breakdown (Doughnut) ──
        $actions = data_provider::get_actions_breakdown($userid);
        $pie = new \core\chart_pie();
        $pie->set_doughnut(true);
        $totalactions = array_sum($actions);
        $pieseries = new \core\chart_series(
            get_string('actions_breakdown', 'block_dashboard_data_visualization'),
            array_values($actions)
        );
        $colors = ['#4e73df', '#1cc88a', '#f6c23e', '#858796'];
        $pieseries->set_colors($colors);
        $pie->add_series($pieseries);
        $pie->set_labels([
            get_string('action_learning', 'block_dashboard_data_visualization'),
            get_string('action_viewed', 'block_dashboard_data_visualization'),
            get_string('action_assessments', 'block_dashboard_data_visualization'),
            get_string('action_other', 'block_dashboard_data_visualization'),
        ]);
        $actions_chart = $output->render_chart($pie, false);

        // Percentages for legend.
        $actionpcts = [];
        foreach ($actions as $key => $val) {
            $actionpcts[$key] = $totalactions > 0 ? round($val / $totalactions * 100) : 0;
        }

        // ── Chart: Study Consistency (Vertical Bar) ──
        $sc = data_provider::get_study_consistency($userid);
        $barchart = new \core\chart_bar();
        $barseries = new \core\chart_series(
            get_string('study_consistency', 'block_dashboard_data_visualization'),
            $sc['values']
        );
        $barchart->add_series($barseries);
        $barchart->set_labels($sc['labels']);
        $consistency_chart = $output->render_chart($barchart, false);

        // ── Chart: Top Subjects by Sessions (Horizontal Bar) ──
        $ts = data_provider::get_top_subjects_by_sessions($userid);
        $topsubjects_chart = self::build_horizontal_bar_sessions($ts, $output);

        // ── Chart: Performance by Subject (Horizontal Bar) ──
        $perf = data_provider::get_performance_by_subject($userid);
        $performance_chart = self::build_horizontal_bar_perf($perf, $output);

        // ── Data: Strengths & Weaknesses ──
        $sw = data_provider::get_strengths_and_weaknesses($userid);

        // ── Data: Course Progress Table ──
        $courserows = data_provider::get_course_progress($userid);

        return [
            'username' => $USER->firstname,
            'engagement' => $engagement,
            'avggrade' => $avggrade !== null ? round($avggrade) . '%' : '-',
            'completed' => $cc['completed'],
            'total_enrolled' => $cc['total'],
            'completionrate' => $completionrate,
            'sessions' => $sessions,
            'streak' => $streak,
            'improvement' => $improvement,
            'engagement_chart' => $engagement_chart,
            'completed_chart' => $completed_chart,
            'inprogress_chart' => $inprogress_chart,
            'actions_chart' => $actions_chart,
            'totalactions' => number_format($totalactions),
            'pct_learning' => $actionpcts['learning'],
            'pct_viewed' => $actionpcts['viewed'],
            'pct_assessments' => $actionpcts['assessments'],
            'pct_other' => $actionpcts['other'],
            'consistency_chart' => $consistency_chart,
            'topsubjects_chart' => $topsubjects_chart,
            'performance_chart' => $performance_chart,
            'strengths' => $sw['strengths'],
            'weaknesses' => $sw['weaknesses'],
            'has_strengths' => !empty($sw['strengths']),
            'has_weaknesses' => !empty($sw['weaknesses']),
            'courserows' => $courserows,
            'has_courses' => !empty($courserows),
            'show_engagement_over_time' => !isset($this->config->show_engagement_over_time) || $this->config->show_engagement_over_time,
            'show_completed_by_subject' => !isset($this->config->show_completed_by_subject) || $this->config->show_completed_by_subject,
            'show_in_progress_by_subject' => !isset($this->config->show_in_progress_by_subject) || $this->config->show_in_progress_by_subject,
            'show_actions_breakdown' => !isset($this->config->show_actions_breakdown) || $this->config->show_actions_breakdown,
            'show_study_consistency' => !isset($this->config->show_study_consistency) || $this->config->show_study_consistency,
            'show_top_subjects' => !isset($this->config->show_top_subjects) || $this->config->show_top_subjects,
            'show_performance_by_subject' => !isset($this->config->show_performance_by_subject) || $this->config->show_performance_by_subject,
            'show_kpi_engagement' => !isset($this->config->show_kpi_engagement) || $this->config->show_kpi_engagement,
            'show_kpi_grade' => !isset($this->config->show_kpi_grade) || $this->config->show_kpi_grade,
            'show_kpi_completed' => !isset($this->config->show_kpi_completed) || $this->config->show_kpi_completed,
            'show_kpi_sessions' => !isset($this->config->show_kpi_sessions) || $this->config->show_kpi_sessions,
            'show_kpi_streak' => !isset($this->config->show_kpi_streak) || $this->config->show_kpi_streak,
            'show_strengths' => !isset($this->config->show_strengths) || $this->config->show_strengths,
            'show_course_progress' => !isset($this->config->show_course_progress) || $this->config->show_course_progress,
        ];
    }

    private static function build_horizontal_bar(array $records, renderer_base $output): string {
        $labels = [];
        $values = [];
        foreach ($records as $r) {
            $labels[] = $r->catname;
            $values[] = (int)$r->cnt;
        }
        if (empty($values)) {
            return '<p class="text-muted small">' . get_string('no_data', 'block_dashboard_data_visualization') . '</p>';
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = new \core\chart_series('Courses', $values);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $output->render_chart($chart, false);
    }

    private static function build_horizontal_bar_sessions(array $records, renderer_base $output): string {
        $labels = [];
        $values = [];
        foreach ($records as $r) {
            $labels[] = $r->catname;
            $values[] = (int)$r->sessions;
        }
        if (empty($values)) {
            return '<p class="text-muted small">' . get_string('no_data', 'block_dashboard_data_visualization') . '</p>';
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = new \core\chart_series('Sessions', $values);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $output->render_chart($chart, false);
    }

    private static function build_horizontal_bar_perf(array $records, renderer_base $output): string {
        $labels = [];
        $values = [];
        foreach ($records as $r) {
            $labels[] = $r->catname;
            $values[] = round((float)$r->avggrade);
        }
        if (empty($values)) {
            return '<p class="text-muted small">' . get_string('no_data', 'block_dashboard_data_visualization') . '</p>';
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = new \core\chart_series('Grade %', $values);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $output->render_chart($chart, false);
    }
}
