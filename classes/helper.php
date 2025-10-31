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
 * Helper class for local_profilefield_autofill plugin
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill;

/**
 * Helper class for profile field auto-fill functionality
 */
class helper {

    /**
     * Get all available source fields (standard user fields + custom profile fields)
     *
     * @return array Array of field options for select elements
     */
    public static function get_source_field_options() {
        $options = [];
        
        // Standard user fields.
        $standardfields = self::get_standard_user_fields();
        if (!empty($standardfields)) {
            $options[get_string('standardfields', 'local_profilefield_autofill')] = $standardfields;
        }

        // Custom profile fields.
        $customfields = self::get_custom_profile_field_options();
        if (!empty($customfields)) {
            $options[get_string('customfields', 'local_profilefield_autofill')] = $customfields;
        }

        return $options;
    }

    /**
     * Get target field options (both standard user fields and custom profile fields)
     *
     * @return array Array of target field options
     */
    public static function get_target_field_options() {
        $options = [];
        
        // Standard user fields that can be safely updated
        $standardfields = self::get_updatable_standard_fields();
        if (!empty($standardfields)) {
            $options[get_string('standardfields', 'local_profilefield_autofill')] = $standardfields;
        }

        // Custom profile fields
        $customfields = self::get_custom_profile_field_options_for_target();
        if (!empty($customfields)) {
            $options[get_string('customfields', 'local_profilefield_autofill')] = $customfields;
        }

        return $options;
    }

    /**
     * Get standard user fields that can be safely updated as targets
     *
     * @return array Array of updatable standard field options
     */
    private static function get_updatable_standard_fields() {
        return [
            'city' => get_string('field_city', 'local_profilefield_autofill'),
            'country' => get_string('field_country', 'local_profilefield_autofill'),
            'institution' => get_string('field_institution', 'local_profilefield_autofill'),
            'department' => get_string('field_department', 'local_profilefield_autofill'),
            'phone1' => get_string('field_phone1', 'local_profilefield_autofill'),
            'phone2' => get_string('field_phone2', 'local_profilefield_autofill'),
            'address' => get_string('field_address', 'local_profilefield_autofill'),
            'description' => get_string('field_description', 'local_profilefield_autofill'),
        ];
    }

    /**
     * Get custom profile field options for target selection
     *
     * @return array Array of custom profile field options with profile_field_ prefix
     */
    private static function get_custom_profile_field_options_for_target() {
        global $DB;

        $options = [];
        $fields = $DB->get_records('user_info_field', null, 'name ASC');

        foreach ($fields as $field) {
            $key = 'profile_field_' . $field->shortname;
            $options[$key] = format_string($field->name);
        }

        return $options;
    }

    /**
     * Get standard user fields that can be used as source fields
     *
     * @return array Array of standard field options
     */
    private static function get_standard_user_fields() {
        return [
            'username' => get_string('field_username', 'local_profilefield_autofill'),
            'email' => get_string('field_email', 'local_profilefield_autofill'),
            'firstname' => get_string('field_firstname', 'local_profilefield_autofill'),
            'lastname' => get_string('field_lastname', 'local_profilefield_autofill'),
            'city' => get_string('field_city', 'local_profilefield_autofill'),
            'country' => get_string('field_country', 'local_profilefield_autofill'),
            'institution' => get_string('field_institution', 'local_profilefield_autofill'),
            'department' => get_string('field_department', 'local_profilefield_autofill'),
            'phone1' => get_string('field_phone1', 'local_profilefield_autofill'),
            'phone2' => get_string('field_phone2', 'local_profilefield_autofill'),
            'address' => get_string('field_address', 'local_profilefield_autofill'),
        ];
    }

    /**
     * Get custom profile field options
     *
     * @return array Array of custom profile field options
     */
    private static function get_custom_profile_field_options() {
        global $DB;

        $options = [];
        $fields = $DB->get_records('user_info_field', null, 'name ASC');

        foreach ($fields as $field) {
            $key = 'profile_field_' . $field->shortname;
            $options[$key] = format_string($field->name);
        }

        return $options;
    }



    /**
     * Validate field mapping data
     *
     * @param array $data Mapping data to validate
     * @return array Array of validation errors (empty if valid)
     */
    public static function validate_mapping_data($data) {
        $errors = [];

        // Check required fields.
        if (empty($data['sourcefield'])) {
            $errors['sourcefield'] = get_string('required');
        }

        if (empty($data['sourcevalue'])) {
            $errors['sourcevalue'] = get_string('required');
        }

        if (empty($data['targetfield'])) {
            $errors['targetfield'] = get_string('required');
        }

        if (empty($data['targetvalue'])) {
            $errors['targetvalue'] = get_string('required');
        }

        // Validate source field exists.
        if (!empty($data['sourcefield'])) {
            if (!self::validate_source_field($data['sourcefield'])) {
                $errors['sourcefield'] = get_string('fieldnotfound', 'local_profilefield_autofill');
            }
        }

        // Validate target field exists.
        if (!empty($data['targetfield'])) {
            if (!self::validate_target_field($data['targetfield'])) {
                $errors['targetfield'] = get_string('fieldnotfound', 'local_profilefield_autofill');
            }
        }

        return $errors;
    }

