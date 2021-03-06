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
 * Prints a particular instance of groupformation
 *
 * @package mod_groupformation
 * @author Eduard Gallwas, Johannes Konert, Rene Roepke, Nora Wester, Ahmed Zukic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/classes/util/define_file.php');
require_once(dirname(__FILE__) . '/classes/moodle_interface/storage_manager.php');
require_once(dirname(__FILE__) . '/classes/moodle_interface/user_manager.php');
require_once(dirname(__FILE__) . '/classes/controller/questionnaire_controller.php');

// Read URL params.
$id = optional_param('id', 0, PARAM_INT);

// TODO: after fixing db issue, change param to url?
$urlcategory = optional_param('category', '', PARAM_TEXT);

// Import jQuery and js file.
groupformation_add_jquery($PAGE, 'survey_functions.js');

// Determine instances of course module, course, groupformation.
groupformation_determine_instance($id, $cm, $course, $groupformation);

// Require user login if not already logged in.
require_login($course, true, $cm);

// Get useful stuff.
$context = $PAGE->context;
$userid = $USER->id;

$data = new mod_groupformation_data ();
$store = new mod_groupformation_storage_manager ($groupformation->id);
$usermanager = new mod_groupformation_user_manager ($groupformation->id);
$groupsmanager = new mod_groupformation_groups_manager ($groupformation->id);

$scenario = $store->get_scenario();
$names = $store->get_categories();


if (!has_capability('mod/groupformation:editsettings', $context)) {
    $currenttab = 'answering';
    // Log access to page.
    groupformation_info($USER->id, $groupformation->id, '<view_student_questionnaire>');
} else {
    $currenttab = 'view';
    // Log access to page.
    groupformation_info($USER->id, $groupformation->id, '<view_teacher_questionnaire_preview>');
}


if (data_submitted() && confirm_sesskey()) {
    $category = optional_param('category', null, PARAM_ALPHA);
    $direction = optional_param('direction', null, PARAM_BOOL);
    $percent = optional_param('percent', null, PARAM_INT);
    $action = optional_param('action', null, PARAM_BOOL);
}

if (!isset ($category)) {
    $category = $store->get_previous_category($urlcategory);
}

if (!isset ($direction)) {
    $direction = 1;
}


// Set PAGE config.
$PAGE->set_url('/mod/groupformation/questionnaire_view.php', array(
    'id' => $cm->id));
$PAGE->set_title(format_string($groupformation->name));
$PAGE->set_heading(format_string($course->fullname));

$consent = $usermanager->get_consent($userid);
$participantcode = $usermanager->has_participant_code($userid) || !$data->ask_for_participant_code();

if (
    (!$consent && !$participantcode && !$groupsmanager->groups_created()) &&
    !has_capability('mod/groupformation:editsettings', $context)) {
    $returnurl = new moodle_url ('/mod/groupformation/view.php', array(
        'id' => $cm->id, 'giveconsent' => '1', 'giveparticipantcode' => '1'));
    redirect($returnurl);
}

if ((!$consent && !$groupsmanager->groups_created()) && !has_capability('mod/groupformation:editsettings', $context)) {
    $returnurl = new moodle_url ('/mod/groupformation/view.php', array(
        'id' => $cm->id, 'giveconsent' => '1'));
    redirect($returnurl);
}

if ((!$participantcode && !$groupsmanager->groups_created()) && !has_capability('mod/groupformation:editsettings', $context)) {
    $returnurl = new moodle_url ('/mod/groupformation/view.php', array(
        'id' => $cm->id, 'giveparticipantcode' => '1'));
    redirect($returnurl);
}

$inarray = in_array($category, $names);
$go = true;
$controller = new mod_groupformation_questionnaire_controller($groupformation->id,
    get_string('language',
        'groupformation'),
    $userid, $category, $cm->id);

if (has_capability('mod/groupformation:onlystudent', $context) &&
    !has_capability('mod/groupformation:editsettings', $context) &&
    (data_submitted() && confirm_sesskey())
) {
    $status = $usermanager->get_answering_status($userid);
    if ($status == 0 || $status == -1) {
        if ($inarray) {
            $go = $controller->save_answers($category);
        }
    }
}


if ($direction == 0 && $percent == 0) {
    $returnurl = new moodle_url ('/mod/groupformation/view.php', array(
        'id' => $cm->id, 'back' => '1'));
    redirect($returnurl);
}

$available = $store->is_questionnaire_available() || $store->is_questionnaire_accessible();
$isteacher = has_capability('mod/groupformation:editsettings', $context);
if (($available || $isteacher) && ($category == '' || $inarray)) {

    echo $OUTPUT->header();


    // Print the tabs.
    require('tabs.php');

    if (groupformation_get_current_questionnaire_version() > $store->get_version()) {
        echo '<div class="alert">' . get_string('questionnaire_outdated', 'groupformation') . '</div>';
    }
    if ($store->is_archived() && !has_capability('mod/groupformation:editsettings', $context)) {
        echo '<div class="alert" id="commited_view">' . get_string('archived_activity_answers', 'groupformation') .
            '</div>';
    } else if ($store->is_archived() && has_capability('mod/groupformation:editsettings', $context)) {
        echo '<div class="alert" id="commited_view">' . get_string('archived_activity_admin', 'groupformation') .
            '</div>';
    } else {
        if ($direction == 0) {
            $controller->go_back();
        } else if (!$go) {
            $controller->not_go_on();
        }

        $controller->print_page();
    }
} else if (!$available || $category == 'no') {

    if (isset ($action) && $action == 1) {
        $usermanager->change_status($userid);
    }

    $returnurl = new moodle_url ('/mod/groupformation/view.php', array(
        'id' => $cm->id, 'do_show' => 'view', 'back' => '1'));
    redirect($returnurl);
} else {

    echo $OUTPUT->heading('Category has been manipulated');
}


echo $OUTPUT->footer();

