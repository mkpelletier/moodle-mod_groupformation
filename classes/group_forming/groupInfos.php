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
 *
 *
 * @package mod_groupformation
 * @author Nora Wester
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//TODO noch nicht getestet
//defined('MOODLE_INTERNAL') || die();  -> template
//namespace mod_groupformation\classes\lecturer_settings;

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//require_once 'storage_manager.php';
require_once($CFG->dirroot.'/mod/groupformation/classes/moodle_interface/storage_manager_groups.php');


class mod_groupformation_groupInfos {

	private $store;
	private $groupformationid;

	
	/**
	 *
	 * @param unknown $groupformationid
	*/
	public function __construct($groupformationid){
		$this->groupformationid = $groupformationid;
		$this->store = new mod_groupformation_storage_manager_groups($groupformationid);
	}
	
	public function render($userid){
		$id = $this->store->haveGroup($userid);
		if ( $id == -1 ){
			echo '<h1> Die Gruppenbildung ist noch nicht abgeschlossen </h1>';
		}else{
			echo 'Deine Gruppennummer ist ' . $id . '<br>';
			//@Ren� hier m�ssen die Daten f�r Moodlegruppen id und Gruppenmitglieder herausgeholte werden
			$otherMembers = $this->store->getGroupMembers($userid);
			echo 'Deine Arbeitskollegen sind: <br>';
			foreach ( $otherMembers as $memberid ){
				$member = get_complete_user_data('id', $memberid);
				if (!$member) {
					echo 'user does not exist!';
				}
				echo $member->firstname . ' ' . $member->lastname;
				//var_dump($member);
			}
		}
	}
}