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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * Prints a particular instance of groupformation
 *
 * @package mod_groupformation
 * @author Nora Wester,
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

	require_once (dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php');
	require_once (dirname ( __FILE__ ) . '/lib.php');
	require_once (dirname ( __FILE__ ) . '/locallib.php');

	// Read URL params
	$id = optional_param ( 'id', 0, PARAM_INT ); // Course Module ID
// 	$g = optional_param ( 'g', 0, PARAM_INT ); // groupformation instance ID
	$do_show = optional_param('do_show', 'group', PARAM_TEXT);
	
	
	// Import jQuery and js file
	groupformation_add_jquery ( $PAGE, 'survey_functions.js' );
	
	// Determine instances of course module, course, groupformation
	groupformation_determine_instance($id, $cm, $course, $groupformation);
		
	// Require user login if not already logged in
	require_login ( $course, true, $cm );
	
	// Get useful stuff
	$context = $PAGE->context;
	$userid = $USER->id;
	
	if (has_capability('mod/groupformation:editsettings', $context)){
		$returnurl = new moodle_url('/mod/groupformation/analysisView.php', array('id' => $id, 'do_show' => 'analysis'));
		redirect($returnurl);
	}else{
		$current_tab = $do_show;
	}	
	
	// Log access to page
	groupformation_log($USER->id,$groupformation->id,'<view_student_group_assignment>');
		
	// Trigger event TODO @Nora why?
	groupformation_trigger_event($cm, $course, $groupformation, $context);

	// Set PAGE config
	$PAGE->set_url ( '/mod/groupformation/evaluationView.php', array ('id' => $cm->id, 'do_show' => $do_show ) );
	$PAGE->set_title ( format_string ( $groupformation->name ) );
	$PAGE->set_heading ( format_string ( $course->fullname ) );
	
	echo $OUTPUT->header ();
	
	// Print the tabs.
	require ('tabs.php');
	
	// Conditions to show the intro can change to look for own settings or whatever.
	if ($groupformation->intro) {
		echo $OUTPUT->box ( format_module_intro ( 'groupformation', $groupformation, $cm->id ), 'generalbox mod_introbox', 'groupformationintro' );
	}
	
	// Replace the following lines with you own code.
	//echo $OUTPUT->heading ( $groupformation->name );
	
	echo '<div style="color:red;">Diese Seite ist noch in der Entwicklung. Die Inhalte sind ggf. noch rein statisch und haben keinen Effekt oder keine Funktion</div>';
	
	echo $OUTPUT->footer ();
