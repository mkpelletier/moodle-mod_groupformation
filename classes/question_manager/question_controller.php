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
 * Question controller
 *
 * @package mod_groupformation
 * @author Eduard Gallwas, Johannes Konert, René Röpke, Neora Wester, Ahmed Zukic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *         
 */
// require_once(dirname(__FILE__).'/storage_manager.php');
// require_once(dirname(__FILE__).'/xml_loader.php');
require_once ($CFG->dirroot . '/mod/groupformation/classes/moodle_interface/storage_manager.php');
require_once ($CFG->dirroot . '/mod/groupformation/classes/util/xml_loader.php');
require_once ($CFG->dirroot . '/mod/groupformation/classes/util/define_file.php');

if (! defined ( 'MOODLE_INTERNAL' )) {
	die ( 'Direct access to this script is forbidden.' ); // / It must be included from a Moodle page
}
class mod_groupformation_question_controller {
	private $SAVE = 0;
	private $COMMIT = 1;
	private $status;
	private $numbers = array ();
	private $names = array ();
	private $groupformationid;
	private $store;
	private $xml;
	private $scenario;
	private $lang;
	private $userId;
	private $currentCategoryPosition = 0;
	private $numberOfCategory;
	private $data;
	private $hasAnswer;
	
	/**
	 * Constructs an instance of question controller
	 *
	 * @param int $groupformationid        	
	 * @param string $lang        	
	 * @param int $userId        	
	 * @param string $oldCategory        	
	 */
	public function __construct($groupformationid, $lang, $userId, $oldCategory) {
		$this->groupformationid = $groupformationid;
		$this->lang = $lang;
		$this->userId = $userId;
		$this->store = new mod_groupformation_storage_manager ( $groupformationid );
		$this->xml = new mod_groupformation_xml_loader ();
		$this->data = new mod_groupformation_data ();
		$this->scenario = $this->store->getScenario ();
		$this->names = $this->store->getCategories ();
		$this->numberOfCategory = count ( $this->names );
		$this->init ( $userId );
		$this->setIternalNumber ( $oldCategory );
	}
	
	/**
	 * Returns where questionnaire has been submitted or not
	 *
	 * @return boolean
	 */
	public function hasCommited() {
		$status = $this->store->answeringStatus ( $this->userId );
		return $status == 1;
	}
	
	// --- Mathevorkurs
	// public function goNotOn(){
	// $this->goIternalBack(1);
	// }
	public function hasAllAnswered() {
		return $this->store->hasAnsweredEverything ( $this->userId );
	}
	// ---
	public function goBack() {
		$this->goIternalBack ( 2 );
	}
	private function goIternalBack($back) {
		while ( $back > 0 && $this->currentCategoryPosition != 0 ) {
			if ($this->numbers [$this->currentCategoryPosition] != 0) {
				$back = $back - 1;
			}
			$this->currentCategoryPosition = $this->currentCategoryPosition - 1;
		}
	}
	public function getPercent($category = null) {
		if (! is_null ( $category )) {
			$categories = $this->store->getCategories ();
			$pos = array_search ( $category, $categories );
			return 100.0 * ((1.0 * $pos) / count ( $categories ));
		}
		
		$total = 0;
		$sub = 0;
		
		$temp = 0;
		
		foreach ( $this->numbers as $num ) {
			if ($num != 0) {
				$total ++;
				if ($temp < $this->currentCategoryPosition) {
					$sub ++;
				}
			}
			
			$temp ++;
		}
		
		return ($sub / $total) * 100;
	}
	private function init($userId) {
		if (! $this->store->catalogTableNotSet ()) {
			$this->numbers = $this->store->getNumbers ( $this->names );
			// $this->setNulls();
		}
		
		$this->status = $this->store->answeringStatus ( $userId );
	}
	private function setIternalNumber($category) {
		if ($category != "") {
			$this->currentCategoryPosition = $this->store->getPosition ( $category );
			$this->currentCategoryPosition ++;
		}
	}
	private function setNulls() {
		if ($this->scenario == 'project' || $this->scenario == 1) {
			$this->numbers [$this->store->getPosition ( 'learning' )] = 0;
		}
		
		if ($this->scenario == 'homework' || $this->scenario == 2) {
			$this->numbers [$this->store->getPosition ( 'motivation' )] = 0;
		}
		
		if ($this->scenario == 'presentation' || $this->scenario == 3) {
			for($i = 0; $i < count ( $this->numbers ); $i ++) {
				if ($i != $this->store->getPosition ( 'topic' ) && $i != $this->store->getPosition ( 'general' )) {
					$this->numbers [$i] = 0;
				}
			}
		}
	}
	
