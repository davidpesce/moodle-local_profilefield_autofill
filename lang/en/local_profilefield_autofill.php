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

$string['actionscolumn'] = 'Actions';
$string['addmapping'] = 'Add New Mapping';
$string['applymappingstask'] = 'Apply profile field mappings';
$string['bulkdeleted'] = 'All mappings in group deleted successfully.';
$string['bulkdisabled'] = 'All mappings in group disabled successfully.';
$string['bulkenabled'] = 'All mappings in group enabled successfully.';
$string['bulkmanage'] = 'Bulk Actions';
$string['bulkmanage_desc'] = 'In group view, you can manage multiple mappings with the same target field and value together.';
$string['choosefield'] = 'Choose a field...';
$string['comma'] = 'Comma (,)';
$string['conditioncolumn'] = 'Condition';
$string['confirmbulkdelete'] = 'Are you sure you want to delete ALL mappings in this group? This action cannot be undone.';
$string['confirmdeletemapping'] = 'Are you sure you want to delete this mapping?';
$string['csvemptyrow'] = 'Empty row';
$string['csvfile'] = 'CSV File';
$string['csvfile_help'] = 'Select a CSV file containing profile field mappings. Maximum file size: 2MB.';
$string['csvfileempty'] = 'The uploaded CSV file is empty.';
$string['csvformat'] = 'CSV Format';
$string['csvformatinfo'] = 'The CSV file should have 4 columns in this order: sourcefield, sourcevalue, targetfield, targetvalue. Each row represents one mapping rule.';
$string['csvimport'] = 'Import CSV';
$string['csvimportcomplete'] = 'CSV import completed. Total: {$a->total}, Imported: {$a->imported}, Updated: {$a->updated}, Skipped: {$a->skipped}';
$string['csvimporthelp'] = 'Upload a CSV file to import multiple profile field mappings at once. The CSV should contain columns for: sourcefield, sourcevalue, targetfield, targetvalue.';
$string['csvinvalidsourcefield'] = 'Row {$a->row}: Invalid source field "{$a->field}".';
$string['csvinvalidtargetfield'] = 'Row {$a->row}: Invalid target field "{$a->field}".';
$string['csvinvalidtargetvalue'] = 'Row {$a->row}: Invalid value "{$a->value}" for field "{$a->field}". {$a->suggestion}';
$string['csvmappingexists'] = 'Mapping already exists (update not enabled)';
$string['csvnodata'] = 'No valid data found in the CSV file.';
$string['csvoptions'] = 'CSV Options';
$string['csvrowtooshort'] = 'Row {$a} has too few columns (expected 4).';
$string['csvskippeditems'] = 'Skipped Items:';
$string['csvskippedrow'] = 'Row {$a->row}: {$a->reason}';
$string['csvsourcefieldmissing'] = 'Row {$a}: Source field is required.';
$string['csvsourcevaluemissing'] = 'Row {$a}: Source value is required.';
$string['csvtargetfieldmissing'] = 'Row {$a}: Target field is required.';
$string['csvtargetvaluemissing'] = 'Row {$a}: Target value is required.';
$string['customfields'] = 'Custom Profile Fields';
$string['delete'] = 'Delete';
$string['deleteall'] = 'Delete All';
$string['deletemapping'] = 'Delete Mapping';
$string['delimiter'] = 'Delimiter';
$string['delimiter_help'] = 'The character used to separate values in the CSV file.';
$string['disable'] = 'Disable';
$string['disableall'] = 'Disable All';
$string['disabledstatus'] = 'Disabled';
$string['downloadtemplate'] = 'Download CSV Template';
$string['edit'] = 'Edit';
$string['editmapping'] = 'Edit Mapping';
$string['enable'] = 'Enable';
$string['enableall'] = 'Enable All';
$string['enabled'] = 'Enabled';
$string['enabled_desc'] = 'Whether this mapping rule is active.';
$string['enabled_help'] = 'When enabled, this mapping rule will be applied whenever users are created or updated. Disabled rules are ignored but preserved in the system.';
$string['enabledstatus'] = 'Enabled';
$string['enableimported'] = 'Enable imported mappings';
$string['enableimported_help'] = 'Automatically enable all mappings imported from the CSV file.';
$string['encoding'] = 'File Encoding';
$string['encoding_help'] = 'The character encoding of the CSV file.';
$string['errorimportingrow'] = 'Error importing row {$a}.';
$string['errorupdatingrow'] = 'Error updating row {$a}.';
$string['exportmappings'] = 'Export Mappings';
$string['field_address'] = 'Address';
$string['field_city'] = 'City';
$string['field_country'] = 'Country';
$string['field_department'] = 'Department';
$string['field_description'] = 'Description';
$string['field_email'] = 'Email address';
$string['field_firstname'] = 'First name';
$string['field_institution'] = 'Institution';
$string['field_lastname'] = 'Last name';
$string['field_phone1'] = 'Phone 1';
$string['field_phone2'] = 'Phone 2';
$string['field_username'] = 'Username';
$string['fieldnotfound'] = 'The specified field could not be found.';
$string['filesizetoobig'] = 'File size cannot exceed 2MB.';
$string['groupview'] = 'Group View';
$string['groupviewhelp'] = 'Grouped view shows mappings organized by target field and value, with bulk actions available for each group.';
$string['hasheader'] = 'File has header row';
$string['hasheader_help'] = 'Check this if the first row of the CSV file contains column headers.';
$string['help_mapping'] = 'This plugin monitors user profile changes and automatically fills profile fields based on configured rules. You can target both standard user fields (like city, institution) and custom profile fields. For example, you could automatically set a "City" field to "Boston" when the email domain is "@university.edu".';
$string['help_sourcevalue'] = 'Examples:<br>• Exact match: "student@university.edu"<br>• Wildcard: "*@university.edu" (matches any email from university.edu)<br>• Wildcard: "student*" (matches anything starting with "student")';
$string['import'] = 'Import';
$string['importmappings'] = 'Import Mappings';
$string['importoptions'] = 'Import Options';
$string['invaliddata'] = 'Invalid data provided.';
$string['invalidfiletype'] = 'Only CSV files are allowed.';
$string['listview'] = 'List View';
$string['mappingdeleted'] = 'Mapping deleted successfully.';
$string['mappingsaved'] = 'Mapping saved successfully.';
$string['nomappings'] = 'No field mappings have been configured yet.';
$string['pipe'] = 'Pipe (|)';
$string['plugindesc'] = 'Automatically fills or updates custom profile fields based on the values of other fields.';
$string['pluginname'] = 'Profile Field Auto-fill';
$string['searchmappings'] = 'Search mappings...';
$string['semicolon'] = 'Semicolon (;)';
$string['settingsdesc'] = 'Configure automatic profile field mappings. When a user is created or updated, these rules will automatically populate target fields based on source field values.';
$string['settingsheading'] = 'Profile Field Auto-fill Settings';
$string['sourcecolumn'] = 'Source';
$string['sourcefield'] = 'Source Field';
$string['sourcefield_desc'] = 'The field to monitor for changes. Can be a standard user field (like email, username) or a custom profile field.';
$string['sourcefield_help'] = 'Select the field that will be monitored for changes. This can be either a standard user field (like email, username, first name) or a custom profile field. When this field\'s value matches the condition you specify, the target field will be automatically updated.';
$string['sourcevalue'] = 'Source Value';
$string['sourcevalue_desc'] = 'The value in the source field that will trigger this mapping. Use * as a wildcard (e.g., *@example.com).';
$string['sourcevalue_help'] = 'Enter the value that will trigger this mapping rule. You can use exact matches or wildcards:\n\n* **Exact match**: "student@university.edu" - matches exactly this email\n* **Wildcard patterns**: Use * to match multiple characters:\n  * "*@university.edu" - matches any email ending with @university.edu\n  * "student*" - matches anything starting with "student"\n  * "*teacher*" - matches anything containing "teacher"\n\nThe matching is case-insensitive.';
$string['standardfields'] = 'Standard User Fields';
$string['statuscolumn'] = 'Status';
$string['tab'] = 'Tab';
$string['targetcolumn'] = 'Target';
$string['targetfield'] = 'Target Field';
$string['targetfield_desc'] = 'The custom profile field that will be automatically filled.';
$string['targetfield_help'] = 'Select the field that will be automatically filled when the source condition is met. You can choose from standard user fields (like city, institution, etc.) or custom profile fields. Note: Critical fields like username and email cannot be modified for security reasons.';
$string['targetvalue'] = 'Target Value';
$string['targetvalue_desc'] = 'The value that will be set in the target field when the source condition is met.';
$string['targetvalue_help'] = 'Enter the value that will be set in the target field when the source condition matches. This will replace any existing value in the target field.';
$string['updateexisting'] = 'Update existing mappings';
$string['updateexisting_help'] = 'If a mapping with the same source field, source value, and target field already exists, update it with the new target value.';
$string['valuecolumn'] = 'Value';
