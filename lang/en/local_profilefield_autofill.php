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
 * Language file for local_profilefield_autofill plugin
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Profile Field Auto-fill';
$string['plugindesc'] = 'Automatically fills or updates custom profile fields based on the values of other fields.';

// Settings page.
$string['settingsheading'] = 'Profile Field Auto-fill Settings';
$string['settingsdesc'] = 'Configure automatic profile field mappings. When a user is created or updated, these rules will automatically populate target fields based on source field values.';

// Mapping management.
$string['addmapping'] = 'Add New Mapping';
$string['editmapping'] = 'Edit Mapping';
$string['deletemapping'] = 'Delete Mapping';
$string['confirmdeletemapping'] = 'Are you sure you want to delete this mapping?';
$string['nomappings'] = 'No field mappings have been configured yet.';

// Form fields.
$string['sourcefield'] = 'Source Field';
$string['sourcefield_desc'] = 'The field to monitor for changes. Can be a standard user field (like email, username) or a custom profile field.';
$string['sourcevalue'] = 'Source Value';
$string['sourcevalue_desc'] = 'The value in the source field that will trigger this mapping. Use * as a wildcard (e.g., *@example.com).';
$string['targetfield'] = 'Target Field';
$string['targetfield_desc'] = 'The custom profile field that will be automatically filled.';
$string['targetvalue'] = 'Target Value';
$string['targetvalue_desc'] = 'The value that will be set in the target field when the source condition is met.';
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'Whether this mapping rule is active.';

// Field options.
$string['choosefield'] = 'Choose a field...';
$string['standardfields'] = 'Standard User Fields';
$string['customfields'] = 'Custom Profile Fields';

// Standard user fields.
$string['field_username'] = 'Username';
$string['field_email'] = 'Email address';
$string['field_firstname'] = 'First name';
$string['field_lastname'] = 'Last name';
$string['field_city'] = 'City';
$string['field_country'] = 'Country';
$string['field_institution'] = 'Institution';
$string['field_department'] = 'Department';
$string['field_phone1'] = 'Phone 1';
$string['field_phone2'] = 'Phone 2';
$string['field_address'] = 'Address';
$string['field_description'] = 'Description';

// Messages.
$string['mappingsaved'] = 'Mapping saved successfully.';
$string['mappingdeleted'] = 'Mapping deleted successfully.';
$string['invaliddata'] = 'Invalid data provided.';
$string['fieldnotfound'] = 'The specified field could not be found.';

// Table headers.
$string['sourcecolumn'] = 'Source';
$string['conditioncolumn'] = 'Condition';
$string['targetcolumn'] = 'Target';
$string['valuecolumn'] = 'Value';
$string['statuscolumn'] = 'Status';
$string['actionscolumn'] = 'Actions';

// Status.
$string['enabledstatus'] = 'Enabled';
$string['disabledstatus'] = 'Disabled';

// Actions.
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';

// Help text.
$string['help_sourcevalue'] = 'Examples:<br>• Exact match: "student@university.edu"<br>• Wildcard: "*@university.edu" (matches any email from university.edu)<br>• Wildcard: "student*" (matches anything starting with "student")';
$string['help_mapping'] = 'This plugin monitors user profile changes and automatically fills profile fields based on configured rules. You can target both standard user fields (like city, institution) and custom profile fields. For example, you could automatically set a "City" field to "Boston" when the email domain is "@university.edu".';

// Help strings for form fields.
$string['sourcefield_help'] = 'Select the field that will be monitored for changes. This can be either a standard user field (like email, username, first name) or a custom profile field. When this field\'s value matches the condition you specify, the target field will be automatically updated.';
$string['sourcevalue_help'] = 'Enter the value that will trigger this mapping rule. You can use exact matches or wildcards:\n\n* **Exact match**: "student@university.edu" - matches exactly this email\n* **Wildcard patterns**: Use * to match multiple characters:\n  * "*@university.edu" - matches any email ending with @university.edu\n  * "student*" - matches anything starting with "student"\n  * "*teacher*" - matches anything containing "teacher"\n\nThe matching is case-insensitive.';
$string['targetfield_help'] = 'Select the field that will be automatically filled when the source condition is met. You can choose from standard user fields (like city, institution, etc.) or custom profile fields. Note: Critical fields like username and email cannot be modified for security reasons.';
$string['targetvalue_help'] = 'Enter the value that will be set in the target field when the source condition matches. This will replace any existing value in the target field.';
$string['enabled_help'] = 'When enabled, this mapping rule will be applied whenever users are created or updated. Disabled rules are ignored but preserved in the system.';