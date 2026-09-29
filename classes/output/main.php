<?php
namespace block_dashboard_data_visualization\output;

defined('MOODLE_INTERNAL') || die();

use block_dashboard_data_visualization\data_provider;
use block_dashboard_data_visualization\layout;
use renderable;
use templatable;
use renderer_base;

class main implements renderable, templatable {

    /** @var string Component name used for every string lookup in this class. */
    const PLUGIN = 'block_dashboard_data_visualization';

    /** @var array Slice colours of the actions doughnut, in label order. */
    const ACTION_COLOURS = ['#4e73df', '#1cc88a', '#f6c23e', '#858796'];

    private $config;

    public function __construct($config = null) {
        $this->config = $config;
    }

    public function export_for_template(renderer_base $output): array {
        global $USER;

        $userid = $USER->id;
        $chartcolor = layout::clean_colour($this->cfg('chart_color'));

        // ── KPIs ──
        // The engagement history feeds both the KPI trend and the line chart, so it is fetched
        // whenever either of them is on screen.
        $showkpiengagement = $this->is_visible('kpi_engagement');
        $showengagementchart = $this->is_visible('engagement_over_time');

        $improvement = 0;
        $engagement_chart = '';
        if ($showkpiengagement || $showengagementchart) {
            $eot = data_provider::get_engagement_over_time($userid);

            if (count($eot['values']) >= 2) {
                $first = $eot['values'][0];
                $last = end($eot['values']);
                $improvement = $first > 0 ? round(($last - $first) / $first * 100) : 0;
            }

            if ($showengagementchart) {
                $linechart = new \core\chart_line();
                $linechart->set_smooth(true);
                $series = new \core\chart_series(
                    get_string('engagement_score', self::PLUGIN),
                    $eot['values']
                );
                $series->set_color($chartcolor);
                $linechart->add_series($series);
                $linechart->set_labels($eot['labels']);
                $engagement_chart = $output->render_chart($linechart, false);
            }
        }

        $kpidefs = [];

        if ($showkpiengagement) {
            $kpidefs['kpi_engagement'] = [
                'icon' => 'fa-bolt',
                'iconbg' => '#eef2ff',
                'iconcolor' => '#4e73df',
                'label' => get_string('engagement_score', self::PLUGIN),
                'value' => data_provider::get_engagement_score($userid),
                'unit' => get_string('of100', self::PLUGIN),
                'trend' => $improvement > 0
                    ? '▲ ' . $improvement . '% ' . get_string('vs_last_month', self::PLUGIN)
                    : '',
            ];
        }

        if ($this->is_visible('kpi_grade')) {
            $avggrade = data_provider::get_average_grade($userid);
            $kpidefs['kpi_grade'] = [
                'icon' => 'fa-star',
                'iconbg' => '#eafbf0',
                'iconcolor' => '#1cc88a',
                'label' => get_string('average_grade', self::PLUGIN),
                'value' => $avggrade !== null ? round($avggrade) . '%' : '-',
            ];
        }

        if ($this->is_visible('kpi_completed')) {
            $cc = data_provider::get_courses_completed($userid);
            $completionrate = $cc['total'] > 0 ? round($cc['completed'] / $cc['total'] * 100) : 0;
            $kpidefs['kpi_completed'] = [
                'icon' => 'fa-graduation-cap',
                'iconbg' => '#fff8e5',
                'iconcolor' => '#f6c23e',
                'label' => get_string('courses_completed', self::PLUGIN),
                'value' => $cc['completed'],
                'unit' => '/ ' . $cc['total'],
                'sub' => get_string('completion_rate', self::PLUGIN, $completionrate),
            ];
        }

        if ($this->is_visible('kpi_sessions')) {
            $kpidefs['kpi_sessions'] = [
                'icon' => 'fa-clock-o',
                'iconbg' => '#feecec',
                'iconcolor' => '#e74a3b',
                'label' => get_string('study_sessions', self::PLUGIN),
                'value' => data_provider::get_study_sessions($userid),
            ];
        }

        if ($this->is_visible('kpi_streak')) {
            $kpidefs['kpi_streak'] = [
                'icon' => 'fa-fire',
                'iconbg' => '#fff0f6',
                'iconcolor' => '#e83e8c',
                'label' => get_string('learning_streak', self::PLUGIN),
                'value' => data_provider::get_learning_streak($userid),
                'unit' => get_string('days', self::PLUGIN),
                'sub' => get_string('keep_it_up', self::PLUGIN),
                'subclass' => 'ddv-streak-msg',
            ];
        }

        // ── Panels ──
        $paneldefs = [];

        if ($showengagementchart) {
            $paneldefs['engagement_over_time'] = [
                'type' => 'chart',
                'title' => get_string('engagement_over_time', self::PLUGIN),
                'sub' => get_string('engagement_over_time_sub', self::PLUGIN),
                'chart' => $engagement_chart,
                'badge' => $improvement > 0 ? '▲ ' . get_string('improvement', self::PLUGIN, $improvement) : '',
            ];
        }

        if ($this->is_visible('completed_by_subject')) {
            $paneldefs['completed_by_subject'] = [
                'type' => 'chart',
                'title' => get_string('completed_by_subject', self::PLUGIN),
                'sub' => get_string('completed_by_subject_sub', self::PLUGIN),
                'chart' => self::build_horizontal_bar(
                    data_provider::get_completed_by_subject($userid), 'cnt', 'Courses', $output, $chartcolor
                ),
            ];
        }

        if ($this->is_visible('in_progress_by_subject')) {
            $paneldefs['in_progress_by_subject'] = [
                'type' => 'chart',
                'title' => get_string('in_progress_by_subject', self::PLUGIN),
                'sub' => get_string('in_progress_by_subject_sub', self::PLUGIN),
                'chart' => self::build_horizontal_bar(
                    data_provider::get_in_progress_by_subject($userid), 'cnt', 'Courses', $output, $chartcolor
                ),
            ];
        }

        if ($this->is_visible('actions_breakdown')) {
            $actions = data_provider::get_actions_breakdown($userid);
            $totalactions = array_sum($actions);

            $pie = new \core\chart_pie();
            $pie->set_doughnut(true);
            // Chart.js draws its own legend by default, which both duplicates the legend below the
            // chart and pushes the ring off-centre. The block renders its own legend instead.
            $pie->set_legend_options(['display' => false]);
            $pieseries = new \core\chart_series(
                get_string('actions_breakdown', self::PLUGIN),
                array_values($actions)
            );
            $pieseries->set_colors(self::ACTION_COLOURS);
            $pie->add_series($pieseries);

            $actionkeys = ['learning', 'viewed', 'assessments', 'other'];
            $actionlabels = [];
            $legend = [];
            foreach ($actionkeys as $i => $actionkey) {
                $actionlabels[$i] = get_string('action_' . $actionkey, self::PLUGIN);
                $value = isset($actions[$actionkey]) ? $actions[$actionkey] : 0;
                $legend[] = [
                    'color' => self::ACTION_COLOURS[$i],
                    'label' => $actionlabels[$i],
                    'pct' => $totalactions > 0 ? round($value / $totalactions * 100) : 0,
                ];
            }
            $pie->set_labels($actionlabels);

            $paneldefs['actions_breakdown'] = [
                'type' => 'actions',
                'title' => get_string('actions_breakdown', self::PLUGIN),
                'sub' => get_string('actions_breakdown_sub', self::PLUGIN),
                'chart' => $output->render_chart($pie, false),
                'totalactions' => number_format($totalactions),
                'totallabel' => get_string('total_actions', self::PLUGIN),
                'legend' => $legend,
            ];
        }

        if ($this->is_visible('study_consistency')) {
            $sc = data_provider::get_study_consistency($userid);
            $barchart = new \core\chart_bar();
            $barseries = new \core\chart_series(
                get_string('study_consistency', self::PLUGIN),
                $sc['values']
            );
            $barseries->set_color($chartcolor);
            $barchart->add_series($barseries);
            $barchart->set_labels($sc['labels']);

            $paneldefs['study_consistency'] = [
                'type' => 'chart',
                'title' => get_string('study_consistency', self::PLUGIN),
                'sub' => get_string('study_consistency_sub', self::PLUGIN),
                'chart' => $output->render_chart($barchart, false),
            ];
        }

        if ($this->is_visible('top_subjects')) {
            $paneldefs['top_subjects'] = [
                'type' => 'chart',
                'title' => get_string('top_subjects', self::PLUGIN),
                'sub' => get_string('top_subjects_sub', self::PLUGIN),
                'chart' => self::build_horizontal_bar(
                    data_provider::get_top_subjects_by_sessions($userid), 'sessions', 'Sessions', $output, $chartcolor
                ),
            ];
        }

        if ($this->is_visible('performance_by_subject')) {
            $paneldefs['performance_by_subject'] = [
                'type' => 'chart',
                'title' => get_string('performance_by_subject', self::PLUGIN),
                'sub' => get_string('performance_by_subject_sub', self::PLUGIN),
                'chart' => self::build_horizontal_bar(
                    data_provider::get_performance_by_subject($userid), 'avggrade', 'Grade %', $output, $chartcolor
                ),
            ];
        }

        if ($this->is_visible('strengths')) {
            $sw = data_provider::get_strengths_and_weaknesses($userid);
            $paneldefs['strengths'] = [
                'type' => 'strengths',
                'title' => get_string('strengths_areas', self::PLUGIN),
                'strengths' => $sw['strengths'],
                'weaknesses' => $sw['weaknesses'],
                'has_strengths' => !empty($sw['strengths']),
                'has_weaknesses' => !empty($sw['weaknesses']),
            ];
        }

        if ($this->is_visible('course_progress')) {
            $courserows = data_provider::get_course_progress($userid);
            $paneldefs['course_progress'] = [
                'type' => 'coursetable',
                'title' => get_string('course_progress', self::PLUGIN),
                'courserows' => $courserows,
                'has_courses' => !empty($courserows),
            ];
        }

        return [
            'username' => $USER->firstname,
            'kpis' => $this->order_sections($kpidefs, layout::kpis(), false),
            'has_kpis' => !empty($kpidefs),
            'panels' => $this->order_sections($paneldefs, layout::panels(), true),
        ];
    }

