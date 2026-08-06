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
require_once(__DIR__ . '/classes/import_form.php');

// Check admin access.
// Determine admin page context based on how we were accessed.
global $ADMIN;
$adminpage = 'local_profilefield_autofill_manage'; // Default to local plugins context.

// Check if we're being accessed from the accounts section.
$section = optional_param('section', '', PARAM_ALPHANUMEXT);
if ($section === 'local_profilefield_autofill_accounts') {
    $adminpage = 'local_profilefield_autofill_accounts';
}

admin_externalpage_setup($adminpage);

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/profilefield_autofill/manage.php'));
$PAGE->set_title(get_string('pluginname', 'local_profilefield_autofill'));
$PAGE->set_heading(get_string('pluginname', 'local_profilefield_autofill'));

// Remove breadcrumb navigation for cleaner interface.
$PAGE->navbar->ignore_active();
$PAGE->set_pagelayout('admin');

// Scope for this plugin's stylesheet. Set explicitly rather than relying on the
// body class Moodle derives from the pagetype: admin_externalpage_setup() above
// prefixes the pagetype with 'admin-', so the derived class is
// path-admin-local-profilefield_autofill, which is easy to get wrong and would
// change if this page stopped being an admin external page.
$PAGE->add_body_class('local-profilefield-autofill');

// Add custom CSS for grouped view.
$PAGE->requires->css('/local/profilefield_autofill/styles.css');

// No Bootstrap module is requested here on purpose. The grouped view's collapse
// toggles are driven by Bootstrap's data-api, which the theme already loads site
// wide; there is no core/bootstrap AMD module to require (Bootstrap ships under
// theme_boost/bootstrap/), so asking for one only raises a RequireJS error.

