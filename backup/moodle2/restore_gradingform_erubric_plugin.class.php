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
 * Learning Analytics Enriched Rubric (e-rubric) - Restore
 *
 * Restores the learning analytics enriched rubric specific data from grading.xml file.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class contains all necessary definitions needed for a successful restore of all e-rubric data.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_gradingform_erubric_plugin extends restore_gradingform_plugin {

    /**
     * Declares the enriched rubric XML paths attached to the form definition element.
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_definition_plugin_structure() {

        $paths = array();

        $paths[] = new restore_path_element('gradingform_erubric_criterion',
            $this->get_pathfor('/enrichedcriteria/enrichedcriterion'));

        $paths[] = new restore_path_element('gradingform_erubric_level',
            $this->get_pathfor('/enrichedcriteria/enrichedcriterion/enrichedlevels/enrichedlevel'));

        return $paths;
    }

    /**
     * Declares the enriched rubric XML paths attached to the form instance element.
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_instance_plugin_structure() {

        $paths = array();

        $paths[] = new restore_path_element('gradinform_erubric_filling',
            $this->get_pathfor('/enrichedfillings/enrichedfilling'));

        return $paths;
    }

    /**
     * Processes criterion element data.
     *
     * Sets the mapping 'gradingform_erubric_criterion' to be used later by
     * {@link self::process_gradinform_erubric_filling()}
     *
     * @param stdClass|array $data
     */
    public function process_gradingform_erubric_criterion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->definitionid = $this->get_new_parentid('grading_definition');

        $newid = $DB->insert_record('gradingform_erubric_criteria', $data);
        $this->set_mapping('gradingform_erubric_criterion', $oldid, $newid);
    }

    /**
     * Processes level element data.
     *
     * Sets the mapping 'gradingform_erubric_level' to be used later by
     * {@link self::process_gradinform_erubric_filling()}
     *
     * @param stdClass|array $data
     */
    public function process_gradingform_erubric_level($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->criterionid = $this->get_new_parentid('gradingform_erubric_criterion');

        $newid = $DB->insert_record('gradingform_erubric_levels', $data);
        $this->set_mapping('gradingform_erubric_level', $oldid, $newid);
    }

    /**
     * Processes filling element data.
     *
     * @param stdClass|array $data
     */
    public function process_gradinform_erubric_filling($data) {
        global $DB;

        $data = (object)$data;
        $data->instanceid = $this->get_new_parentid('grading_instance');
        $data->criterionid = $this->get_mappingid('gradingform_erubric_criterion', $data->criterionid);
        $data->levelid = $this->get_mappingid('gradingform_erubric_level', $data->levelid);

        if (!empty($data->criterionid)) {
            $DB->insert_record('gradingform_erubric_fillings', $data);
        }
    }
}