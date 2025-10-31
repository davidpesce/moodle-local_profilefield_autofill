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
 * Event observer for local_profilefield_autofill plugin
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill;

/**
 * Event observer class for profile field auto-fill functionality
 */
class observer {

    /**
     * Observer for user_created event
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        self::process_user_profile_autofill($event->objectid);
    }

    /**
     * Observer for user_updated event
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        self::process_user_profile_autofill($event->objectid);
    }

    /**
     * Process profile field auto-fill for a user
     *
     * @param int $userid The user ID
     */
    private static function process_user_profile_autofill($userid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Get all enabled mappings.
        $mappings = $DB->get_records('local_profilefield_mapping', ['enabled' => 1]);
        
        if (empty($mappings)) {
            return;
        }

        // Get user data with profile fields.
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            return;
        }

        // Load custom profile fields for this user.
        profile_load_custom_fields($user);

        $changed = false;
        $profilefields = [];

        foreach ($mappings as $mapping) {
            // Get source field value.
            $sourcevalue = self::get_field_value($user, $mapping->sourcefield);
            
            if ($sourcevalue === null) {
                continue; // Source field doesn't exist or has no value.
            }

            // Check if source value matches the mapping condition.
            if (self::values_match($sourcevalue, $mapping->sourcevalue)) {
                // Check if target is a custom profile field or standard field
                if (strpos($mapping->targetfield, 'profile_field_') === 0) {
                    // Handle custom profile field
                    $shortname = substr($mapping->targetfield, strlen('profile_field_'));
                    $targetfieldinfo = self::get_custom_field_info($shortname);
                    if (!$targetfieldinfo) {
                        continue; // Target field doesn't exist.
                    }

                    // Load current profile fields if not already loaded.
                    if (empty($profilefields)) {
                        $profilefields = profile_get_user_fields_with_data($userid);
                    }

                    // Update the custom profile field.
                    $targetfielddata = null;
                    foreach ($profilefields as $field) {
                        if ($field->get_shortname() === $shortname) {
                            $targetfielddata = $field;
                            break;
                        }
                    }

                    if ($targetfielddata) {
                        // Check if the value actually needs to be changed.
                        $currentvalue = $targetfielddata->display_data();
                        if ($currentvalue != $mapping->targetvalue) {
                            // Update the field value.
                            $DB->set_field('user_info_data', 'data', $mapping->targetvalue, [
                                'userid' => $userid,
                                'fieldid' => $targetfieldinfo->id
                            ]);
                            $changed = true;
                        }
                    } else {
                        // Field doesn't exist for this user, create it.
                        $record = new \stdClass();
                        $record->userid = $userid;
                        $record->fieldid = $targetfieldinfo->id;
                        $record->data = $mapping->targetvalue;
                        $record->dataformat = 0;
                        $DB->insert_record('user_info_data', $record);
                        $changed = true;
                    }
                } else {
                    // Handle standard user field
                    $currentvalue = isset($user->{$mapping->targetfield}) ? $user->{$mapping->targetfield} : '';
                    if ($currentvalue != $mapping->targetvalue) {
                        // Update the standard user field
                        $DB->set_field('user', $mapping->targetfield, $mapping->targetvalue, ['id' => $userid]);
                        $changed = true;
                    }
                }
            }
        }

        // If we made changes, trigger a profile updated event.
        if ($changed) {
            // Clear the user cache to ensure changes are reflected.
            \core_user::reset_caches();
        }
    }

    /**
     * Get field value from user object or profile fields
     *
     * @param \stdClass $user User object with profile fields
     * @param string $fieldname Field name (can be standard field or profile_field_xxx)
     * @return mixed Field value or null if not found
     */
    private static function get_field_value($user, $fieldname) {
        // Check if it's a custom profile field.
        if (strpos($fieldname, 'profile_field_') === 0) {
            $shortname = substr($fieldname, strlen('profile_field_'));
            if (isset($user->profile[$shortname])) {
                return $user->profile[$shortname];
            }
        } else {
            // It's a standard user field.
            if (property_exists($user, $fieldname)) {
                return $user->{$fieldname};
            }
        }

        return null;
    }

    /**
     * Check if two values match (supports basic pattern matching)
     *
     * @param mixed $sourcevalue The actual value from the source field
     * @param string $patternvalue The pattern to match against
     * @return bool True if values match
     */
    private static function values_match($sourcevalue, $patternvalue) {
        // Convert both to strings for comparison.
        $sourcestr = (string)$sourcevalue;
        $patternstr = (string)$patternvalue;

        // Exact match.
        if ($sourcestr === $patternstr) {
            return true;
        }

        // Check for wildcard patterns.
        if (strpos($patternstr, '*') !== false) {
            $pattern = str_replace('*', '.*', preg_quote($patternstr, '/'));
            return preg_match('/^' . $pattern . '$/i', $sourcestr);
        }

        // Case-insensitive match.
        return strcasecmp($sourcestr, $patternstr) === 0;
    }

    /**
     * Get custom field information by shortname
     *
     * @param string $shortname The field shortname
     * @return \stdClass|null Field info or null if not found
     */
    private static function get_custom_field_info($shortname) {
        global $DB;

        return $DB->get_record('user_info_field', ['shortname' => $shortname]);
    }
}