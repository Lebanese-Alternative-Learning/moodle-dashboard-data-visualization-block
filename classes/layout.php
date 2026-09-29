<?php
namespace block_dashboard_data_visualization;

defined('MOODLE_INTERNAL') || die();

/**
 * Layout definitions shared by the block renderable and its configuration form.
 *
 * Keeping the section keys, default order and default widths in one place means the
 * form and the renderer can never drift apart.
 */
class layout {

    /** Accent colour used by the line and bar charts when nothing is configured. */
    const DEFAULT_CHART_COLOUR = '#4e73df';

    /** Grid widths (in twelfths) a panel is allowed to occupy. */
    const WIDTHS = [3, 4, 6, 8, 12];

    /**
     * KPI cards with their default display order.
     *
     * @return array section key => default order
     */
    public static function kpis(): array {
        return [
            'kpi_engagement' => 10,
            'kpi_grade' => 20,
            'kpi_completed' => 30,
            'kpi_sessions' => 40,
            'kpi_streak' => 50,
        ];
    }

    /**
     * Chart and panel cards with their default display order and grid width.
     *
     * @return array section key => ['order' => int, 'width' => int]
     */
    public static function panels(): array {
        return [
            'engagement_over_time' => ['order' => 10, 'width' => 6],
            'completed_by_subject' => ['order' => 20, 'width' => 3],
            'in_progress_by_subject' => ['order' => 30, 'width' => 3],
            'actions_breakdown' => ['order' => 40, 'width' => 4],
            'study_consistency' => ['order' => 50, 'width' => 4],
            'top_subjects' => ['order' => 60, 'width' => 4],
            'performance_by_subject' => ['order' => 70, 'width' => 4],
            'strengths' => ['order' => 80, 'width' => 4],
            'course_progress' => ['order' => 90, 'width' => 4],
        ];
    }

    /**
     * Options for the per-panel width selector.
     *
     * @return array width in twelfths => human readable label
     */
    public static function width_options(): array {
        return [
            3 => get_string('width_quarter', 'block_dashboard_data_visualization'),
            4 => get_string('width_third', 'block_dashboard_data_visualization'),
            6 => get_string('width_half', 'block_dashboard_data_visualization'),
            8 => get_string('width_twothirds', 'block_dashboard_data_visualization'),
            12 => get_string('width_full', 'block_dashboard_data_visualization'),
        ];
    }

    /**
     * Return a safe #rrggbb colour, falling back when the configured value is unusable.
     *
     * @param mixed $value the raw configured value
     * @param string $fallback colour to use when $value is not a hex colour
     * @return string
     */
    public static function clean_colour($value, string $fallback = self::DEFAULT_CHART_COLOUR): string {
        if (is_string($value)) {
            $value = trim($value);
            if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
                return strtolower($value);
            }
        }
        return $fallback;
    }
}
