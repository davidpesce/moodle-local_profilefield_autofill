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
 * Privacy provider for local_profilefield_autofill.
 *
 * @package    local_profilefield_autofill
 * @copyright  2026 David Pesce - Exputo Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_profilefield_autofill\privacy;

/**
 * Privacy provider.
 *
 * The plugin's own table holds mapping rules only — source and target field
 * names, the values to match and write, an enabled flag and timestamps — and
 * stores nothing about any individual user. The user data it writes lives in
 * core's own {user} and {user_info_data} tables, which core's providers already
 * export and delete, so there is nothing for this plugin to add.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Reason why this plugin stores no personal data.
     *
     * @return string Language string identifier
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
