<?php
defined('MOODLE_INTERNAL') || die();

class block_dashboard_data_visualization extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_dashboard_data_visualization');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $config = isset($this->config) ? $this->config : null;
        $renderable = new \block_dashboard_data_visualization\output\main($config);
        $renderer = $this->page->get_renderer('block_dashboard_data_visualization');

        $this->content = new stdClass();
        $this->content->text = $renderer->render_main($renderable);
        $this->content->footer = '';

        return $this->content;
    }

    public function applicable_formats() {
        return ['my' => true, 'site-index' => false, 'course-view' => false];
    }

    public function instance_allow_config() {
        return true;
    }

    public function hide_header() {
        return true;
    }

    public function html_attributes() {
        $attributes = parent::html_attributes();
        $attributes['class'] .= ' block-dashboard-viz';
        return $attributes;
    }
}
