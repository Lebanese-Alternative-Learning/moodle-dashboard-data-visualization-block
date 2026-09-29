<?php
defined('MOODLE_INTERNAL') || die();

use block_dashboard_data_visualization\layout;

class block_dashboard_data_visualization_edit_form extends block_edit_form {

    /** @var string Component name used for every string lookup in this form. */
    const PLUGIN = 'block_dashboard_data_visualization';

    protected function specific_definition($mform) {
        // --- KPI CARDS ---
        $mform->addElement('header', 'ddvkpiheader', get_string('kpi_cards', self::PLUGIN));
        $mform->setExpanded('ddvkpiheader', true);

        foreach (layout::kpis() as $key => $order) {
            $this->add_section_controls($mform, $key, $order, null);
        }

        // --- CHARTS & PANELS ---
        $mform->addElement('header', 'ddvpanelheader', get_string('panel_cards', self::PLUGIN));
        $mform->setExpanded('ddvpanelheader', true);
        $mform->addElement('static', 'ddvpanelhelp', '', get_string('layout_help', self::PLUGIN));

        foreach (layout::panels() as $key => $def) {
            $this->add_section_controls($mform, $key, $def['order'], $def['width']);
        }

        // --- COLORS ---
        $mform->addElement('header', 'ddvcolourheader', get_string('colors', self::PLUGIN));
        $mform->setExpanded('ddvcolourheader', true);

        $mform->addElement('text', 'config_chart_color', get_string('chart_color', self::PLUGIN));
        $mform->setType('config_chart_color', PARAM_TEXT);
        $mform->setDefault('config_chart_color', layout::DEFAULT_CHART_COLOUR);
        // MoodleQuickForm_text forces type="text" from its constructor, so any type passed in the
        // attributes array is discarded. Switching it afterwards is what gives a native picker.
        $mform->updateElementAttr('config_chart_color', [
            'type' => 'color',
            'class' => 'ddv-colour-input',
        ]);
    }

    /**
     * Add the "visible / order / width" controls for one dashboard section.
     *
     * The elements are grouped so the form stays readable, but the group does not append its own
     * name, which keeps the submitted keys flat (config_show_x, config_order_x, config_width_x) so
     * blocklib still strips the config_ prefix and stores them as instance config.
     *
     * @param MoodleQuickForm $mform
     * @param string $key section key, e.g. 'kpi_streak' or 'top_subjects'
     * @param int $defaultorder
     * @param int|null $defaultwidth null for KPI cards, which are not individually sized
     */
    private function add_section_controls($mform, string $key, int $defaultorder, ?int $defaultwidth) {
        $label = get_string('show_' . $key, self::PLUGIN);

        $group = [];

        $group[] = $mform->createElement(
            'advcheckbox',
            'config_show_' . $key,
            '',
            get_string('section_visible', self::PLUGIN),
            ['aria-label' => get_string('visible_for', self::PLUGIN, $label)]
        );

        $orderel = $mform->createElement('text', 'config_order_' . $key, '', [
            'size' => 3,
            'title' => get_string('section_order', self::PLUGIN),
            'aria-label' => get_string('order_for', self::PLUGIN, $label),
        ]);
        // Same constructor caveat as the colour field above.
        $orderel->updateAttributes([
            'type' => 'number',
            'min' => 0,
            'step' => 1,
            'class' => 'ddv-order-input',
        ]);
        $group[] = $orderel;

        if ($defaultwidth !== null) {
            $group[] = $mform->createElement(
                'select',
                'config_width_' . $key,
                '',
                layout::width_options(),
                ['aria-label' => get_string('width_for', self::PLUGIN, $label)]
            );
        }

        $mform->addGroup($group, 'ddvgrp_' . $key, $label, ' ', false);

        $mform->setDefault('config_show_' . $key, 1);
        $mform->setDefault('config_order_' . $key, $defaultorder);
        $mform->setType('config_order_' . $key, PARAM_INT);

        if ($defaultwidth !== null) {
            $mform->setDefault('config_width_' . $key, $defaultwidth);
            $mform->setType('config_width_' . $key, PARAM_INT);
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $colour = isset($data['config_chart_color']) ? trim((string)$data['config_chart_color']) : '';
        if ($colour !== '' && !preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $colour)) {
            $errors['config_chart_color'] = get_string('invalid_colour', self::PLUGIN);
        }

        return $errors;
    }
}