	/**
	 * Returns whether there is a next category or not
	 *
	 * @return boolean
	 */
	public function hasNext() {
		if ($this->currentCategoryPosition >= 0 && $this->currentCategoryPosition < $this->numberOfCategory) {
			while ( $this->currentCategoryPosition < $this->numberOfCategory && $this->numbers [$this->currentCategoryPosition] == 0 ) {
				$this->currentCategoryPosition ++;
			}
		}
		return ($this->currentCategoryPosition != - 1 && $this->currentCategoryPosition < $this->numberOfCategory);
	}
	
	/**
	 * Returns question in current language or possible default language
	 *
	 * @param int $i        	
	 * @return stdClass
	 */
	public function getQuestion($i) {
		$record = $this->store->getCatalogQuestion ( $i, $this->names [$this->currentCategoryPosition], $this->lang );
		
		if (empty ( $record )) {
			if ($this->lang != 'en') {
				$record = $this->store->getCatalogQuestion ( $i, $this->names [$this->currentCategoryPosition], 'en' );
			} else {
				$lang = $this->store->getPossibleLang ( $this->names [$this->currentCategoryPosition] );
				$record = $this->store->getCatalogQuestion ( $i, $this->names [$this->currentCategoryPosition], $lang );
			}
		}
		
		return $record;
	}
	/**
	 * Returns whether current category is 'topic' or not
	 *
	 * @return boolean
	 */
	public function isTopics() {
		return $this->currentCategoryPosition == $this->store->getPosition ( 'topic' );
	}
	
	/**
	 * Returns whether current category is 'knowledge' or not
	 *
	 * @return boolean
	 */
	public function isKnowledge() {
		return $this->currentCategoryPosition == $this->store->getPosition ( 'knowledge' );
	}
	
	/**
	 * Returns whether current category is 'points' or not
	 *
	 * @return boolean
	 */
	public function isPoints() {
		return $this->currentCategoryPosition == $this->store->getPosition ( 'points' );
	}
	
	/**
	 * Returns questions
	 *
	 * @return array
	 */
	public function getNextQuestions() {
		if ($this->currentCategoryPosition != - 1) {
			
			$questions = array ();
			
			$this->hasAnswer = $this->hasAnswers ();
			
			if ($this->isKnowledge () || $this->isTopics ()) {
				
				$temp = $this->store->getKnowledgeOrTopicValues ( $this->names [$this->currentCategoryPosition] );
				$values = $this->xml->xmlToArray ( '<?xml version="1.0" encoding="UTF-8" ?> <OPTIONS> ' . $temp . ' </OPTIONS>' );
				
				$text = '';
				
				$type;
				
				if ($this->isTopics ()) {
					$type = 'type_topics';
				} else {
					$type = 'type_knowledge';
				}
				
				$options = $options = array (
						100 => get_string ( 'excellent', 'groupformation' ),
						0 => get_string ( 'none', 'groupformation' ) 
				);
				
				$position = 1;
				$questionsfirst = array ();
				$answerPosition = array ();
				
				foreach ( $values as $value ) {
					$question = array ();
					$question [] = $type;
					$question [] = $text . $value;
					$question [] = $options;
					if ($this->hasAnswer) {
						$answer = $this->store->getSingleAnswer ( $this->userId, $this->names [$this->currentCategoryPosition], $position );
						if ($answer != false) {
							$question [] = $answer;
						} else {
							$question [] = - 1;
						}
						$answerPosition [$answer] = $position - 1;
						$position ++;
					}
					
					$questionsfirst [] = $question;
				}
				
				$l = count ( $answerPosition );
				
				if ($l > 0 && $this->currentCategoryPosition == $this->store->getPosition ( 'topic' )) {
					for($k = 1; $k <= $l; $k ++) {
						$h = $questionsfirst [$answerPosition [$k]];
						$h [] = $answerPosition [$k];
						$questions [] = $h;
					}
				} else {
					$questions = $questionsfirst;
				}
			} elseif ($this->isPoints ()) {
				for($i = 1; $i <= $this->numbers [$this->currentCategoryPosition]; $i ++) {
					$record = $this->getQuestion ( $i );
					
					$question = array ();
					
					if (count ( $record ) == 0) {
						echo '<div class="alert">This questionaire site is neither available in your favorite language nor in english!</div>';
						return null;
					} else {
						
						$question [] = 'type_points';
						$question [] = $record->question;
						$question [] = $options = $options = array (
								$this->store->get_max_points() => get_string ( 'excellent', 'groupformation' ),
								0 => get_string ( 'bad', 'groupformation' ) 
						);
						if ($this->hasAnswer) {
							$answer = $this->store->getSingleAnswer ( $this->userId, $this->names [$this->currentCategoryPosition], $i );
							if ($answer != false) {
								$question [] = $answer;
							} else {
								$question [] = - 1;
							}
						}
					}
					
					$questions [] = $question;
				}
			} else {
				// ---------------------------------------------------------------------------------------------------------
				for($i = 1; $i <= $this->numbers [$this->currentCategoryPosition]; $i ++) {
					$record = $this->getQuestion ( $i );
					
					$question = $this->prepareQuestion ( $i, $record );
					
					$questions [] = $question;
				}
				// ---------------------------------------------------------------------------------------------------------
			}
			$this->currentCategoryPosition ++;
			
			return $questions;
		}
	}
	