    /**
     * Validate that a source field exists
     *
     * @param string $fieldname The field name to validate
     * @return bool True if field exists
     */
    private static function validate_source_field($fieldname) {
        // Check standard fields.
        $standardfields = array_keys(self::get_standard_user_fields());
        if (in_array($fieldname, $standardfields)) {
            return true;
        }

        // Check custom profile fields.
        if (strpos($fieldname, 'profile_field_') === 0) {
            $shortname = substr($fieldname, strlen('profile_field_'));
            return self::custom_field_exists($shortname);
        }

        return false;
    }

    /**
     * Validate that a target field exists
     *
     * @param string $fieldname The field name to validate (can be standard field or profile_field_xxx)
     * @return bool True if field exists
     */
    private static function validate_target_field($fieldname) {
        // Check if it's a custom profile field
        if (strpos($fieldname, 'profile_field_') === 0) {
            $shortname = substr($fieldname, strlen('profile_field_'));
            return self::custom_field_exists($shortname);
        }
        
        // Check if it's a valid standard field that can be updated
        $updatablefields = array_keys(self::get_updatable_standard_fields());
        return in_array($fieldname, $updatablefields);
    }

    /**
     * Check if a custom profile field exists
     *
     * @param string $shortname The field shortname
     * @return bool True if field exists
     */
    private static function custom_field_exists($shortname) {
        global $DB;
        return $DB->record_exists('user_info_field', ['shortname' => $shortname]);
    }

    /**
     * Format field name for display
     *
     * @param string $fieldname The field name
     * @return string Formatted field name
     */
    public static function format_field_name($fieldname) {
        // Check if it's a custom profile field.
        if (strpos($fieldname, 'profile_field_') === 0) {
            $shortname = substr($fieldname, strlen('profile_field_'));
            $field = self::get_custom_field_by_shortname($shortname);
            return $field ? format_string($field->name) : $fieldname;
        }

        // Check standard fields (both source and target)
        $standardfields = array_merge(
            self::get_standard_user_fields(),
            self::get_updatable_standard_fields()
        );
        return isset($standardfields[$fieldname]) ? $standardfields[$fieldname] : $fieldname;
    }

    /**
     * Get custom field by shortname
     *
     * @param string $shortname The field shortname
     * @return \stdClass|null Field object or null if not found
     */
    private static function get_custom_field_by_shortname($shortname) {
        global $DB;
        return $DB->get_record('user_info_field', ['shortname' => $shortname]);
    }

    /**
     * Get mapping by ID
     *
     * @param int $id Mapping ID
     * @return \stdClass|null Mapping object or null if not found
     */
    public static function get_mapping($id) {
        global $DB;
        return $DB->get_record('local_profilefield_mapping', ['id' => $id]);
    }

    /**
     * Save or update a mapping
     *
     * @param \stdClass $data Mapping data
     * @return int The mapping ID
     */
    public static function save_mapping($data) {
        global $DB;

        $now = time();
        
        if (empty($data->id)) {
            // Create new mapping.
            $data->timecreated = $now;
            $data->timemodified = $now;
            return $DB->insert_record('local_profilefield_mapping', $data);
        } else {
            // Update existing mapping.
            $data->timemodified = $now;
            $DB->update_record('local_profilefield_mapping', $data);
            return $data->id;
        }
    }

    /**
     * Delete a mapping
     *
     * @param int $id Mapping ID
     * @return bool True if successful
     */
    public static function delete_mapping($id) {
        global $DB;
        return $DB->delete_records('local_profilefield_mapping', ['id' => $id]);
    }

    /**
     * Get all mappings
     *
     * @return array Array of mapping objects
     */
    public static function get_all_mappings() {
        global $DB;
        
        return $DB->get_records('local_profilefield_mapping', null, 'timecreated ASC');
    }

    /**
     * Toggle mapping enabled status
     *
     * @param int $id Mapping ID
     * @return bool True if successful
     */
    public static function toggle_mapping_status($id) {
        global $DB;
        
        $mapping = $DB->get_record('local_profilefield_mapping', ['id' => $id]);
        if (!$mapping) {
            return false;
        }

        $newstatus = $mapping->enabled ? 0 : 1;
        return $DB->set_field('local_profilefield_mapping', 'enabled', $newstatus, ['id' => $id]);
    }
}