// Handle actions.
if ($action === 'delete' && $id > 0) {
    require_sesskey();

    // Preserve the grouped view parameter.
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);

    if (\local_profilefield_autofill\helper::delete_mapping($id)) {
        redirect(
            $redirecturl,
            get_string('mappingdeleted', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            $redirecturl,
            get_string('invaliddata', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Handle bulk actions.
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
        redirect(
            $redirecturl,
            get_string('invaliddata', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
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
        redirect(
            $redirecturl,
            get_string('bulkdeleted', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            $redirecturl,
            get_string('invaliddata', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

if ($action === 'toggle' && $id > 0) {
    require_sesskey();

    // Preserve the grouped view parameter.
    $grouped = optional_param('grouped', 0, PARAM_INT);
    $redirecturl = new moodle_url($PAGE->url, $grouped ? ['grouped' => 1] : []);

    if (\local_profilefield_autofill\helper::toggle_mapping_status($id)) {
        redirect(
            $redirecturl,
            get_string('mappingsaved', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect(
            $redirecturl,
            get_string('invaliddata', 'local_profilefield_autofill'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Handle CSV import.
if ($action === 'import') {
    $formurl = new moodle_url('/local/profilefield_autofill/manage.php', ['action' => 'import']);
    $form = new local_profilefield_autofill_import_form($formurl);

    if ($form->is_cancelled()) {
        redirect($PAGE->url);
    }

    if ($data = $form->get_data()) {
        try {
            // Get uploaded file content.
            $content = $form->get_file_content('csvfile');

            if (empty($content)) {
                throw new Exception(get_string('csvfileempty', 'local_profilefield_autofill'));
            }

            // Parse CSV.
            $csvdata = \local_profilefield_autofill\helper::parse_csv_content(
                $content,
                $data->delimiter,
                $data->encoding,
                !empty($data->hasheader)
            );

            if (empty($csvdata)) {
                throw new Exception(get_string('csvnodata', 'local_profilefield_autofill'));
            }

            // Import mappings.
            $options = [
                'updateexisting' => !empty($data->updateexisting),
                'enableimported' => !empty($data->enableimported),
            ];

            $results = \local_profilefield_autofill\helper::import_mappings_from_csv($csvdata, $options);

            // Prepare success message with details.
            $message = get_string('csvimportcomplete', 'local_profilefield_autofill', $results);

            // Add skipped items to message if any.
            if (!empty($results['skipped_items'])) {
                $skippedmsg = html_writer::tag('h5', get_string('csvskippeditems', 'local_profilefield_autofill'));
                $skippedmsg .= html_writer::alist($results['skipped_items']);
                $message .= html_writer::div($skippedmsg, 'alert alert-info mt-2');
            }

            redirect($PAGE->url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            redirect(
                $PAGE->url,
                get_string('errorimportcsv', 'local_profilefield_autofill', $e->getMessage()),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('csvimport', 'local_profilefield_autofill'));

    echo html_writer::div(get_string('csvimporthelp', 'local_profilefield_autofill'), 'alert alert-info');

    // Add download template link.
    $templateurl = new moodle_url('/local/profilefield_autofill/manage.php', ['action' => 'template']);
    echo html_writer::div(
        html_writer::link(
            $templateurl,
            get_string('downloadtemplate', 'local_profilefield_autofill'),
            ['class' => 'btn btn-secondary btn-sm']
        ),
        'mb-3'
    );

    $form->display();

    echo $OUTPUT->footer();
    exit;
}

// Handle CSV template download.
if ($action === 'template') {
    $columns = \local_profilefield_autofill\helper::get_csv_columns();

    // Downloads go through core's dataformat writer rather than hand-rolled
    // header() and echo calls, so quoting and content headers are core's problem.
    // download_data() sends the response and exits.
    \core\dataformat::download_data(
        'profilefield_mappings_template',
        'csv',
        array_combine($columns, $columns),
        \local_profilefield_autofill\helper::get_csv_template_rows()
    );
}

// Handle CSV export.
if ($action === 'export') {
    $mappings = \local_profilefield_autofill\helper::get_all_mappings();
    $columns = \local_profilefield_autofill\helper::get_csv_columns();

    $rows = [];
    foreach ($mappings as $mapping) {
        $row = [];
        foreach ($columns as $column) {
            // Administrator-authored values, but neutralise anything a
            // spreadsheet would evaluate as a formula on reopening the file.
            $row[$column] = \local_profilefield_autofill\helper::escape_csv_formula($mapping->$column);
        }
        $rows[] = $row;
    }

    \core\dataformat::download_data(
        'profilefield_mappings_export_' . date('Y-m-d'),
        'csv',
        array_combine($columns, $columns),
        $rows
    );
}

// Handle form for adding/editing mappings.
if ($action === 'add' || ($action === 'edit' && $id > 0)) {
    $mapping = null;
    if ($action === 'edit') {
        $mapping = \local_profilefield_autofill\helper::get_mapping($id);
        if (!$mapping) {
            redirect(
                $PAGE->url,
                get_string('invaliddata', 'local_profilefield_autofill'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }

    // Set the form action URL explicitly.
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
            // Preserve the grouped view parameter if it was set.
            $returnurl = optional_param('returnurl', '', PARAM_URL);
            $redirecturl = $returnurl ? new moodle_url($returnurl) : $PAGE->url;

            if ($mappingid) {
                redirect(
                    $redirecturl,
                    get_string('mappingsaved', 'local_profilefield_autofill'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    $redirecturl,
                    get_string('invaliddata', 'local_profilefield_autofill'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        } catch (Exception $e) {
            $returnurl = optional_param('returnurl', '', PARAM_URL);
            $redirecturl = $returnurl ? new moodle_url($returnurl) : $PAGE->url;
            redirect(
                $redirecturl,
                get_string('errorsavemapping', 'local_profilefield_autofill', $e->getMessage()),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }

    echo $OUTPUT->header();

    $title = $action === 'add'
        ? get_string('addmapping', 'local_profilefield_autofill')
        : get_string('editmapping', 'local_profilefield_autofill');
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

// Add action buttons.
$addurl = new moodle_url($PAGE->url, ['action' => 'add']);
$importurl = new moodle_url($PAGE->url, ['action' => 'import']);
$exporturl = new moodle_url($PAGE->url, ['action' => 'export']);

echo html_writer::start_div('mb-3');
echo html_writer::link(
    $addurl,
    get_string('addmapping', 'local_profilefield_autofill'),
    ['class' => 'btn btn-primary mr-2']
);
echo html_writer::link(
    $importurl,
    get_string('importmappings', 'local_profilefield_autofill'),
    ['class' => 'btn btn-secondary mr-2']
);
echo html_writer::link(
    $exporturl,
    get_string('exportmappings', 'local_profilefield_autofill'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();

// Get view preference.
$grouped = optional_param('grouped', 0, PARAM_INT);

// Get all mappings.
$mappings = \local_profilefield_autofill\helper::get_all_mappings();

if (empty($mappings)) {
    echo html_writer::div(get_string('nomappings', 'local_profilefield_autofill'), 'alert alert-warning');
} else {
    // View toggle controls and search.
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');

    // View toggle buttons.
    echo html_writer::start_div('btn-group btn-group-sm');

    $listclass = !$grouped ? 'btn-primary' : 'btn-outline-primary';
    $listurl = new moodle_url($PAGE->url);
    echo html_writer::link(
        $listurl,
        get_string('listview', 'local_profilefield_autofill'),
        ['class' => 'btn ' . $listclass]
    );

    $groupclass = $grouped ? 'btn-primary' : 'btn-outline-primary';
    $groupurl = new moodle_url($PAGE->url, ['grouped' => 1]);
    echo html_writer::link(
        $groupurl,
        get_string('groupview', 'local_profilefield_autofill'),
        ['class' => 'btn ' . $groupclass]
    );

    echo html_writer::end_div();

    // Search input.
    echo html_writer::start_div('input-group input-group-sm profilefield-search-group');
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'class' => 'form-control',
        'id' => 'mapping-search',
        'placeholder' => get_string('searchmappings', 'local_profilefield_autofill'),
        'aria-label' => get_string('searchmappings', 'local_profilefield_autofill'),
    ]);
    echo html_writer::start_div('input-group-append');
    echo html_writer::tag(
        'span',
        html_writer::tag('i', '', ['class' => 'fa fa-search']),
        ['class' => 'input-group-text']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();

    if ($grouped) {
        // Display grouped view.
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
            // Preserve current view state for return URLs.
            $currentparams = $grouped ? ['grouped' => 1] : [];
            $currenturl = new moodle_url($PAGE->url, $currentparams);

            $editurl = new moodle_url(
                $PAGE->url,
                ['action' => 'edit', 'id' => $mapping->id, 'returnurl' => $currenturl->out(false)]
            );
            $actions[] = html_writer::link(
                $editurl,
                html_writer::tag('i', '', ['class' => 'fa fa-cog', 'aria-hidden' => 'true']),
                ['class' => 'action-icon', 'title' => get_string('edit', 'local_profilefield_autofill')]
            );

            // Toggle status link with eye icon.
            $toggletext = $mapping->enabled
                ? get_string('disable', 'local_profilefield_autofill')
                : get_string('enable', 'local_profilefield_autofill');
            $toggleicon = $mapping->enabled ? 'fa-eye-slash' : 'fa-eye';
            $toggleparams = array_merge(['action' => 'toggle', 'id' => $mapping->id, 'sesskey' => sesskey()], $currentparams);
            $toggleurl = new moodle_url($PAGE->url, $toggleparams);
            $actions[] = html_writer::link(
                $toggleurl,
                html_writer::tag('i', '', ['class' => 'fa ' . $toggleicon, 'aria-hidden' => 'true']),
                ['class' => 'action-icon', 'title' => $toggletext]
            );

            // Delete link with trash icon.
            $deleteparams = array_merge(['action' => 'delete', 'id' => $mapping->id, 'sesskey' => sesskey()], $currentparams);
            $deleteurl = new moodle_url($PAGE->url, $deleteparams);
            $deleteconfirm = get_string('confirmdeletemapping', 'local_profilefield_autofill');
            $actions[] = html_writer::link(
                $deleteurl,
                html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true']),
                ['class' => 'action-icon', 'title' => get_string('delete', 'local_profilefield_autofill'),
                    'onclick' => "return confirm('$deleteconfirm');"]
            );

            $row[] = implode(' ', $actions);

            $table->data[] = $row;
        }

        echo html_writer::table($table);
    }
}

// Add search functionality using AMD.
$PAGE->requires->js_call_amd('local_profilefield_autofill/search_handler', 'init');

echo $OUTPUT->footer();
