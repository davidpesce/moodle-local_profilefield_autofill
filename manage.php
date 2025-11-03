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

// Add custom CSS for grouped view
$PAGE->requires->css('/local/profilefield_autofill/styles.css');

// Ensure Bootstrap JS is loaded for collapse functionality
$PAGE->requires->js_call_amd('core/bootstrap', 'init');

// Handle actions.
if ($action === 'delete' && $id > 0) {
    require_sesskey();
    
    // Preserve the grouped view parameter
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);
    
    if (\local_profilefield_autofill\helper::delete_mapping($id)) {
        redirect($redirecturl, get_string('mappingdeleted', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($redirecturl, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Handle bulk actions
if ($action === 'bulkenable' || $action === 'bulkdisable') {
    $targetfield = required_param('targetfield', PARAM_TEXT);
    $targetvalue = required_param('targetvalue', PARAM_TEXT);
    require_sesskey();
    
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);
    
    $enabled = ($action === 'bulkenable') ? 1 : 0;
    $result = \local_profilefield_autofill\helper::bulk_update_status($targetfield, $targetvalue, $enabled);
    
    if ($result) {
        $message = $enabled ? 
            get_string('bulkenabled', 'local_profilefield_autofill') : 
            get_string('bulkdisabled', 'local_profilefield_autofill');
        redirect($redirecturl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($redirecturl, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

if ($action === 'bulkdelete') {
    $targetfield = required_param('targetfield', PARAM_TEXT);
    $targetvalue = required_param('targetvalue', PARAM_TEXT);
    require_sesskey();
    
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);
    
    $result = \local_profilefield_autofill\helper::bulk_delete($targetfield, $targetvalue);
    
    if ($result) {
        redirect($redirecturl, get_string('bulkdeleted', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($redirecturl, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

if ($action === 'toggle' && $id > 0) {
    require_sesskey();
    
    // Preserve the grouped view parameter
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);
    
    if (\local_profilefield_autofill\helper::toggle_mapping_status($id)) {
        redirect($redirecturl, get_string('mappingsaved', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($redirecturl, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
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
        $returnurl = optional_param('returnurl', '', PARAM_URL);
        $redirecturl = $returnurl ? new moodle_url($returnurl) : $PAGE->url;
        redirect($redirecturl);
    }

    if ($data = $form->get_data()) {
        try {
            $mappingid = \local_profilefield_autofill\helper::save_mapping($data);
            // Preserve the grouped view parameter if it was set
            $returnurl = optional_param('returnurl', '', PARAM_URL);
            $redirecturl = $returnurl ? new moodle_url($returnurl) : $PAGE->url;
            
            if ($mappingid) {
                redirect($redirecturl, get_string('mappingsaved', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                redirect($redirecturl, get_string('invaliddata', 'local_profilefield_autofill'), null, \core\output\notification::NOTIFY_ERROR);
            }
        } catch (Exception $e) {
            $returnurl = optional_param('returnurl', '', PARAM_URL);
            $redirecturl = $returnurl ? new moodle_url($returnurl) : $PAGE->url;
            redirect($redirecturl, 'Error saving mapping: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
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

// Add action button.
$addurl = new moodle_url($PAGE->url, ['action' => 'add']);

echo html_writer::div(
    html_writer::link($addurl, get_string('addmapping', 'local_profilefield_autofill'), 
        ['class' => 'btn btn-primary']),
    'mb-3'
);

// Get view preference
$grouped = optional_param('grouped', 0, PARAM_INT);

// Get all mappings.
$mappings = \local_profilefield_autofill\helper::get_all_mappings();

if (empty($mappings)) {
    echo html_writer::div(get_string('nomappings', 'local_profilefield_autofill'), 'alert alert-warning');
} else {
    // View toggle controls and search
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
    
    // View toggle buttons
    echo html_writer::start_div('btn-group btn-group-sm');
    
    $listclass = !$grouped ? 'btn-primary' : 'btn-outline-primary';
    $listurl = new moodle_url($PAGE->url);
    echo html_writer::link($listurl, get_string('listview', 'local_profilefield_autofill'), 
        ['class' => 'btn ' . $listclass]);
    
    $groupclass = $grouped ? 'btn-primary' : 'btn-outline-primary';
    $groupurl = new moodle_url($PAGE->url, ['grouped' => 1]);
    echo html_writer::link($groupurl, get_string('groupview', 'local_profilefield_autofill'), 
        ['class' => 'btn ' . $groupclass]);
    
    echo html_writer::end_div();
    
    // Search input
    echo html_writer::start_div('input-group input-group-sm', ['style' => 'width: 250px;']);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'id' => 'mapping-search',
        'placeholder' => get_string('searchmappings', 'local_profilefield_autofill'),
        'aria-label' => get_string('searchmappings', 'local_profilefield_autofill')
    ]);
    echo html_writer::start_div('input-group-append');
    echo html_writer::tag('span', html_writer::tag('i', '', ['class' => 'fa fa-search']), 
        ['class' => 'input-group-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    echo html_writer::end_div();
    
    // Info text for grouped view
    if ($grouped) {
        echo html_writer::div(get_string('groupviewhelp', 'local_profilefield_autofill'), 
            'alert alert-info small mb-3');
    }

    // Add view toggle
    $toggleurl = new moodle_url($PAGE->url, ['grouped' => $grouped ? 0 : 1]);
    $toggletext = $grouped ? 'Show List View' : 'Show Grouped View';
    echo html_writer::div(
        html_writer::link($toggleurl, $toggletext, ['class' => 'btn btn-sm btn-outline-secondary']),
        'mb-3 text-right'
    );
    
    if ($grouped) {
        // Display grouped view
        $groups = \local_profilefield_autofill\helper::group_mappings_by_target($mappings);
        echo html_writer::start_div('', ['id' => 'grouped-mappings-container']);
        \local_profilefield_autofill\helper::display_grouped_mappings($groups, $PAGE->url);
        echo html_writer::end_div();
    } else {
        // Display original table view
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
    $table->id = 'profilefield-mappings-table';
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
        
        // Action icons - native Moodle style (plain icons)
        // Preserve current view state for return URLs
        $currentparams = $grouped ? ['grouped' => 1] : [];
        $currenturl = new moodle_url($PAGE->url, $currentparams);
        
        $editurl = new moodle_url($PAGE->url, ['action' => 'edit', 'id' => $mapping->id, 'returnurl' => $currenturl->out(false)]);
        $actions[] = html_writer::link($editurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-cog', 'aria-hidden' => 'true']), 
            ['class' => 'action-icon', 'title' => get_string('edit', 'local_profilefield_autofill')]);

        // Toggle status link with eye icon.
        $toggletext = $mapping->enabled ? get_string('disable', 'local_profilefield_autofill') : get_string('enable', 'local_profilefield_autofill');
        $toggleicon = $mapping->enabled ? 'fa-eye-slash' : 'fa-eye';
        $toggleparams = array_merge(['action' => 'toggle', 'id' => $mapping->id, 'sesskey' => sesskey()], $currentparams);
        $toggleurl = new moodle_url($PAGE->url, $toggleparams);
        $actions[] = html_writer::link($toggleurl, 
            html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon, 'aria-hidden' => 'true']), 
            ['class' => 'action-icon', 'title' => $toggletext]);

        // Delete link with trash icon.
        $deleteparams = array_merge(['action' => 'delete', 'id' => $mapping->id, 'sesskey' => sesskey()], $currentparams);
        $deleteurl = new moodle_url($PAGE->url, $deleteparams);
        $deleteconfirm = get_string('confirmdeletemapping', 'local_profilefield_autofill');
        $actions[] = html_writer::link($deleteurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']), 
            ['class' => 'action-icon', 'title' => get_string('delete', 'local_profilefield_autofill'), 'onclick' => "return confirm('$deleteconfirm');"]);

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
    }
}

// Add search functionality JavaScript
echo html_writer::start_tag('script');
echo "
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mapping-search');
    if (!searchInput) return;
    
    var searchTimeout;
    
    function performSearch() {
        var searchTerm = searchInput.value.trim().toLowerCase();
        
        // Get current view elements
        var table = document.getElementById('profilefield-mappings-table');
        var container = document.getElementById('grouped-mappings-container');
        
        if (searchTerm === '') {
            // Show all elements
            if (table) {
                var rows = table.querySelectorAll('tbody tr');
                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            }
            if (container) {
                var cards = container.querySelectorAll('.card');
                for (var i = 0; i < cards.length; i++) {
                    cards[i].style.display = '';
                }
            }
            return;
        }
        
        // Search table view
        if (table) {
            var tbody = table.querySelector('tbody');
            if (tbody) {
                var rows = tbody.querySelectorAll('tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(searchTerm) !== -1 ? '' : 'none';
                }
            }
        }
        
        // Search grouped view
        if (container) {
            var cards = container.querySelectorAll('.card');
            for (var i = 0; i < cards.length; i++) {
                var card = cards[i];
                var text = card.textContent.toLowerCase();
                card.style.display = text.indexOf(searchTerm) !== -1 ? '' : 'none';
            }
        }
    }
    
    function debouncedSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    }
    
    // Bind events
    searchInput.addEventListener('input', debouncedSearch);
    searchInput.addEventListener('keyup', debouncedSearch);
});
";
echo html_writer::end_tag('script');

echo $OUTPUT->footer();