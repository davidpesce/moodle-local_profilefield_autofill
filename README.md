# Profile Field Auto-fill Plugin

A Moodle local plugin that automatically fills or updates custom profile fields based on the values of other fields.

## Features

- **Automatic Field Population**: Automatically populates custom profile fields when users are created or updated
- **Flexible Source Fields**: Use standard user fields (email, username, etc.) or custom profile fields as triggers
- **Pattern Matching**: Support for exact matches and wildcard patterns (using * for multiple characters)
- **Admin Interface**: Easy-to-use admin interface for managing field mapping rules
- **Event-Driven**: Listens to `user_created` and `user_updated` events for real-time processing
- **Enable/Disable Rules**: Individual mapping rules can be enabled or disabled without deletion

## Installation

1. Download or clone this plugin into your Moodle's `/local/` directory:
   ```
   /path/to/moodle/local/profilefield_autofill/
   ```

2. Visit the Site Administration → Notifications page to complete the installation.

3. The plugin will create the necessary database table automatically.

## Configuration

1. Navigate to **Site Administration → Local plugins → Profile Field Auto-fill**

2. Click **"Add New Mapping"** to create a field mapping rule

3. Configure the mapping:
   - **Source Field**: Choose the field to monitor (standard user field or custom profile field)
   - **Source Value**: Enter the value/pattern that will trigger the rule
   - **Target Field**: Select the custom profile field to auto-fill
   - **Target Value**: Enter the value to set in the target field
   - **Enabled**: Check to activate the rule

## Usage Examples

### Example 1: Auto-assign User Type based on Email Domain

- **Source Field**: Email address
- **Source Value**: `*@university.edu`
- **Target Field**: User Type (custom profile field)
- **Target Value**: `Student`

This will automatically set the "User Type" field to "Student" for any user with an email ending in "@university.edu".

### Example 2: Set Department based on Username Pattern

- **Source Field**: Username  
- **Source Value**: `staff*`
- **Target Field**: Department (custom profile field)
- **Target Value**: `Faculty`

This will set the "Department" field to "Faculty" for any username starting with "staff".

### Example 3: Copy Values Between Custom Fields

- **Source Field**: Employee ID (custom profile field)
- **Source Value**: `EMP*`
- **Target Field**: User Category (custom profile field) 
- **Target Value**: `Employee`

This will set "User Category" to "Employee" when the Employee ID starts with "EMP".

## Pattern Matching

The plugin supports flexible pattern matching:

- **Exact Match**: `student@example.com` - matches exactly
- **Suffix Wildcard**: `*@example.com` - matches any email from example.com
- **Prefix Wildcard**: `teacher*` - matches usernames starting with "teacher"
- **Contains**: `*admin*` - matches anything containing "admin"

## Choosing source fields: the trust boundary

Mappings are applied on `user_updated` as well as `user_created`, and that
includes a user updating their own profile. If a mapping's **source** field is
one the user can edit themselves — `city`, `department`, or an unlocked custom
profile field — then that user can set the source to the trigger value and cause
the plugin to write the mapped **target** value onto their own record.

This is what an auto-fill plugin is for, and it is bounded: a user can only ever
obtain the exact value an administrator wired to that condition, the plugin only
ever touches the record of the user in the event, and privileged fields
(`username`, `email`, roles, capabilities) are not offered as targets. But it
does mean:

> **Do not drive a security-relevant target from a user-editable source field.**

If a target field is consumed elsewhere for entitlement, access or licensing
decisions, drive it from a field the user cannot set — one locked in the profile
field definition, or a standard field maintained by an authoritative sync.
Otherwise a user can grant themselves whatever that mapping confers.

The same applies in reverse: a mapping whose source is locked and whose target is
descriptive carries no such risk, which covers most configurations.

## Technical Details

### Database Table

The plugin creates a table `local_profilefield_autofill_mapping` with the following structure:

- `id` - Primary key
- `sourcefield` - Source field name (e.g., 'email', 'profile_field_department')
- `targetfield` - Target custom profile field shortname
- `sourcevalue` - Pattern to match in source field
- `targetvalue` - Value to set in target field
- `enabled` - Whether the mapping is active
- `timecreated` / `timemodified` - Timestamps

### Events

The plugin listens to these Moodle events:
- `\core\event\user_created`
- `\core\event\user_updated`

### Requirements

- Moodle 4.5 or later
- Admin privileges to configure mappings

## Support

For issues, questions, or contributions, please contact the plugin maintainer or create an issue in the repository.

## License

This plugin is licensed under the GNU General Public License v3.0.