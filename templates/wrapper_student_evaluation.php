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
 * @package mod_groupformation
 * @author Eduard Gallwas, Johannes Konert, Rene Roepke, Nora Wester, Ahmed Zukic
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>
<div class="gf_settings_pad">
	<link href="js/bootstrap.min.css" rel="stylesheet">
    <div class="gf_pad_header"><?php echo get_string('evaluation', 'groupformation'); ?></div>
    <div class="gf_pad_content">
        <?php if ($this->_['eval_show_text']): ?>
            <?php echo $this->_['eval_text']; ?>
        <?php endif; ?>
        <div id="json-content" style="display:none;"><?php echo $this->_['json_content']; ?>
        </div>



        <!-- adding chart -->
        <div id="gf_chart"></div>

		<!-- adding modal -->
		<div id="gf-modal" class="modal fade bs-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
		</div>

		<!-- panel-group for Information -->
		<div class="panel-group" id="gf-accordion" role="tablist" aria-multiselectable="true"></div>


  </div>
</div>

    </div>
</div>
