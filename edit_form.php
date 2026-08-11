<?php
defined('MOODLE_INTERNAL') || die();

class block_dashboard_data_visualization_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Toggle for Engagement Over Time
        $mform->addElement('advcheckbox', 'config_show_engagement_over_time', get_string('show_engagement_over_time', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_engagement_over_time', 1);

        // Toggle for Completed by Subject
        $mform->addElement('advcheckbox', 'config_show_completed_by_subject', get_string('show_completed_by_subject', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_completed_by_subject', 1);

        // Toggle for In Progress by Subject
        $mform->addElement('advcheckbox', 'config_show_in_progress_by_subject', get_string('show_in_progress_by_subject', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_in_progress_by_subject', 1);

        // Toggle for Actions Breakdown
        $mform->addElement('advcheckbox', 'config_show_actions_breakdown', get_string('show_actions_breakdown', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_actions_breakdown', 1);

        // Toggle for Study Consistency
        $mform->addElement('advcheckbox', 'config_show_study_consistency', get_string('show_study_consistency', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_study_consistency', 1);

        // Toggle for Top Subjects by Sessions
        $mform->addElement('advcheckbox', 'config_show_top_subjects', get_string('show_top_subjects', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_top_subjects', 1);

        // Toggle for Performance by Subject
        $mform->addElement('advcheckbox', 'config_show_performance_by_subject', get_string('show_performance_by_subject', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_performance_by_subject', 1);

        // --- NEW TOGGLES ---
        // KPIs
        $mform->addElement('advcheckbox', 'config_show_kpi_engagement', get_string('show_kpi_engagement', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_kpi_engagement', 1);

        $mform->addElement('advcheckbox', 'config_show_kpi_grade', get_string('show_kpi_grade', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_kpi_grade', 1);

        $mform->addElement('advcheckbox', 'config_show_kpi_completed', get_string('show_kpi_completed', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_kpi_completed', 1);

        $mform->addElement('advcheckbox', 'config_show_kpi_sessions', get_string('show_kpi_sessions', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_kpi_sessions', 1);

        $mform->addElement('advcheckbox', 'config_show_kpi_streak', get_string('show_kpi_streak', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_kpi_streak', 1);

        // Bottom Row
        $mform->addElement('advcheckbox', 'config_show_strengths', get_string('show_strengths', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_strengths', 1);

        $mform->addElement('advcheckbox', 'config_show_course_progress', get_string('show_course_progress', 'block_dashboard_data_visualization'));
        $mform->setDefault('config_show_course_progress', 1);
    }
}
