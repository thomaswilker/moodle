<?php


/**
 * Specialised backup task for the invisible format.
 * Used to include the co branding img in the backup process.
 *
 */
class backup_format_invisible_plugin extends backup_format_plugin {


    protected function define_course_plugin_structure() {
        // Define virtual plugin element, ensures only operates on invisible format.
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'invisible');

        // Create plugin container element with standard name.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Add wrapper to plugin.
        $plugin->add_child($pluginwrapper);

        // Set up (format) plugin's own structure and add to wrapper.
        $invisible = new backup_nested_element('invisible');
        $pluginwrapper->add_child($invisible);

        // We don't have additional custom tables for this format, so nothing for this step.

        // Include files which have format_invisible and area image and no itemid.
        $invisible->annotate_files('format_invisible', 'cobrandinglogo', null);

        return $plugin;
    }
}
