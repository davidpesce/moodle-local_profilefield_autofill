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
 * Scheduled task to apply profile field mappings
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill\task;

/**
 * Scheduled task to apply profile field mappings to users
 */
class apply_mappings extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('applymappingstask', 'local_profilefield_autofill');
    }

    /**
     * Run the scheduled task.
     */
    public function execute() {
        global $DB;

        // Get all enabled mappings.
        $mappings = \local_profilefield_autofill\helper::get_all_mappings();
        $enabledmappings = array_filter($mappings, function ($mapping) {
            return $mapping->enabled;
        });

        if (empty($enabledmappings)) {
            mtrace('No enabled mappings found. Skipping task.');
            return;
        }

        mtrace('Starting profile field mapping task with ' . count($enabledmappings) . ' enabled mappings...');

        $totalusers = 0;
        $totalupdates = 0;

        // Process each mapping.
        foreach ($enabledmappings as $mapping) {
            mtrace("Processing mapping: {$mapping->sourcefield} = '{$mapping->sourcevalue}'"
                . " → {$mapping->targetfield} = '{$mapping->targetvalue}'");

            // Build the query to find matching users.
            $sql = $this->build_user_query($mapping);
            $params = $this->build_query_params($mapping);

            try {
                // Get users that match the source condition.
                $users = $DB->get_records_sql($sql, $params);
                $usercount = count($users);
                $updates = 0;

                if ($usercount > 0) {
                    mtrace("  Found {$usercount} users matching source condition");

                    foreach ($users as $user) {
                        if ($this->update_user_field($user, $mapping)) {
                            $updates++;
                        }
                    }

                    mtrace("  Updated {$updates} users");
                } else {
                    mtrace("  No users found matching source condition");
                }

                $totalusers += $usercount;
                $totalupdates += $updates;
            } catch (\Exception $e) {
                mtrace("  ERROR processing mapping: " . $e->getMessage());
                continue;
            }
        }

        mtrace("Task completed. Processed {$totalusers} users, made {$totalupdates} updates.");
    }

    /**
     * Build SQL query to find users matching the source condition.
     *
     * @param object $mapping The mapping record
     * @return string SQL query
     */
    private function build_user_query($mapping) {
        global $DB;

        $sourcefield = $mapping->sourcefield;

        // Handle custom profile fields vs standard user fields. The comparison is
        // case insensitive so that it agrees with the observer, which falls back to
        // strcasecmp() and matches wildcards case insensitively.
        if (strpos($sourcefield, 'profile_field_') === 0) {
            // Custom profile field - join with user_info_data.
            $like = $DB->sql_like($DB->sql_compare_text('uid.data'), ':sourcevalue', false);

            $sql = "SELECT DISTINCT u.*, uid.data as source_value
                    FROM {user} u
                    JOIN {user_info_data} uid ON u.id = uid.userid
                    JOIN {user_info_field} uif ON uid.fieldid = uif.id
                    WHERE u.deleted = 0
                    AND u.suspended = 0
                    AND uif.shortname = :fieldshortname
                    AND {$like}";
        } else {
            // Standard user field.
            $like = $DB->sql_like($DB->sql_compare_text("u.{$sourcefield}"), ':sourcevalue', false);

            $sql = "SELECT DISTINCT u.*, u.{$sourcefield} as source_value
                    FROM {user} u
                    WHERE u.deleted = 0
                    AND u.suspended = 0
                    AND {$like}";
        }

        return $sql;
    }

    /**
     * Build parameters for the user query.
     *
     * @param object $mapping The mapping record
     * @return array Query parameters
     */
    private function build_query_params($mapping) {
        global $DB;

        $params = [];

        // Add field shortname parameter for custom fields.
        if (strpos($mapping->sourcefield, 'profile_field_') === 0) {
            $shortname = substr($mapping->sourcefield, strlen('profile_field_'));
            $params['fieldshortname'] = $shortname;
        }

        // Convert the wildcard pattern to a SQL LIKE pattern. Escape first so that a
        // literal % or _ in the mapping stays literal, then turn * into the wildcard.
        $sourcevalue = str_replace('*', '%', $DB->sql_like_escape($mapping->sourcevalue));
        $params['sourcevalue'] = $sourcevalue;

        return $params;
    }

    /**
     * Update a user's target field with the mapping value.
     *
     * @param object $user The user record
     * @param object $mapping The mapping record
     * @return bool True if field was updated
     */
    private function update_user_field($user, $mapping) {
        global $DB;

        $targetfield = $mapping->targetfield;
        $targetvalue = $mapping->targetvalue;

        try {
            if (strpos($targetfield, 'profile_field_') === 0) {
                // Custom profile field.
                $shortname = substr($targetfield, strlen('profile_field_'));

                // Get the field definition.
                $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
                if (!$field) {
                    mtrace("    WARNING: Target field '{$shortname}' not found for user {$user->id}");
                    return false;
                }

                // Check if user already has this value.
                $existing = $DB->get_record('user_info_data', [
                    'userid' => $user->id,
                    'fieldid' => $field->id,
                ]);

                if ($existing && $existing->data === $targetvalue) {
                    // Value already matches, skip update.
                    return false;
                }

                if ($existing) {
                    // Update existing record.
                    $existing->data = $targetvalue;
                    $DB->update_record('user_info_data', $existing);
                } else {
                    // Create new record.
                    $record = new \stdClass();
                    $record->userid = $user->id;
                    $record->fieldid = $field->id;
                    $record->data = $targetvalue;
                    $DB->insert_record('user_info_data', $record);
                }

                mtrace("    Updated user {$user->id} ({$user->username}): {$targetfield} = '{$targetvalue}'");
                return true;
            } else {
                // Standard user field.
                // Check if user already has this value.
                if (isset($user->$targetfield) && $user->$targetfield === $targetvalue) {
                    return false;
                }

                // Update the user record.
                $updateuser = new \stdClass();
                $updateuser->id = $user->id;
                $updateuser->$targetfield = $targetvalue;
                $updateuser->timemodified = time();

                $DB->update_record('user', $updateuser);

                mtrace("    Updated user {$user->id} ({$user->username}): {$targetfield} = '{$targetvalue}'");
                return true;
            }
        } catch (\Exception $e) {
            mtrace("    ERROR updating user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
