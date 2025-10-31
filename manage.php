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
 * Management page for profile field mappings
 *
 * @package    local_profilefield_autofill
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/mapping_form.php');

// Check admin access.
admin_externalpage_setup('local_profilefield_autofill_manage');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/profilefield_autofill/manage.php'));
$PAGE->set_title(get_string('pluginname', 'local_profilefield_autofill'));
$PAGE->set_heading(get_string('pluginname', 'local_profilefield_autofill'));

// Handle actions.
if ($action === 'delete' && $id > 0) {
    require_sesskey();
    
    if (\local_profilefield_autofill\helper::delete_mapping($id)) {
        redirect($PAGE->url, get_string('mappingdeleted', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($PAGE->url, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

if ($action === 'toggle' && $id > 0) {
    require_sesskey();
    
    if (\local_profilefield_autofill\helper::toggle_mapping_status($id)) {
        redirect($PAGE->url, get_string('mappingsaved', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($PAGE->url, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Handle form for adding/editing mappings.
if ($action === 'add' || ($action === 'edit' && $id > 0)) {
    $mapping = null;
    if ($action === 'edit') {
        $mapping = \local_profilefield_autofill\helper::get_mapping($id);
        if (!$mapping) {
            redirect($PAGE->url, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    // Set the form action URL explicitly
    $formurl = new moodle_url('/local/profilefield_autofill/manage.php', ['action' => $action]);
    if ($action === 'edit') {
        $formurl->param('id', $id);
    }
    
    $form = new local_profilefield_autofill_mapping_form($formurl);
    
    if ($mapping) {
        $form->set_data($mapping);
    }

    if ($form->is_cancelled()) {
        redirect($PAGE->url);
    }

    if ($data = $form->get_data()) {
        try {
            $mappingid = \local_profilefield_autofill\helper::save_mapping($data);
            if ($mappingid) {
                redirect($PAGE->url, get_string('mappingsaved', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                redirect($PAGE->url, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
            }
        } catch (Exception $e) {
            redirect($PAGE->url, 'Error saving mapping: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    echo $OUTPUT->header();
    
    $title = $action === 'add' ? get_string('addmapping', 'local_profilefield_autofill') : get_string('editmapping', 'local_profilefield_autofill');
    echo $OUTPUT->heading($title);
    
    echo html_writer::div(get_string('help_mapping', 'local_profilefield_autofill'), 'alert alert-info');
    
    $form->display();
    
    echo $OUTPUT->footer();
    exit;
}

// Display management page.
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'local_profilefield_autofill'));

echo html_writer::div(get_string('plugindesc', 'local_profilefield_autofill'), 'alert alert-info');

// Add mapping button.
$addurl = new moodle_url($PAGE->url, ['action' => 'add']);
echo html_writer::link($addurl, get_string('addmapping', 'local_profilefield_autofill'), 
    ['class' => 'btn btn-primary mb-3']);

// Get all mappings.
$mappings = \local_profilefield_autofill\helper::get_all_mappings();

if (empty($mappings)) {
    echo html_writer::div(get_string('nomappings', 'local_profilefield_autofill'), 'alert alert-warning');
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('sourcecolumn', 'local_profilefield_autofill'),
        get_string('conditioncolumn', 'local_profilefield_autofill'),
        get_string('targetcolumn', 'local_profilefield_autofill'),
        get_string('valuecolumn', 'local_profilefield_autofill'),
        get_string('statuscolumn', 'local_profilefield_autofill'),
        get_string('actionscolumn', 'local_profilefield_autofill'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($mappings as $mapping) {
        $row = [];

        // Source field and condition.
        $sourcename = \local_profilefield_autofill\helper::format_field_name($mapping->sourcefield);
        $row[] = html_writer::tag('strong', $sourcename);
        $row[] = html_writer::tag('code', format_string($mapping->sourcevalue));

        // Target field and value.
        $targetname = \local_profilefield_autofill\helper::format_field_name($mapping->targetfield);
        $row[] = html_writer::tag('strong', $targetname);
        $row[] = html_writer::tag('code', format_string($mapping->targetvalue));

        // Status.
        $statustext = $mapping->enabled ? 
            get_string('enabledstatus', 'local_profilefield_autofill') : 
            get_string('disabledstatus', 'local_profilefield_autofill');
        $statusclass = $mapping->enabled ? 'badge-success' : 'badge-secondary';
        $row[] = html_writer::span($statustext, 'badge ' . $statusclass);

        // Actions.
        $actions = [];
        
        // Edit link.
        $editurl = new moodle_url($PAGE->url, ['action' => 'edit', 'id' => $mapping->id]);
        $actions[] = html_writer::link($editurl, get_string('edit', 'local_profilefield_autofill'), 
            ['class' => 'btn btn-sm btn-outline-primary']);

        // Toggle status link.
        $toggletext = $mapping->enabled ? get_string('disable', 'local_profilefield_autofill') : get_string('enable', 'local_profilefield_autofill');
        $toggleurl = new moodle_url($PAGE->url, ['action' => 'toggle', 'id' => $mapping->id, 'sesskey' => sesskey()]);
        $actions[] = html_writer::link($toggleurl, $toggletext, 
            ['class' => 'btn btn-sm btn-outline-secondary']);

        // Delete link.
        $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $mapping->id, 'sesskey' => sesskey()]);
        $deleteconfirm = get_string('confirmdeletemapping', 'local_profilefield_autofill');
        $actions[] = html_writer::link($deleteurl, get_string('delete', 'local_profilefield_autofill'), 
            ['class' => 'btn btn-sm btn-outline-danger', 'onclick' => "return confirm('$deleteconfirm');"]);

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();