    /**
     * Turn the collected section definitions into the ordered list the template renders.
     *
     * @param array $sections section key => definition, in default order
     * @param array $defaults section key => default order (KPIs) or ['order', 'width'] (panels)
     * @param bool $withwidth whether the sections carry a configurable grid width
     * @return array
     */
    private function order_sections(array $sections, array $defaults, bool $withwidth): array {
        $items = [];
        $position = 0;

        foreach ($sections as $key => $def) {
            $defaultorder = $withwidth ? $defaults[$key]['order'] : $defaults[$key];

            $item = $def + [
                'unit' => '',
                'sub' => '',
                'subclass' => '',
                'trend' => '',
                'badge' => '',
            ];

            // KPI keys are prefixed to keep the config names unambiguous; the CSS hook is not.
            $item['key'] = str_replace('_', '-', $withwidth ? $key : substr($key, strlen('kpi_')));
            $item['order'] = (int)$this->cfg('order_' . $key, $defaultorder);
            $item['position'] = $position++;
            $item['has_unit'] = $item['unit'] !== '';
            $item['has_sub'] = $item['sub'] !== '';
            $item['has_trend'] = $item['trend'] !== '';
            $item['has_badge'] = $item['badge'] !== '';

            if ($withwidth) {
                $item['width'] = $this->width_of($key, $defaults[$key]['width']);
                $item['is_chart'] = $def['type'] === 'chart';
                $item['is_actions'] = $def['type'] === 'actions';
                $item['is_strengths'] = $def['type'] === 'strengths';
                $item['is_coursetable'] = $def['type'] === 'coursetable';
            }

            $items[] = $item;
        }

        // Equal order values keep their default relative position.
        usort($items, function($a, $b) {
            return [$a['order'], $a['position']] <=> [$b['order'], $b['position']];
        });

        return $items;
    }

