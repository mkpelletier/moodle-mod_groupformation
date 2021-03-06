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
 * Basic grouping interface
 *
 * @package mod_groupformation
 * @author Eduard Gallwas, Johannes Konert, Rene Roepke, Nora Wester, Ahmed Zukic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die ('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/mod/groupformation/lib.php');
require_once($CFG->dirroot . '/mod/groupformation/locallib.php');
require_once($CFG->dirroot . '/mod/groupformation/classes/grouping/grouping.php');

class mod_groupformation_topic_grouping extends mod_groupformation_grouping {

    private $groupformationid;
    private $usermanager;
    private $store;
    private $groupsmanager;
    private $criterioncalculator;

    /**
     * mod_groupformation_job_manager constructor.
     * @param $groupformationid
     */
    public function __construct($groupformationid) {
        $this->groupformationid = $groupformationid;
        $this->usermanager = new mod_groupformation_user_manager($groupformationid);
        $this->store = new mod_groupformation_storage_manager($groupformationid);
        $this->groupsmanager = new mod_groupformation_groups_manager($groupformationid);
        $this->criterioncalculator = new mod_groupformation_criterion_calculator($groupformationid);
        $this->participantparser = new mod_groupformation_participant_parser($groupformationid);
    }

    /**
     * Scientific division of users and creation of participants
     *
     * @param $users Two parted array - first part is all groupal users, second part are all random users
     * @return array
     */
    public function run_grouping($users) {

        if (count($users[0]) == 0) {
            return array();
        }

        $groupsizes = $this->store->determine_group_size($users, $this->groupformationid);
        ksort($groupsizes);

        $cohorts = array();

        mod_groupformation_group::set_group_members_max_size(max($groupsizes));

        $configurationkey = "topic:1";

        $rawparticipants = $this->participantparser->build_topic_participants($users[0]);

        $participants = $rawparticipants;
        $randomparticipants = $this->participantparser->build_empty_participants($users[1]);

        $cohort = $this->build_cohort($participants, $groupsizes, $configurationkey);

        if (!is_null($cohort)) {
            $size = ceil((count($users [0]) + count($users [1])) / count($cohort->groups));
            mod_groupformation_group::set_group_members_max_size($size);

            $max = null;
            foreach ($cohort->groups as $group) {
                $value = count($group->get_participants());
                $groups [] = array(
                    'id' => $group->get_id(), 'count' => $value, 'group' => $group, 'participants' => array());
                if ($max == null || $max < $value) {
                    $max = $value;
                }
            }
            usort($groups, function ($a, $b) {
                return $a ['count'] - $b ['count'];
            });
            $groups = array_slice($groups, 0, count($groups));
            for ($i = 0; $i < count($randomparticipants); $i++) {
                usort($groups, function ($a, $b) {
                    return $a ['count'] - $b ['count'];
                });
                $groups = array_slice($groups, 0, count($groups));

                $p = $randomparticipants [$i];
                $groups [0] ['group']->add_participant($p, true);
                $groups [0] ['count']++;
            }

            usort($groups, function ($a, $b) {
                return $a ['count'] - $b ['count'];
            });

            $cohorts[$configurationkey] = $cohort;
        } else {
            $configurationkey = 'random:1';

            // Pure random groups because no answers.
            $max = max($groupsizes);

            $cohorts[$configurationkey] = $this->build_random_cohort($randomparticipants, $max);
        }

        return $cohorts;
    }

}