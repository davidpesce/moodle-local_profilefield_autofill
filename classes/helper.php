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
        return $DB->get_record('local_profilefield_autofill_mapping', ['id' => $id]);
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
            return $DB->insert_record('local_profilefield_autofill_mapping', $data);
        } else {
            // Update existing mapping.
            $data->timemodified = $now;
            $DB->update_record('local_profilefield_autofill_mapping', $data);
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
        return $DB->delete_records('local_profilefield_autofill_mapping', ['id' => $id]);
    }

    /**
     * Get all mappings
     *
     * @return array Array of mapping objects
     */
    public static function get_all_mappings() {
        global $DB;
        
        return $DB->get_records('local_profilefield_autofill_mapping', null, 'timecreated ASC');
    }

    /**
     * Toggle mapping enabled status
     *
     * @param int $id Mapping ID
     * @return bool True if successful
     */
    public static function toggle_mapping_status($id) {
        global $DB;
        
        $mapping = $DB->get_record('local_profilefield_autofill_mapping', ['id' => $id]);
        if (!$mapping) {
            return false;
        }

        $newstatus = $mapping->enabled ? 0 : 1;
        return $DB->set_field('local_profilefield_autofill_mapping', 'enabled', $newstatus, ['id' => $id]);
    }

    /**
     * Group mappings by target field and value
     *
     * @param array $mappings Array of mapping objects
     * @return array Grouped mappings
     */
    public static function group_mappings_by_target($mappings) {
        $groups = [];
        
        foreach ($mappings as $mapping) {
            $key = $mapping->targetfield . '::' . $mapping->targetvalue;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'targetfield' => $mapping->targetfield,
                    'targetvalue' => $mapping->targetvalue,
                    'mappings' => []
                ];
            }
            $groups[$key]['mappings'][] = $mapping;
        }
        
        // Sort groups alphabetically by target field name, then by target value
        uasort($groups, function($a, $b) {
            // First sort by formatted field name
            $fieldA = self::format_field_name($a['targetfield']);
            $fieldB = self::format_field_name($b['targetfield']);
            
            $fieldComparison = strcasecmp($fieldA, $fieldB);
            if ($fieldComparison !== 0) {
                return $fieldComparison;
            }
            
            // If field names are the same, sort by target value
            return strcasecmp($a['targetvalue'], $b['targetvalue']);
        });
        
        // Sort mappings within each group alphabetically by source field, then source value
        foreach ($groups as &$group) {
            usort($group['mappings'], function($a, $b) {
                // First sort by formatted source field name
                $fieldA = self::format_field_name($a->sourcefield);
                $fieldB = self::format_field_name($b->sourcefield);
                
                $fieldComparison = strcasecmp($fieldA, $fieldB);
                if ($fieldComparison !== 0) {
                    return $fieldComparison;
                }
                
                // If source field names are the same, sort by source value
                return strcasecmp($a->sourcevalue, $b->sourcevalue);
            });
        }
        
        return $groups;
    }

    /**
     * Display mappings in grouped format
     *
     * @param array $groups Grouped mappings
     * @param moodle_url $pageurl Current page URL
     */
    public static function display_grouped_mappings($groups, $pageurl) {
        global $OUTPUT;
        
        $groupindex = 0;
        foreach ($groups as $group) {
            $targetname = self::format_field_name($group['targetfield']);
            $count = count($group['mappings']);
            $groupid = 'mapping-group-' . $groupindex;
            
            // Group header with collapse functionality and bulk actions
            echo \html_writer::start_div('card mb-3');
            echo \html_writer::start_div('card-header bg-light d-flex justify-content-between align-items-center');
            
            // Left side - clickable header with collapse icon
            $enabledcount = count(array_filter($group['mappings'], function($m) { return $m->enabled; }));
            $headertext = $targetname . ' → "' . format_string($group['targetvalue']) . '" (' . $enabledcount . '/' . $count . ' enabled)';
            
            echo \html_writer::start_div('flex-grow-1');
            echo \html_writer::start_tag('h6', ['class' => 'mb-0']);
            echo \html_writer::start_tag('button', [
                'class' => 'btn btn-link text-left p-0 text-decoration-none collapsed',
                'type' => 'button',
                'data-toggle' => 'collapse',
                'data-target' => '#' . $groupid,
                'aria-expanded' => 'false',
                'aria-controls' => $groupid,
                'style' => 'color: inherit; font-weight: inherit;'
            ]);
            echo \html_writer::tag('i', '', ['class' => 'fa fa-chevron-down mr-2', 'aria-hidden' => 'true']);
            echo $headertext;
            echo \html_writer::end_tag('button');
            echo \html_writer::end_tag('h6');
            echo \html_writer::end_div();
            
            // Right side - bulk action buttons
            echo \html_writer::start_div('btn-group btn-group-sm');
            
            // Enable all button (only if some are disabled)
            if ($enabledcount < $count) {
                $enableurl = new \moodle_url($pageurl, [
                    'action' => 'bulkenable',
                    'targetfield' => $group['targetfield'],
                    'targetvalue' => $group['targetvalue'],
                    'grouped' => 1,
                    'sesskey' => sesskey()
                ]);
                echo \html_writer::link($enableurl, 
                    get_string('enableall', 'local_profilefield_autofill'), 
                    ['class' => 'btn btn-success btn-sm']);
            }
            
            // Disable all button (only if some are enabled)
            if ($enabledcount > 0) {
                $disableurl = new \moodle_url($pageurl, [
                    'action' => 'bulkdisable',
                    'targetfield' => $group['targetfield'],
                    'targetvalue' => $group['targetvalue'],
                    'grouped' => 1,
                    'sesskey' => sesskey()
                ]);
                echo \html_writer::link($disableurl, 
                    get_string('disableall', 'local_profilefield_autofill'), 
                    ['class' => 'btn btn-warning btn-sm']);
            }
            
            // Delete all button
            $deleteurl = new \moodle_url($pageurl, [
                'action' => 'bulkdelete',
                'targetfield' => $group['targetfield'],
                'targetvalue' => $group['targetvalue'],
                'grouped' => 1,
                'sesskey' => sesskey()
            ]);
            $deleteconfirm = get_string('confirmbulkdelete', 'local_profilefield_autofill');
            echo \html_writer::link($deleteurl, 
                get_string('deleteall', 'local_profilefield_autofill'), 
                ['class' => 'btn btn-danger btn-sm', 
                 'onclick' => "return confirm('$deleteconfirm');"]);
            
            echo \html_writer::end_div(); // btn-group
            echo \html_writer::end_div(); // card-header
            
            // Collapsible body
            echo \html_writer::start_div('collapse', ['id' => $groupid]);
            echo \html_writer::start_div('card-body p-2');
            
            // Display mappings in this group
            foreach ($group['mappings'] as $mapping) {
                $sourcename = self::format_field_name($mapping->sourcefield);
                
                echo \html_writer::start_div('d-flex justify-content-between align-items-center border-bottom py-2');
                
                // Left side - source info
                echo \html_writer::start_div();
                echo \html_writer::tag('strong', $sourcename, ['class' => 'mr-2']);
                echo \html_writer::tag('code', format_string($mapping->sourcevalue));
                echo \html_writer::end_div();
                
                // Right side - status and actions
                echo \html_writer::start_div('d-flex align-items-center');
                
                // Status badge
                $statusclass = $mapping->enabled ? 'badge-success' : 'badge-secondary';
                $statustext = $mapping->enabled ? 
                    get_string('enabledstatus', 'local_profilefield_autofill') : 
                    get_string('disabledstatus', 'local_profilefield_autofill');
                echo \html_writer::span($statustext, 'badge ' . $statusclass . ' mr-2');
                
                // Action icons - native Moodle style (plain icons)
                // Preserve current view state in action URLs
                $currenturl = new \moodle_url($pageurl, ['grouped' => 1]);
                
                $editurl = new \moodle_url($pageurl, ['action' => 'edit', 'id' => $mapping->id, 'returnurl' => $currenturl->out(false)]);
                echo \html_writer::link($editurl, 
                    \html_writer::tag('i', '', ['class' => 'fa fa-cog', 'aria-hidden' => 'true']), 
                    ['class' => 'action-icon mr-2', 'title' => get_string('edit', 'local_profilefield_autofill')]);
                
                $toggletext = $mapping->enabled ? get_string('disable', 'local_profilefield_autofill') : get_string('enable', 'local_profilefield_autofill');
                $toggleicon = $mapping->enabled ? 'fa-eye-slash' : 'fa-eye';
                $toggleurl = new \moodle_url($pageurl, ['action' => 'toggle', 'id' => $mapping->id, 'grouped' => 1, 'sesskey' => sesskey()]);
                echo \html_writer::link($toggleurl, 
                    \html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon, 'aria-hidden' => 'true']), 
                    ['class' => 'action-icon mr-2', 'title' => $toggletext]);
                
                $deleteurl = new \moodle_url($pageurl, ['action' => 'delete', 'id' => $mapping->id, 'grouped' => 1, 'sesskey' => sesskey()]);
                echo \html_writer::link($deleteurl, 
                    \html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']), 
                    ['class' => 'action-icon', 
                     'title' => get_string('delete', 'local_profilefield_autofill'),
                     'onclick' => 'return confirm("' . get_string('confirmdeletemapping', 'local_profilefield_autofill') . '");']);
                
                echo \html_writer::end_div();
                echo \html_writer::end_div();
            }
            
            echo \html_writer::end_div(); // card-body
            echo \html_writer::end_div(); // collapse
            echo \html_writer::end_div(); // card
            
            $groupindex++;
        }
    }

    /**
     * Bulk update status for mappings with same target field and value
     *
     * @param string $targetfield Target field name
     * @param string $targetvalue Target value
     * @param int $enabled New enabled status (0 or 1)
     * @return bool True if successful
     */
    public static function bulk_update_status($targetfield, $targetvalue, $enabled) {
        global $DB;
        
        $params = [
            'targetfield' => $targetfield,
            'targetvalue' => $targetvalue
        ];
        
        // Build SQL with proper text comparison
        $sql_targetfield = $DB->sql_compare_text('targetfield') . ' = ' . $DB->sql_compare_text(':targetfield');
        $sql_targetvalue = $DB->sql_compare_text('targetvalue') . ' = ' . $DB->sql_compare_text(':targetvalue');
        $where_clause = $sql_targetfield . ' AND ' . $sql_targetvalue;
        
        // First check if any records exist
        $count = $DB->count_records_select('local_profilefield_autofill_mapping', $where_clause, $params);
        
        if ($count == 0) {
            return false; // No records to update
        }
        
        // Update the records
        $result = $DB->set_field_select('local_profilefield_autofill_mapping', 'enabled', $enabled, $where_clause, $params);
            
        return $result !== false;
    }

    /**
     * Bulk delete mappings with same target field and value
     *
     * @param string $targetfield Target field name
     * @param string $targetvalue Target value
     * @return bool True if successful
     */
    public static function bulk_delete($targetfield, $targetvalue) {
        global $DB;
        
        $params = [
            'targetfield' => $targetfield,
            'targetvalue' => $targetvalue
        ];
        
        // Build SQL with proper text comparison
        $sql_targetfield = $DB->sql_compare_text('targetfield') . ' = ' . $DB->sql_compare_text(':targetfield');
        $sql_targetvalue = $DB->sql_compare_text('targetvalue') . ' = ' . $DB->sql_compare_text(':targetvalue');
        $where_clause = $sql_targetfield . ' AND ' . $sql_targetvalue;
        
        // First check if any records exist
        $count = $DB->count_records_select('local_profilefield_autofill_mapping', $where_clause, $params);
        
        if ($count == 0) {
            return false; // No records to delete
        }
        
        // Delete the records using delete_records_select for text field comparisons
        return $DB->delete_records_select('local_profilefield_autofill_mapping', $where_clause, $params);
    }

    /**
     * Import mappings from CSV data
     *
     * @param array $csvdata Array of CSV rows
     * @param array $options Import options (updateexisting, enableimported)
     * @return array Import results with counts and errors
     */
    public static function import_mappings_from_csv($csvdata, $options = []) {
        global $DB;
        
        $results = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'skipped_items' => []
        ];
        
        $updateexisting = !empty($options['updateexisting']);
        $enableimported = !empty($options['enableimported']);
        
        foreach ($csvdata as $rownum => $row) {
            $results['total']++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                $results['skipped']++;
                $results['skipped_items'][] = get_string('csvskippedrow', 'local_profilefield_autofill', [
                    'row' => $rownum + 1,
                    'reason' => get_string('csvemptyrow', 'local_profilefield_autofill')
                ]);
                continue;
            }
            
            // Validate required fields
            $validation = self::validate_csv_row($row, $rownum);
            if (!empty($validation)) {
                foreach ($validation as $error) {
                    // Don't add row number if error already contains it
                    if (strpos($error, 'Row ') === 0) {
                        $results['skipped_items'][] = $error;
                    } else {
                        $results['skipped_items'][] = get_string('csvskippedrow', 'local_profilefield_autofill', [
                            'row' => $rownum + 1,
                            'reason' => $error
                        ]);
                    }
                }
                $results['skipped']++;
                continue;
            }
            
            // Normalize field names - detect and convert core fields to proper format
            $sourcefield = self::normalize_field_name(trim($row[0]));
            $targetfield = self::normalize_field_name(trim($row[2]));
            
            $mapping = (object)[
                'sourcefield' => $sourcefield,
                'sourcevalue' => trim($row[1]),
                'targetfield' => $targetfield,
                'targetvalue' => trim($row[3]),
                'enabled' => $enableimported ? 1 : 0,
                'timecreated' => time(),
                'timemodified' => time()
            ];
            
            // Check if mapping already exists (using sql_compare_text for text fields)
            $sql = "SELECT * FROM {local_profilefield_autofill_mapping} 
                    WHERE sourcefield = :sourcefield 
                    AND " . $DB->sql_compare_text('sourcevalue') . " = " . $DB->sql_compare_text(':sourcevalue') . "
                    AND targetfield = :targetfield";
            
            $params = [
                'sourcefield' => $mapping->sourcefield,
                'sourcevalue' => $mapping->sourcevalue,
                'targetfield' => $mapping->targetfield
            ];
            
            $existing = $DB->get_record_sql($sql, $params);
            
            if ($existing) {
                if ($updateexisting) {
                    $mapping->id = $existing->id;
                    $mapping->timecreated = $existing->timecreated;
                    
                    if ($DB->update_record('local_profilefield_autofill_mapping', $mapping)) {
                        $results['updated']++;
                    } else {
                        $results['skipped_items'][] = get_string('csvskippedrow', 'local_profilefield_autofill', [
                            'row' => $rownum + 1,
                            'reason' => get_string('errorupdatingrow', 'local_profilefield_autofill', $rownum + 1)
                        ]);
                        $results['skipped']++;
                    }
                } else {
                    $results['skipped_items'][] = get_string('csvskippedrow', 'local_profilefield_autofill', [
                        'row' => $rownum + 1,
                        'reason' => get_string('csvmappingexists', 'local_profilefield_autofill')
                    ]);
                    $results['skipped']++;
                }
            } else {
                if ($DB->insert_record('local_profilefield_autofill_mapping', $mapping)) {
                    $results['imported']++;
                } else {
                    $results['skipped_items'][] = get_string('csvskippedrow', 'local_profilefield_autofill', [
                        'row' => $rownum + 1,
                        'reason' => get_string('errorimportingrow', 'local_profilefield_autofill', $rownum + 1)
                    ]);
                    $results['skipped']++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Validate a CSV row
     *
     * @param array $row CSV row data
     * @param int $rownum Row number for error reporting
     * @return array Array of validation errors
     */
    private static function validate_csv_row($row, $rownum) {
        $errors = [];
        $rownum++; // Convert to 1-based for user display
        
        // Check if we have enough columns
        if (count($row) < 4) {
            $errors[] = get_string('csvrowtooshort', 'local_profilefield_autofill', $rownum);
            return $errors;
        }
        
        // Validate required fields
        if (empty(trim($row[0]))) {
            $errors[] = get_string('csvsourcefieldmissing', 'local_profilefield_autofill', $rownum);
        }
        if (empty(trim($row[1]))) {
            $errors[] = get_string('csvsourcevaluemissing', 'local_profilefield_autofill', $rownum);
        }
        if (empty(trim($row[2]))) {
            $errors[] = get_string('csvtargetfieldmissing', 'local_profilefield_autofill', $rownum);
        }
        if (empty(trim($row[3]))) {
            $errors[] = get_string('csvtargetvaluemissing', 'local_profilefield_autofill', $rownum);
        }
        
        // Validate field names exist (skip if database not accessible)
        try {
            $sourceoptions = self::get_source_field_options();
            $targetoptions = self::get_target_field_options();
            
            $sourcefield = trim($row[0]);
            $targetfield = trim($row[2]);
            
            $sourcevalid = false;
            foreach ($sourceoptions as $group) {
                if (array_key_exists($sourcefield, $group)) {
                    $sourcevalid = true;
                    break;
                }
            }
            
            $targetvalid = false;
            foreach ($targetoptions as $group) {
                if (array_key_exists($targetfield, $group)) {
                    $targetvalid = true;
                    break;
                }
            }
            
            if (!$sourcevalid) {
                $errors[] = get_string('csvinvalidsourcefield', 'local_profilefield_autofill', [
                    'row' => $rownum, 
                    'field' => $sourcefield
                ]);
            }
            
            if (!$targetvalid) {
                $errors[] = get_string('csvinvalidtargetfield', 'local_profilefield_autofill', [
                    'row' => $rownum, 
                    'field' => $targetfield
                ]);
            }
            
            // Additional validation for dropdown/menu fields
            if ($targetvalid && strpos($targetfield, 'profile_field_') === 0) {
                $fieldname = str_replace('profile_field_', '', $targetfield);
                $targetvalue = trim($row[3]);
                
                $validationresult = self::validate_dropdown_value($fieldname, $targetvalue);
                if (!$validationresult['valid']) {
                    $errors[] = get_string('csvinvalidtargetvalue', 'local_profilefield_autofill', [
                        'row' => $rownum,
                        'field' => $targetfield,
                        'value' => $targetvalue,
                        'suggestion' => $validationresult['suggestion']
                    ]);
                }
            }
        } catch (Exception $e) {
            // Skip field validation if database is not accessible
            // This allows import to proceed with basic validation only
        }
        
        return $errors;
    }
    
    /**
     * Parse CSV file content
     *
     * @param string $content CSV file content
     * @param string $delimiter CSV delimiter
     * @param string $encoding File encoding
     * @param bool $hasheader Whether first row is header
     * @return array Parsed CSV data
     */
    public static function parse_csv_content($content, $delimiter = ',', $encoding = 'UTF-8', $hasheader = true) {
        // Convert encoding if needed
        if (strtoupper($encoding) !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Handle different delimiters
        if ($delimiter === '\t') {
            $delimiter = "\t";
        }
        
        // Parse CSV
        $lines = str_getcsv($content, "\n");
        $data = [];
        
        foreach ($lines as $line) {
            if (trim($line)) {
                $data[] = str_getcsv($line, $delimiter);
            }
        }
        
        // Remove header if present
        if ($hasheader && !empty($data)) {
            array_shift($data);
        }
        
        return $data;
    }
    
    /**
     * Generate CSV template content
     *
     * @return string CSV template with headers and example data
     */
    public static function generate_csv_template() {
        $headers = [
            'sourcefield',
            'sourcevalue', 
            'targetfield',
            'targetvalue'
        ];
        
        $examples = [
            ['email', '*@university.edu', 'profile_field_institution', 'University Name'],
            ['city', 'Boston', 'profile_field_region', 'Northeast'],
            ['profile_field_department', 'IT', 'profile_field_autofill', 'Technology']
        ];
        
        $csv = implode(',', $headers) . "\n";
        
        foreach ($examples as $example) {
            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $example)) . "\n";
        }
        
        return $csv;
    }
    
    /**
     * Validate if a value is valid for a dropdown/menu profile field
     *
     * @param string $fieldname Profile field shortname (without profile_field_ prefix)
     * @param string $value Value to validate
     * @return array Array with 'valid' boolean and 'suggestion' string
     */
    private static function validate_dropdown_value($fieldname, $value) {
        global $DB;
        
        try {
            // Get the profile field definition
            $field = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
            if (!$field) {
                return ['valid' => true, 'suggestion' => '']; // Field doesn't exist, let normal validation handle it
            }
            
            // Only validate dropdown/menu fields
            if (!in_array($field->datatype, ['menu', 'checkbox'])) {
                return ['valid' => true, 'suggestion' => '']; // Not a dropdown, no validation needed
            }
            
            // For menu fields, check if value exists in param1 (menu options)
            if ($field->datatype === 'menu' && !empty($field->param1)) {
                $options = explode("\n", $field->param1);
                $options = array_map('trim', $options);
                $options = array_filter($options); // Remove empty options
                
                // Case-insensitive exact match
                foreach ($options as $option) {
                    if (strcasecmp(trim($option), trim($value)) === 0) {
                        return ['valid' => true, 'suggestion' => ''];
                    }
                }
                
                // Find closest match for suggestion
                $suggestion = self::find_closest_match($value, $options);
                return [
                    'valid' => false, 
                    'suggestion' => $suggestion ? "Did you mean '{$suggestion}'? Available options: " . implode(', ', $options) : "Available options: " . implode(', ', $options)
                ];
            }
            
            // For checkbox fields, validate against yes/no values
            if ($field->datatype === 'checkbox') {
                $lowervalue = strtolower(trim($value));
                if (in_array($lowervalue, ['1', '0', 'yes', 'no', 'true', 'false', 'on', 'off'])) {
                    return ['valid' => true, 'suggestion' => ''];
                }
                return [
                    'valid' => false,
                    'suggestion' => "For checkbox fields, use: 1, 0, yes, no, true, false, on, or off"
                ];
            }
            
            return ['valid' => true, 'suggestion' => ''];
            
        } catch (Exception $e) {
            // If validation fails, assume valid to avoid blocking import
            return ['valid' => true, 'suggestion' => ''];
        }
    }
    
    /**
     * Find the closest matching option using simple string similarity
     *
     * @param string $needle Value to match
     * @param array $haystack Array of available options
     * @return string|null Closest match or null if no good match
     */
    private static function find_closest_match($needle, $haystack) {
        $needle = strtolower(trim($needle));
        $bestscore = 0;
        $bestmatch = null;
        
        foreach ($haystack as $option) {
            $option = trim($option);
            $optionlower = strtolower($option);
            
            // Check for partial matches
            if (strpos($optionlower, $needle) !== false || strpos($needle, $optionlower) !== false) {
                return $option;
            }
            
            // Use similar_text for fuzzy matching
            similar_text($needle, $optionlower, $score);
            if ($score > $bestscore && $score > 60) { // At least 60% similarity
                $bestscore = $score;
                $bestmatch = $option;
            }
        }
        
        return $bestmatch;
    }
    
    /**
     * Normalize field name - detect if it's a core user field or custom profile field
     * and return the proper internal field name
     *
     * @param string $fieldname Field name from CSV
     * @return string Normalized field name
     */
    private static function normalize_field_name($fieldname) {
        $fieldname = trim($fieldname);
        
        // If it already has profile_field_ prefix, keep it as is
        if (strpos($fieldname, 'profile_field_') === 0) {
            return $fieldname;
        }
        
        // Define core user fields that don't need profile_field_ prefix
        $corefields = [
            'username', 'email', 'firstname', 'lastname', 'city', 'country',
            'institution', 'department', 'phone1', 'phone2', 'address', 'description'
        ];
        
        // If it's a core field, return as is
        if (in_array($fieldname, $corefields)) {
            return $fieldname;
        }
        
        // Check if a custom profile field with this shortname exists
        global $DB;
        try {
            $customfield = $DB->get_record('user_info_field', ['shortname' => $fieldname]);
            if ($customfield) {
                // It's a custom profile field, add the prefix
                return 'profile_field_' . $fieldname;
            }
        } catch (Exception $e) {
            // Database error, assume it needs the prefix
        }
        
        // Default: assume it's a custom profile field and add prefix
        return 'profile_field_' . $fieldname;
    }
}