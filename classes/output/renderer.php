<?php
namespace block_dashboard_data_visualization\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_main(main $main): string {
        $data = $main->export_for_template($this);
        return $this->render_from_template('block_dashboard_data_visualization/main', $data);
    }
}
