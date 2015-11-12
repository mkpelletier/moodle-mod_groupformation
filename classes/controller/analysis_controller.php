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
 * Controller for analysis view
 *
 * @package mod_groupformation
 * @author MoodlePeers
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (! defined ( 'MOODLE_INTERNAL' )) {
	die ( 'Direct access to this script is forbidden.' ); // / It must be included from a Moodle page
}

require_once ($CFG->dirroot . '/mod/groupformation/classes/moodle_interface/storage_manager.php');
require_once ($CFG->dirroot . '/mod/groupformation/classes/moodle_interface/user_manager.php');
require_once ($CFG->dirroot . '/mod/groupformation/classes/util/template_builder.php');

class mod_groupformation_analysis_controller {
	private $groupformationid;
	private $cm;
	private $store = NULL;
	private $user_manager;
	private $view = NULL;
	private $questionnaire_available;
	private $activity_time;
	private $start_time;
	private $end_time;
	private $time_now;
	private $test;
	private $state;
	
	/**
	 * Creates instance of analysis controller
	 *
	 * @param int $groupformationid        	
	 */
	public function __construct($groupformationid,$cm) {
		$this->cm = $cm;
		$this->groupformationid = $groupformationid;

		$this->store = new mod_groupformation_storage_manager ( $groupformationid );
		$this->user_manager = new mod_groupformation_user_manager ( $groupformationid );
		$this->view = new mod_groupformation_template_builder ();
		
		$this->determine_status ();
	}
	
	/**
	 * Sets start time of questionnaire to now
	 */
	public function start_questionnaire() {
		$this->store->open_questionnaire ();
	}
	
	/**
	 * Sets end time of questionnaire to now
	 */
	public function stop_questionnaire() {
		$this->store->close_questionnaire ();
	}
	
	/**
	 * Loads status for template
	 *
	 * @return string
	 */
	private function load_status() {
		$statusAnalysisView = new mod_groupformation_template_builder ();
		$statusAnalysisView->set_template ( 'analysis_status' );
		
		$this->activity_time = $this->store->get_time ();
		
		if (intval ( $this->activity_time ['start_raw'] ) == 0) {
			$this->start_time = get_string ( 'no_time', 'groupformation' );
		} else {
			$this->start_time = $this->activity_time ['start'];
		}
		
		if (intval ( $this->activity_time ['end_raw'] ) == 0) {
			$this->end_time = get_string ( 'no_time', 'groupformation' );
		} else {
			$this->end_time = $this->activity_time ['end'];
		}
		
		$button_name = ($this->questionnaire_available) ? "stop_questionnaire" : "start_questionnaire";
		$button_caption = ($this->questionnaire_available) ? get_string ( 'activity_end', 'groupformation' ) : get_string ( 'activity_start', 'groupformation' );
		$button_disabled = ($this->job_state !== "ready") ? "disabled" : "";
		
		$statusAnalysisView->assign ( 'button', array (
				'type' => 'submit',
				'name' => $button_name,
				'value' => '',
				'state' => $button_disabled,
				'text' => $button_caption 
		) );
		
		$info_teacher = mod_groupformation_util::get_info_text_for_teacher ( false, "analysis" );
		
		$statusAnalysisView->assign ( 'info_teacher', $info_teacher );
		$statusAnalysisView->assign ( 'analysis_time_start', $this->start_time );
		$statusAnalysisView->assign ( 'analysis_time_end', $this->end_time );
		
		switch ($this->state) {
			case 1 :
				$statusAnalysisView->assign ( 'analysis_status_info', get_string ( 'analysis_status_info0', 'groupformation' ) );
				break;
			case 2 :
				$statusAnalysisView->assign ( 'analysis_status_info', get_string ( 'analysis_status_info1', 'groupformation' ) );
				break;
			case 3 :
				$statusAnalysisView->assign ( 'analysis_status_info', get_string ( 'analysis_status_info2', 'groupformation' ) );
				break;
			case 4 :
				$statusAnalysisView->assign ( 'analysis_status_info', get_string ( 'analysis_status_info4', 'groupformation' ) );
				break;
			default :
				$statusAnalysisView->assign ( 'analysis_status_info', get_string ( 'analysis_status_info3', 'groupformation' ) );
		}
		
		return $statusAnalysisView->load_template ();
	}
	
	/**
	 * Loads statistics for template
	 *
	 * @return string
	 */
	private function load_statistics() {
		global $PAGE;
		
		$questionnaire_StatisticNumbers = mod_groupformation_util::get_infos ($this->groupformationid);
		
		$statisticsAnalysisView = new mod_groupformation_template_builder ();
		$statisticsAnalysisView->set_template ( 'analysis_statistics' );
		$context = $PAGE->context;
		$count = count ( get_enrolled_users ( $context, 'mod/groupformation:onlystudent' ) );
		
		$statisticsAnalysisView->assign ( 'statistics_enrolled', $questionnaire_StatisticNumbers [0] );
		$statisticsAnalysisView->assign ( 'statistics_processed', $questionnaire_StatisticNumbers [1] );
		$statisticsAnalysisView->assign ( 'statistics_submited', $questionnaire_StatisticNumbers [2] );
		$statisticsAnalysisView->assign ( 'statistics_submited_incomplete', $questionnaire_StatisticNumbers [4] );
		$statisticsAnalysisView->assign ( 'statistics_submited_complete', $questionnaire_StatisticNumbers [3] );
		
		return $statisticsAnalysisView->load_template ();
	}
	
	/**
	 * Display all templates
	 *
	 * @return string
	 */
	public function display() {
		$this->view->set_template ( 'wrapper_analysis' );
		$this->view->assign ( 'analysis_name', $this->store->get_name () );
		$this->view->assign ( 'analysis_status_template', $this->load_status () );
		$this->view->assign ( 'analysis_statistics_template', $this->load_statistics () );
		return $this->view->load_template ();
	}
	
	/**
	 * Determine status variables
	 */
	public function determine_status() {
		global $DB;
		$this->questionnaire_available = $this->store->is_questionnaire_available ();
		$this->state = 1;
		$job = mod_groupformation_job_manager::get_job ( $this->groupformationid );
		if (is_null($job)){
			$groupingid = ($this->cm->groupmode!=0)?$this->cm->groupingid:0;
			mod_groupformation_job_manager::create_job($this->groupformationid,$groupingid);
			$job = mod_groupformation_job_manager::get_job($this->groupformationid);
		}
		$this->job_state = mod_groupformation_job_manager::get_status ( $job );
		$completed_q = count($this->user_manager->get_completed());
		
		if ($this->job_state !== 'ready') {
			$this->state = 3;
		} elseif ($this->questionnaire_available) {
			$this->state = 1;
		} elseif ($completed_q > 0) {
			$this->state = 4;
		} else {
			$this->state = 2;
		}
	}
}