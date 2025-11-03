<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mapping form for local_profilefield_autofill plugin
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding/editing profile field mappings
 */
class local_profilefield_autofill_mapping_form extends moodleform {

    /**
     * Define the form
     */
    protected function definition() {
        $mform = $this->_form;

        // Hidden field for mapping ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Source field selection.
        $sourceoptions = \local_profilefield_autofill\helper::get_source_field_options();
        // Flatten the grouped options for now to avoid selectgroups issues
        $flatsourceoptions = ['' => get_string('choosefield', 'local_profilefield_autofill')];
        foreach ($sourceoptions as $group => $options) {
            foreach ($options as $key => $value) {
                $flatsourceoptions[$key] = $group . ': ' . $value;
            }
        }
        $mform->addElement('select', 'sourcefield', get_string('sourcefield', 'local_profilefield_autofill'), $flatsourceoptions);
        $mform->addHelpButton('sourcefield', 'sourcefield', 'local_profilefield_autofill');
        $mform->addRule('sourcefield', get_string('required'), 'required', null, 'client');

        // Source value.
        $mform->addElement('text', 'sourcevalue', get_string('sourcevalue', 'local_profilefield_autofill'), ['size' => 50]);
        $mform->setType('sourcevalue', PARAM_TEXT);
        $mform->addHelpButton('sourcevalue', 'sourcevalue', 'local_profilefield_autofill');
        $mform->addRule('sourcevalue', get_string('required'), 'required', null, 'client');

        // Add help text for source value.
        $mform->addElement('static', 'sourcevalue_help', '', get_string('help_sourcevalue', 'local_profilefield_autofill'));

        // Target field selection.
        $targetoptions = \local_profilefield_autofill\helper::get_target_field_options();
        // Flatten the grouped options for consistency with source field
        $flattargetoptions = ['' => get_string('choosefield', 'local_profilefield_autofill')];
        foreach ($targetoptions as $group => $options) {
            foreach ($options as $key => $value) {
                $flattargetoptions[$key] = $group . ': ' . $value;
            }
        }
        $mform->addElement('select', 'targetfield', get_string('targetfield', 'local_profilefield_autofill'), $flattargetoptions);
        $mform->addHelpButton('targetfield', 'targetfield', 'local_profilefield_autofill');
        $mform->addRule('targetfield', get_string('required'), 'required', null, 'client');

        // Target value - will be replaced dynamically based on field type
        $mform->addElement('text', 'targetvalue', get_string('targetvalue', 'local_profilefield_autofill'), ['size' => 50]);
        $mform->setType('targetvalue', PARAM_TEXT);
        $mform->addHelpButton('targetvalue', 'targetvalue', 'local_profilefield_autofill');
        $mform->addRule('targetvalue', get_string('required'), 'required', null, 'client');
        
        // Add JavaScript for dynamic field updates using AMD
        $this->add_field_type_javascript();
        
        // Get current values for editing
        $currenttargetvalue = '';
        $currenttargetfield = '';
        
        // Try to get from optional params (when editing)
        global $DB;
        $id = optional_param('id', 0, PARAM_INT);
        if ($id > 0) {
            $mapping = $DB->get_record('local_profilefield_mapping', ['id' => $id]);
            if ($mapping) {
                $currenttargetvalue = $mapping->targetvalue;
                $currenttargetfield = $mapping->targetfield;
            }
        }

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_profilefield_autofill'));
        $mform->addHelpButton('enabled', 'enabled', 'local_profilefield_autofill');
        $mform->setDefault('enabled', 1);

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Add JavaScript for dynamic field type handling using AMD
     */
    protected function add_field_type_javascript() {
        global $PAGE, $DB;
        
        // Get field type information for JavaScript
        $fieldtypes = $this->get_field_type_data();
        
        // Get current values for editing
        $currenttargetvalue = '';
        $currenttargetfield = '';
        
        // Try to get from optional params (when editing)
        $id = optional_param('id', 0, PARAM_INT);
        if ($id > 0) {
            $mapping = $DB->get_record('local_profilefield_autofill_mapping', ['id' => $id]);
            if ($mapping) {
                $currenttargetvalue = $mapping->targetvalue;
                $currenttargetfield = $mapping->targetfield;
            }
        }
        
        // Call AMD module with parameters
        $PAGE->requires->js_call_amd('local_profilefield_autofill/form_handler', 'init', [
            $fieldtypes,
            $currenttargetfield,
            $currenttargetvalue
        ]);
    }


    /**
     * Get field type data for JavaScript
     */
    protected function get_field_type_data() {
        global $DB;
        
        $fieldtypes = [];
        
        // Get custom profile field types
        $customfields = $DB->get_records('user_info_field');
        foreach ($customfields as $field) {
            $fieldkey = 'profile_field_' . $field->shortname;
            $fieldtypes[$fieldkey] = [
                'type' => $field->datatype,
                'description' => $field->description,
                'options' => $field->param1 // For menu fields, param1 contains options
            ];
        }
        
        // Add standard fields with their types
        $standardfields = [
            'city' => ['type' => 'text', 'description' => 'User city'],
            'country' => ['type' => 'text', 'description' => 'User country code'],
            'institution' => ['type' => 'text', 'description' => 'User institution'],
            'department' => ['type' => 'text', 'description' => 'User department'],
            'phone1' => ['type' => 'text', 'description' => 'Primary phone number'],
            'phone2' => ['type' => 'text', 'description' => 'Secondary phone number'],
            'address' => ['type' => 'text', 'description' => 'User address'],
            'description' => ['type' => 'textarea', 'description' => 'User description']
        ];
        
        $fieldtypes = array_merge($fieldtypes, $standardfields);
        
        return $fieldtypes;
    }

    /**
     * Validate form data
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Array of validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Use helper to validate the mapping data.
        $validationerrors = \local_profilefield_autofill\helper::validate_mapping_data($data);
        $errors = array_merge($errors, $validationerrors);

        return $errors;
    }
}