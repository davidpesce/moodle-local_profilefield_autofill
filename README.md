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

## Technical Details

### Database Table

The plugin creates a table `local_profilefield_mapping` with the following structure:

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

- Moodle 4.4 or later
- Admin privileges to configure mappings

## Support

For issues, questions, or contributions, please contact the plugin maintainer or create an issue in the repository.

## License

This plugin is licensed under the GNU General Public License v3.0.