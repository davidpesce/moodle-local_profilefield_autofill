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
 * CSV import form for local_profilefield_autofill plugin
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for importing profile field mappings from CSV
 */
class local_profilefield_autofill_import_form extends moodleform {
    /**
     * Define the form
     */
    protected function definition() {
        $mform = $this->_form;

        // Form header.
        $mform->addElement('header', 'importheader', get_string('importmappings', 'local_profilefield_autofill'));

        // File upload.
        $mform->addElement(
            'filepicker',
            'csvfile',
            get_string('csvfile', 'local_profilefield_autofill'),
            null,
            ['accepted_types' => ['.csv']]
        );
        $mform->addRule('csvfile', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('csvfile', 'csvfile', 'local_profilefield_autofill');

        // CSV options.
        $mform->addElement('header', 'csvoptions', get_string('csvoptions', 'local_profilefield_autofill'));

        // Delimiter.
        $delimiteroptions = [
            ',' => get_string('comma', 'local_profilefield_autofill'),
            ';' => get_string('semicolon', 'local_profilefield_autofill'),
            '\t' => get_string('tab', 'local_profilefield_autofill'),
            '|' => get_string('pipe', 'local_profilefield_autofill'),
        ];
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'local_profilefield_autofill'), $delimiteroptions);
        $mform->setDefault('delimiter', ',');
        $mform->addHelpButton('delimiter', 'delimiter', 'local_profilefield_autofill');

        // Encoding.
        $encodingoptions = [
            'UTF-8' => 'UTF-8',
            'ISO-8859-1' => 'ISO-8859-1',
            'Windows-1252' => 'Windows-1252',
        ];
        $mform->addElement('select', 'encoding', get_string('encoding', 'local_profilefield_autofill'), $encodingoptions);
        $mform->setDefault('encoding', 'UTF-8');

        // Has header row.
        $mform->addElement('advcheckbox', 'hasheader', get_string('hasheader', 'local_profilefield_autofill'));
        $mform->setDefault('hasheader', 1);
        $mform->addHelpButton('hasheader', 'hasheader', 'local_profilefield_autofill');

        // Import options.
        $mform->addElement('header', 'importoptions', get_string('importoptions', 'local_profilefield_autofill'));

        // Update existing records.
        $mform->addElement('advcheckbox', 'updateexisting', get_string('updateexisting', 'local_profilefield_autofill'));
        $mform->setDefault('updateexisting', 0);
        $mform->addHelpButton('updateexisting', 'updateexisting', 'local_profilefield_autofill');

        // Enable imported mappings.
        $mform->addElement('advcheckbox', 'enableimported', get_string('enableimported', 'local_profilefield_autofill'));
        $mform->setDefault('enableimported', 1);

        // Add format information.
        $formatinfo = get_string('csvformatinfo', 'local_profilefield_autofill');
        $mform->addElement('static', 'formatinfo', get_string('csvformat', 'local_profilefield_autofill'), $formatinfo);

        // Action buttons.
        $this->add_action_buttons(true, get_string('import', 'local_profilefield_autofill'));
    }

    /**
     * Validate form data
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Array of validation errors
     */
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        // Validate CSV file.
        if (!empty($data['csvfile'])) {
            try {
                $draftitemid = $data['csvfile'];
                $fs = get_file_storage();
                $context = context_user::instance($USER->id);

                $files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id', false);
                if (!empty($files)) {
                    $file = reset($files);
                    $filename = $file->get_filename();

                    // Check file extension.
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if ($extension !== 'csv') {
                        $errors['csvfile'] = get_string('invalidfiletype', 'local_profilefield_autofill');
                    }

                    // Check file size (max 2MB).
                    if ($file->get_filesize() > 2 * 1024 * 1024) {
                        $errors['csvfile'] = get_string('filesizetoobig', 'local_profilefield_autofill');
                    }
                }
            } catch (Exception $e) {
                // If there's an issue accessing the file, add a generic error.
                $errors['csvfile'] = 'Error validating file: ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Get the content of uploaded CSV file
     *
     * @param string $elementname Name of the file element
     * @return string File content
     */
    public function get_file_content($elementname) {
        global $USER;

        $data = $this->get_data();
        if (empty($data->$elementname)) {
            return '';
        }

        $draftitemid = $data->$elementname;
        $fs = get_file_storage();
        $context = context_user::instance($USER->id);

        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id', false);
        if (empty($files)) {
            return '';
        }

        $file = reset($files);
        return $file->get_content();
    }
}