	/**
	 * Returns question array constructed by question record
	 *
	 * @param unknown $record        	
	 * @return multitype:number NULL multitype:string Ambigous <number, mixed>
	 */
	public function prepareQuestion($i, $record) {
		$question = array ();
		if (count ( $record ) == 0) {
			echo '<div class="alert">This questionaire site is neither available in your favorite language nor in english!</div>';
			return null;
		} else {
			
			$question [] = $record->type;
			$question [] = $record->question;
			$question [] = $this->xml->xmlToArray ( '<?xml version="1.0" encoding="UTF-8" ?> <OPTIONS> ' . $record->options . ' </OPTIONS>' );
			
			if ($this->hasAnswer) {
				$answer = $this->store->getSingleAnswer ( $this->userId, $this->names [$this->currentCategoryPosition], $i );
				if ($answer != false) {
					$question [] = $answer;
				} else {
					$question [] = - 1;
				}
			}
		}
		return $question;
	}
	
	/**
	 * TODO comment
	 *
	 * @param array $answers        	
	 */
	public function saveAnswers(array $answers) {
		$temp = 1;
		foreach ( $answers as $answer ) {
			$this->store->saveAnswer ( $this->userId, $answer, $this->names [$this->currentCategoryPosition - 1], $temp );
			$temp ++;
		}
		
		if ($this->status == - 1) {
			$this->status = $this->SAVE;
			$this->store->statusChanged ( $this->userId );
		}
	}
	
	/**
	 * TODO comment
	 *
	 * @return boolean
	 */
	public function questionsToAnswer() {
		return $this->store->answeringStatus ( $this->userId ) != 1;
	}
	
	/**
	 * TODO comment
	 *
	 * @return boolean
	 */
	public function hasAnswers() {
		$firstCondition = $this->store->answeringStatus ( $this->userId ) > - 1;
		// var_dump($this->names[$this->currentCategoryPosition-1]);
		$second = $this->store->getAnswers ( $this->userId, $this->names [$this->currentCategoryPosition] );
		$secondCondition = $second > 0;
		return ($firstCondition && $secondCondition);
	}
	
	/**
	 * TODO comment
	 *
	 * @return multitype:multitype:NULL
	 */
	public function getAnswers() {
		$array = array ();
		
		$answers = $this->store->getAnswers ( $this->userId, $this->names [$this->currentCategoryPosition] );
		foreach ( $answers as $answer ) {
			$temp = array ();
			$temp [] = $answer->questionid;
			$temp [] = $answer->answer;
			
			$array [] = $temp;
		}
		
		return $array;
	}
	
	/**
	 * TODO comment
	 *
	 * @return multitype:
	 */
	public function getCurrentCategory() {
		return $this->names [$this->currentCategoryPosition];
	}
	
	/**
	 * TODO comment
	 */
	public function commited() {
		$this->store->statusChanged ( $this->userId );
	}
}