    /**
     * Read a block instance config value.
     *
     * Moodle strips the config_ prefix from the edit form field names before storing them, so the
     * keys here are the bare names (chart_color, order_kpi_streak, ...).
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private function cfg(string $name, $default = null) {
        if ($this->config !== null && isset($this->config->$name) && $this->config->$name !== '') {
            return $this->config->$name;
        }
        return $default;
    }

    /**
     * Whether a section is switched on. Sections default to visible.
     *
     * @param string $key
     * @return bool
     */
    private function is_visible(string $key): bool {
        $name = 'show_' . $key;
        if ($this->config === null || !isset($this->config->$name)) {
            return true;
        }
        return (bool)$this->config->$name;
    }

    /**
     * Grid width of a panel, in twelfths, ignoring values outside the allowed set.
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    private function width_of(string $key, int $default): int {
        $width = (int)$this->cfg('width_' . $key, $default);
        return in_array($width, layout::WIDTHS, true) ? $width : $default;
    }

    /**
     * Build a horizontal bar chart from a list of category records.
     *
     * @param array $records records carrying a catname and the value field
     * @param string $valuefield name of the numeric property to plot
     * @param string $serieslabel series label shown in tooltips
     * @param renderer_base $output
     * @param string $color accent colour
     * @return string
     */
    private static function build_horizontal_bar(array $records, string $valuefield, string $serieslabel,
            renderer_base $output, string $color): string {
        $labels = [];
        $values = [];
        foreach ($records as $r) {
            $labels[] = $r->catname;
            $values[] = $valuefield === 'avggrade' ? round((float)$r->$valuefield) : (int)$r->$valuefield;
        }
        if (empty($values)) {
            return '<p class="text-muted small">' . get_string('no_data', self::PLUGIN) . '</p>';
        }
        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $series = new \core\chart_series($serieslabel, $values);
        $series->set_color($color);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $output->render_chart($chart, false);
    }
}
