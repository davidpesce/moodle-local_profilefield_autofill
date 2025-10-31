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

        // Target value.
        $mform->addElement('text', 'targetvalue', get_string('targetvalue', 'local_profilefield_autofill'), ['size' => 50]);
        $mform->setType('targetvalue', PARAM_TEXT);
        $mform->addHelpButton('targetvalue', 'targetvalue', 'local_profilefield_autofill');
        $mform->addRule('targetvalue', get_string('required'), 'required', null, 'client');

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_profilefield_autofill'));
        $mform->addHelpButton('enabled', 'enabled', 'local_profilefield_autofill');
        $mform->setDefault('enabled', 1);

        // Action buttons.
        $this->add_action_buttons(true, get_string('savechanges'));
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