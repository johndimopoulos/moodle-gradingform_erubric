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
 * Learning Analytics Enriched Rubric (e-rubric) - Gradingform Controller
 *
 * Grading method controller for the Learning Analytics Enriched Rubric plugin.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/lib.php');

/**
 * This controller encapsulates the enriched rubric grading logic.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_erubric_controller extends gradingform_controller {
    // Modes of displaying the enriched rubric (used in gradingform_erubric_renderer).
    /** Enriched rubric display mode: For editing (moderator or teacher creates a rubric) */
    const DISPLAY_EDIT_FULL     = 1;
    /** Enriched rubric display mode: Preview the rubric design with hidden fields */
    const DISPLAY_EDIT_FROZEN   = 2;
    /** Enriched rubric display mode: Preview the rubric design */
    const DISPLAY_PREVIEW       = 3;
    /** Enriched rubric display mode: Preview the rubric (for people being graded) */
    const DISPLAY_PREVIEW_GRADED = 8;
    /** Enriched rubric display mode: For evaluation, enabled (teacher grades a student) */
    const DISPLAY_EVAL          = 4;
    /** Enriched rubric display mode: For evaluation, with hidden fields */
    const DISPLAY_EVAL_FROZEN   = 5;
    /** Enriched rubric display mode: Teacher reviews filled rubric */
    const DISPLAY_REVIEW        = 6;
    /** Enriched rubric display mode: Display filled rubric (i.e. students see their grades) */
    const DISPLAY_VIEW          = 7;

    // Constants used for enriched rubric default field values.
    /** Select student Collaboration for cooperation check (needed in erubriceditor.js). */
    const INTERACTION_TYPE_COLLABORATION   = 1;
    /** Select student Grade for performance check (needed in erubriceditor.js). */
    const INTERACTION_TYPE_GRADE           = 2;
    /** Select student Study for research - study check (needed in erubriceditor.js). */
    const INTERACTION_TYPE_STUDY           = 3;
    /** Select student Collaboration Entries for simple log entries. */
    const COLLABORATION_TYPE_ENTRIES       = 1;
    /** Select student Collaboration File adds for number of files submited. */
    const COLLABORATION_TYPE_FILE_ADDS     = 2;
    /** Select student Collaboration Replies for number of replies to others. */
    const COLLABORATION_TYPE_REPLIES       = 3;
    /** Select student Collaboration Interactions for number of colleagues interacted. */
    const COLLABORATION_TYPE_INTERACTIONS  = 4;
    /** Select query operator for equal. */
    const OPERATOR_EQUAL                   = 1;
    /** Select query operator for more than. */
    const OPERATOR_MORE_THAN               = 2;
    /** Select query reference for particular student. */
    const REFERENCE_STUDENT                = 1;
    /** Select query reference according to students overall percent. */
    const REFERENCE_STUDENTS               = 2;

    /**
     * Extends the module settings navigation with the enriched rubric grading settings.
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING, the user has the permission moodle/grade:managegradingforms
     * and there is an area with the active grading method set to 'erubric'.
     *
     * @param settings_navigation $settingsnav {@link settings_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {

        $node->add(get_string('defineenrichedrubric', 'gradingform_erubric'),
            $this->get_editor_url(), settings_navigation::TYPE_CUSTOM,
            null, null, new pix_icon('icon', '', 'gradingform_erubric'));
    }

    /**
     * Extends the module navigation
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING and there is an area with the active grading method set to the given plugin.
     *
     * @param global_navigation $navigation {@link global_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_navigation(global_navigation $navigation, navigation_node $node=null) {
        if (has_capability('moodle/grade:managegradingforms', $this->get_context())) {
            // No need for preview if user can manage forms, he will have link to manage.php in settings instead.
            return;
        }
        if ($this->is_form_defined() && ($options = $this->get_options()) && !empty($options['alwaysshowdefinition'])) {
            $node->add(get_string('gradingof', 'gradingform_erubric', get_grading_manager($this->get_areaid())->get_area_title()),
                    new moodle_url('/grade/grading/form/'.$this->get_method_name().'/preview.php', array('areaid' => $this->get_areaid())),
                    settings_navigation::TYPE_CUSTOM);
        }
    }

    /**
     * Saves the enriched rubric definition into the database
     *
     * @see parent::update_definition()
     * @param stdClass $newdefinition enriched rubric definition data as coming from gradingform_erubric_editrubric::get_data()
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     */
    public function update_definition(stdClass $newdefinition, $usermodified = null) {
        $this->update_or_check_erubric($newdefinition, $usermodified, true);
        if (isset($newdefinition->erubric['regrade']) && $newdefinition->erubric['regrade']) {
            $this->mark_for_regrade();
        }
    }

    /**
     * Either saves the enriched rubric definition into the database or check if it has been changed.
     * Returns the level of changes:
     * 0 - no changes
     * 1 - only texts or criteria sortorders are changed, students probably do not require re-grading
     * 2 - added levels but maximum score on enriched rubric is the same, students still may not require re-grading
     * 3 - removed criteria or added levels or changed number of points, students require re-grading but may be re-graded automatically
     * 4 - removed levels - students require re-grading and not all students may be re-graded automatically
     * 5 - added criteria or changed enriched criteria or changed enriched level values - all students require manual re-grading
     *
     * @param stdClass $newdefinition enriched rubric definition data as coming from gradingform_erubric_editrubric::get_data()
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     * @param boolean $doupdate if true actually updates DB, otherwise performs a check
     *
     */
    public function update_or_check_erubric(stdClass $newdefinition, $usermodified = null, $doupdate = false) {
        global $DB;

        // Firstly update the common definition data in the {grading_definition} table.
        if ($this->definition === false) {
            if (!$doupdate) {
                // If we create the new definition there is no such thing as re-grading anyway.
                return 5;
            }
            // If definition does not exist yet, create a blank one
            // (we need id to save files embedded in description).
            parent::update_definition(new stdClass(), $usermodified);
            parent::load_definition();
        }
        if (!isset($newdefinition->erubric['options'])) {
            $newdefinition->erubric['options'] = self::get_default_options();
        }
        $newdefinition->options = json_encode($newdefinition->erubric['options']);
        $editoroptions = self::description_form_field_options($this->get_context());
        $newdefinition = file_postupdate_standard_editor($newdefinition, 'description', $editoroptions, $this->get_context(),
            'grading', 'description', $this->definition->id);

        // Reload the definition from the database.
        $currentdefinition = $this->get_definition(true);

        // Update enriched rubric data.
        $haschanges = array();
        // Check if 'lockzeropoints' option has changed.
        $newlockzeropoints = $newdefinition->erubric['options']['lockzeropoints'];
        $currentoptions = $this->get_options();
        if ((bool)$newlockzeropoints != (bool)$currentoptions['lockzeropoints']) {
            $haschanges[3] = true;
        }

        // Update rubric data...
        if (empty($newdefinition->erubric['criteria'])) {
            $newcriteria = array();
        } else {
            $newcriteria = $newdefinition->erubric['criteria']; // New ones to be saved...
        }
        $currentcriteria = $currentdefinition->erubriccriteria;

        // Create tables to use for evaluation of form data.
        $enrichedcriteriafields = array('sortorder', 'description', 'descriptionformat', 'criteriontype', 'collaborationtype', 'operator', 'referencetype');
        $enrichedlevelfields = array('score', 'definition', 'definitionformat', 'enrichedvalue');
        foreach ($newcriteria as $id => $criterion) {
            // Get the list of submitted levels (if they exist).
            $levelsdata = array();
            if (array_key_exists('levels', $criterion)) {
                $levelsdata = $criterion['levels'];
            }
            $criterionmaxscore = null;
            if (preg_match('/^NEWID\d+$/', $id)) {
                // Insert new criterion into DB.
                $data = array('definitionid' => $this->definition->id, 'descriptionformat' => FORMAT_MOODLE);
                foreach ($enrichedcriteriafields as $key) {
                    if (array_key_exists($key, $criterion)) {
                        $data[$key] = ($criterion[$key]&&(String)$criterion[$key] != '') ? $criterion[$key] : null;
                    }
                }

                // Handle course modules array (if exist), to store in database.
                if (array_key_exists('coursemodules', $criterion) && $criterion['coursemodules'] && is_array($criterion['coursemodules'])) {

                    // Check if user wants to publish the given form definition as a new template in the forms bank,
                    // or if he uses a template to create a new form.
                    $formshared = optional_param('shareform', '', PARAM_INT);
                    $pick = optional_param('pick', '', PARAM_INT);
                    if ($formshared || $pick) {
                        // Don't re-encode coursemodules table.
                        $dblbrackets = array('[[', ']]');
                        $snglbrackets = array('[', ']');
                        $data['coursemodules'] = str_replace($dblbrackets, $snglbrackets, json_encode($criterion['coursemodules']));
                    } else {
                        $data['coursemodules'] = json_encode($criterion['coursemodules']);
                    }
                } else {
                    $data['coursemodules'] = null;
                }

                if ($doupdate) {
                    $id = $DB->insert_record('gradingform_erubric_criteria', $data);
                }
                $haschanges[5] = true;
            } else {
                // Update criterion in DB.
                $data = array();

                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists('coursemodules', $criterion)) {
                    $criterion['coursemodules'] = '';
                }

                foreach ($enrichedcriteriafields as $key) {
                    if (array_key_exists($key, $criterion) && (String)$criterion[$key] != (String)$currentcriteria[$id][$key]) {
                        $data[$key] = ($criterion[$key]&&(String)$criterion[$key] != '') ? $criterion[$key] : null;
                    }
                }

                // Handle course modules array.
                if (is_array($criterion['coursemodules'])) {

                    // Check if coursemodules array already exist in current definition.
                    if (array_key_exists('coursemodules', $currentcriteria[$id]) && is_array($currentcriteria[$id]['coursemodules'])) {
                        // Something quick to accurate compare the modules array from the form, with the modules array to be updated.
                        $dblbrackets = array('[[', ']]');
                        $snglbrackets = array('[', ']');
                        $tempmodulescompstr1 = str_replace($dblbrackets, $snglbrackets, json_encode($currentcriteria[$id]['coursemodules']));
                        $tempmodulescompstr2 = json_encode($criterion['coursemodules']);
                    } else {
                        $tempmodulescompstr1 = 1; // Dummy var.
                        $tempmodulescompstr2 = 2; // Dummy var.
                    }

                    // If there is a change, update current data.
                    if ($tempmodulescompstr1 != $tempmodulescompstr2) {
                        $data['coursemodules'] = json_encode($criterion['coursemodules']);
                    }
                } else {
                    // In case we need to reset already stored course modules to null...
                    if (array_key_exists('coursemodules', $currentcriteria[$id])) {
                        $data['coursemodules'] = null;
                    }
                }

                if (!empty($data)) {
                    // Update only if something is changed...
                    $data['id'] = $id;
                    if ($doupdate) {
                        $DB->update_record('gradingform_erubric_criteria', $data);
                    }
                    // Check if there is a change in any of the enriched fields.
                    if (count($data) > 1 || (count($data) == 2 && !isset($data['description']))) {
                        $haschanges[5] = true; // TODO: Check if this condition works. Maybe there is something about descriptionformat.
                    } else { // Else, only the criterion description has changed.
                        $haschanges[1] = true;
                    }
                }
                // Remove deleted levels from DB and calculate the maximum score for this criteria.
                foreach ($currentcriteria[$id]['levels'] as $levelid => $currentlevel) {
                    if ($criterionmaxscore === null || $criterionmaxscore < $currentlevel['score']) {
                        $criterionmaxscore = $currentlevel['score'];
                    }
                    if (!array_key_exists($levelid, $levelsdata)) {
                        if ($doupdate) {
                            $DB->delete_records('gradingform_erubric_levels', array('id' => $levelid));
                        }
                        $haschanges[4] = true;
                    }
                }
            }
            foreach ($levelsdata as $levelid => $level) {
                if (isset($level['score'])) {
                    $level['score'] = unformat_float($level['score']);
                }

                if (array_key_exists('enrichedvalue', $level) && !is_null($level['enrichedvalue']) && strlen(trim($level['enrichedvalue'])) > 0) {
                    $level['enrichedvalue'] = unformat_float($level['enrichedvalue']);
                } else {
                    $level['enrichedvalue'] = null;
                }

                if (preg_match('/^NEWID\d+$/', $levelid)) {
                    // Insert level into DB.
                    $data = array('criterionid' => $id, 'definitionformat' => FORMAT_MOODLE);
                    foreach ($enrichedlevelfields as $key) {

                        if (array_key_exists($key, $level) && strlen(trim($level[$key])) > 0) {
                            $data[$key] = $level[$key];
                        }
                    }
                    if ($doupdate) {
                        $levelid = $DB->insert_record('gradingform_erubric_levels', $data);
                    }
                    if ($criterionmaxscore !== null && $criterionmaxscore >= $level['score']) {
                        // New level is added but the maximum score for this criteria did not change, re-grading may not be necessary.
                        $haschanges[2] = true;
                    } else {
                        $haschanges[3] = true;
                    }

                    // If there is a new enriched level value added, students must be re-graded.
                    if (count($data) > 1) {
                        $haschanges[5] = true;
                    }

                } else {
                    // Update level in DB.
                    $data = array();
                    foreach ($enrichedlevelfields as $key) {
                        if (array_key_exists($key, $level) && (string)$level[$key] != (string)$currentcriteria[$id]['levels'][$levelid][$key]) {
                            $data[$key] = $level[$key];
                        }
                    }
                    if (!empty($data)) {
                        // Update only if something has changed.
                        $data['id'] = $levelid;
                        if ($doupdate) {
                            $DB->update_record('gradingform_erubric_levels', $data);
                        }
                        if (isset($data['score'])) {
                            $haschanges[3] = true;
                        }

                        // Check if there is a change in any of the enriched fields.
                        if (count($data) > 1 || (count($data) == 1 && !is_null($data['enrichedvalue']))) {
                            $haschanges[5] = true;
                        } else { // Else, only the criterion description has changed.
                            $haschanges[1] = true;
                        }
                    }
                }
            }
        }
        // Remove deleted criteria from DB.
        foreach (array_keys($currentcriteria) as $id) {
            if (!array_key_exists($id, $newcriteria)) {
                if ($doupdate) {
                    $DB->delete_records('gradingform_erubric_criteria', array('id' => $id));
                    $DB->delete_records('gradingform_erubric_levels', array('criterionid' => $id));
                }
                $haschanges[3] = true;
            }
        }
        foreach (array('status', 'description', 'descriptionformat', 'name', 'options') as $key) {
            if (isset($newdefinition->$key) && $newdefinition->$key != $this->definition->$key) {
                $haschanges[1] = true;
            }
        }
        if ($usermodified && $usermodified != $this->definition->usermodified) {
            $haschanges[1] = true;
        }
        if (!count($haschanges)) {
            return 0;
        }
        if ($doupdate) {
            parent::update_definition($newdefinition, $usermodified);
            $this->load_definition();
        }
        // Return the maximum level of changes.
        $changelevels = array_keys($haschanges);
        sort($changelevels);
        return array_pop($changelevels);
    }

    /**
     * Marks all instances filled with this enriched rubric with the status INSTANCE_STATUS_NEEDUPDATE.
     */
    public function mark_for_regrade() {
        global $DB;
        if ($this->has_active_instances()) {
            $conditions = array('definitionid'  => $this->definition->id,
                        'status'  => gradingform_instance::INSTANCE_STATUS_ACTIVE);
            $DB->set_field('grading_instances', 'status', gradingform_instance::INSTANCE_STATUS_NEEDUPDATE, $conditions);
        }
    }

    /**
     * Loads the enriched rubric form definition if it exists.
     *
     * There is a new array called 'erubriccriteria' appended to the list of parent's definition properties.
     */
    protected function load_definition() {

        global $DB;
        $sql = "SELECT gd.*,
                       erc.id AS ercid, erc.sortorder AS ercsortorder, erc.description AS ercdescription, erc.descriptionformat AS ercdescriptionformat,
                       erc.criteriontype AS erccriteriontype, erc.collaborationtype AS erccollaborationtype, erc.coursemodules AS erccoursemodules,
                       erc.operator AS ercoperator, erc.referencetype AS ercreferencetype, erl.id AS erlid, erl.score AS erlscore,
                       erl.definition AS erldefinition, erl.definitionformat AS erldefinitionformat, erl.enrichedvalue AS erlenrichedvalue
                  FROM {grading_definitions} gd
             LEFT JOIN {gradingform_erubric_criteria} erc ON (erc.definitionid = gd.id)
             LEFT JOIN {gradingform_erubric_levels} erl ON (erl.criterionid = erc.id)
                 WHERE gd.areaid = :areaid AND gd.method = :method
              ORDER BY erc.sortorder,erl.score";
        $params = array('areaid' => $this->areaid, 'method' => $this->get_method_name());

        $rs = $DB->get_recordset_sql($sql, $params);
        $this->definition = false;
        foreach ($rs as $record) {
            // Pick the common definition data.
            if ($this->definition === false) {
                $this->definition = new stdClass();
                foreach (array('id', 'name', 'description', 'descriptionformat', 'status', 'copiedfromid',
                        'timecreated', 'usercreated', 'timemodified', 'usermodified', 'timecopied', 'options') as $fieldname) {
                    $this->definition->$fieldname = $record->$fieldname;
                }
                $this->definition->erubriccriteria = array();
            }
            // Pick the criterion data.
            if (!empty($record->ercid) and empty($this->definition->erubriccriteria[$record->ercid])) {
                foreach (array('id', 'sortorder', 'description', 'descriptionformat', 'criteriontype', 'collaborationtype', 'operator', 'referencetype') as $fieldname) {
                    $this->definition->erubriccriteria[$record->ercid][$fieldname] = $record->{'erc'.$fieldname};
                }

                // Handle the coursemodules and create the appropriate array.
                if ($record->{'erccoursemodules'}) {
                    $this->definition->erubriccriteria[$record->ercid]['coursemodules'] = array();
                    $crsmodules = json_decode($record->{'erccoursemodules'});
                    foreach ($crsmodules as $module => $moduleids) {
                        $this->definition->erubriccriteria[$record->ercid]['coursemodules'][$module][] = $moduleids;
                    }
                }
                $this->definition->erubriccriteria[$record->ercid]['levels'] = array();
            }
            // Pick the level data.
            if (!empty($record->erlid)) {
                foreach (array('id', 'score', 'definition', 'definitionformat', 'enrichedvalue') as $fieldname) {
                    $value = $record->{'erl'.$fieldname};
                    if ($fieldname == 'score' || ($fieldname == 'enrichedvalue' && !is_null($value))) {
                        $value = (float)$value; // To prevent displaying something like '1.00000'.
                    }
                    $this->definition->erubriccriteria[$record->ercid]['levels'][$record->erlid][$fieldname] = $value;
                }
            }
        }

        $rs->close();
        $options = $this->get_options();
        if (!$options['sortlevelsasc']) {
            foreach (array_keys($this->definition->erubriccriteria) as $ercid) {
                $this->definition->erubriccriteria[$ercid]['levels'] = array_reverse($this->definition->erubriccriteria[$ercid]['levels'], true);
            }
        }
    }

    /**
     * Returns the default options for the enriched rubric display
     *
     * @return array
     */
    public static function get_default_options() {
        $options = array(
            'sortlevelsasc' => 1,
            'lockzeropoints' => 1,
            'alwaysshowdefinition' => 1,
            'showdescriptionteacher' => 1,
            'showdescriptionstudent' => 1,
            'showscoreteacher' => 1,
            'showscorestudent' => 1,
            'enableremarks' => 1,
            'showremarksstudent' => 1,
            'enrichmentoptions' => 1,
            'showenrichedvaluestudent' => 1,
            'showenrichedvalueteacher' => 1,
            'showenrichedcriteriastudent' => 1,
            'showenrichedcriteriateacher' => 1,
            'overideenrichmentevaluation' => 1,
            'timestampenrichmentstart' => 1,
            'timestampenrichmentend' => 1,
            'showenrichedbenchmarkstudent' => 1,
            'showenrichedbenchmarkteacher' => 1
        );
        return $options;
    }

    /**
     * Gets the options of this enriched rubric definition, fills the missing options with default values.
     *
     * @return array
     */
    public function get_options() {
        $options = self::get_default_options();
        if (!empty($this->definition->options)) {
            $thisoptions = json_decode($this->definition->options);
            foreach ($thisoptions as $option => $value) {
                $options[$option] = $value;
            }
            if (!array_key_exists('lockzeropoints', $thisoptions)) {
                // Rubrics created before Moodle 3.2 don't have 'lockzeropoints' option. In this case they should not
                // assume default value 1 but use "legacy" value 0.
                $options['lockzeropoints'] = 1;
            }
        }
        return $options;
    }

    /**
     * Converts the current definition into an object suitable for the editor form's set_data().
     *
     * @param boolean $addemptycriterion whether to add an empty criterion if the enriched rubric is completely empty (just being created)
     * @return stdClass
     */
    public function get_definition_for_editing($addemptycriterion = false) {

        $definition = $this->get_definition();
        $properties = new stdClass();
        $properties->areaid = $this->areaid;
        if ($definition) {
            foreach (array('id', 'name', 'description', 'descriptionformat', 'status') as $key) {
                $properties->$key = $definition->$key;
            }
            $options = self::description_form_field_options($this->get_context());
            $properties = file_prepare_standard_editor($properties, 'description', $options, $this->get_context(),
                'grading', 'description', $definition->id);
        }
        $properties->erubric = array('criteria' => array(), 'options' => $this->get_options());
        if (!empty($definition->erubriccriteria)) {
            $properties->erubric['criteria'] = $definition->erubriccriteria;
        } else if (!$definition && $addemptycriterion) {
            $properties->erubric['criteria'] = array('addcriterion' => 1);
        }
        return $properties;
    }

    /**
     * Returns the form definition suitable for cloning into another area.
     *
     * @see parent::get_definition_copy()
     * @param gradingform_controller $target the controller of the new copy
     * @return stdClass definition structure to pass to the target's {@link update_definition()}
     */
    public function get_definition_copy(gradingform_controller $target) {

        // Consider the required action as not confirmed unless the user approved it.
        $enrichedconfirmed  = optional_param('enrichedconfirmed', false, PARAM_BOOL);
        // Check if we need to display the appropriate form of consent to the user.
        $shareform  = optional_param('shareform', null, PARAM_INT);
        GLOBAL $PAGE;
        $output = $PAGE->get_renderer('core_grading');

        if (!$enrichedconfirmed && $shareform) {

            // Let the user confirm they understand the risk of setting an enriched rubric as template.
            echo $output->header();
            echo $output->confirm(get_string('enrichshareconfirm', 'gradingform_erubric'),
                new moodle_url($PAGE->url, array('shareform' => $shareform, 'enrichedconfirmed' => 1, 'confirmed' => 1)),
                $PAGE->url);
            echo $output->footer();
            die();

        } else { // Got confirmation or just using another template to create a new enriched rubric.

            $new = parent::get_definition_copy($target);
            $old = $this->get_definition_for_editing();
            $new->description_editor = $old->description_editor;
            $new->erubric = array('criteria' => array(), 'options' => $old->erubric['options']);
            $newcritid = 1;
            $newlevid = 1;
            foreach ($old->erubric['criteria'] as $oldcritid => $oldcrit) {
                unset($oldcrit['id']);
                if (isset($oldcrit['levels'])) {
                    foreach ($oldcrit['levels'] as $oldlevid => $oldlev) {
                        unset($oldlev['id']);
                        $oldcrit['levels']['NEWID'.$newlevid] = $oldlev;
                        unset($oldcrit['levels'][$oldlevid]);
                        $newlevid++;
                    }
                } else {
                    $oldcrit['levels'] = array();
                }
                $new->erubric['criteria']['NEWID'.$newcritid] = $oldcrit;
                $newcritid++;
            }
            return $new;
        }
    }

    /**
     * Options for displaying the enriched rubric description field in the form.
     *
     * @param object $context
     * @return array options for the form description field
     */
    public static function description_form_field_options($context) {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_user_max_upload_file_size($context, $CFG->maxbytes),
            'context'  => $context,
        );
    }

    /**
     * Formats the definition description for display on page.
     *
     * @return string
     */
    public function get_formatted_description() {
        if (!isset($this->definition->description)) {
            return '';
        }
        $context = $this->get_context();

        $options = self::description_form_field_options($this->get_context());
        $description = file_rewrite_pluginfile_urls($this->definition->description, 'pluginfile.php', $context->id,
            'grading', 'description', $this->definition->id, $options);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->definition->descriptionformat, $formatoptions);
    }

    /**
     * Returns the enriched rubric plugin renderer.
     *
     * @param moodle_page $page the target page
     * @return gradingform_erubric_renderer
     */
    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('gradingform_'. $this->get_method_name());
    }

    /**
     * Returns the HTML code displaying the preview of the grading form.
     *
     * @param moodle_page $page the target page
     * @return string
     */
    public function render_preview(moodle_page $page) {

        if (!$this->is_form_defined()) {
            throw new coding_exception('It is the caller\'s responsibility to make sure that the form is actually defined');
        }

        $criteria = $this->definition->erubriccriteria;
        $options = $this->get_options();
        $erubric = '';
        if (has_capability('moodle/grade:managegradingforms', $page->context)) {
            $showdescription = true;
        } else {
            if (empty($options['alwaysshowdefinition'])) {
                // Ensure we don't display unless show rubric option enabled.
                return '';
            }
            $showdescription = $options['showdescriptionstudent'];
        }
        $output = $this->get_renderer($page);
        if ($showdescription) {
            $erubric .= $output->box($this->get_formatted_description(), 'gradingform_erubric-description');
        }
        if (has_capability('moodle/grade:managegradingforms', $page->context)) {
            if (!$options['lockzeropoints']) {
                // Warn about using grade calculation method where minimum number of points is flexible.
                $erubric .= $output->display_erubric_mapping_explained($this->get_min_max_score());
            }
            $erubric .= $output->display_erubric($criteria, $options, self::DISPLAY_PREVIEW, 'erubric');
        } else {
            $erubric .= $output->display_erubric($criteria, $options, self::DISPLAY_PREVIEW_GRADED, 'erubric');
        }

        return $erubric;
    }

    /**
     * Deletes the enriched rubric definition and all the associated information.
     */
    protected function delete_plugin_definition() {
        global $DB;

        // Get the list of instances.
        $instances = array_keys($DB->get_records('grading_instances', array('definitionid' => $this->definition->id), '', 'id'));
        // Delete all fillings.
        $DB->delete_records_list('gradingform_erubric_fillings', 'instanceid', $instances);
        // Delete instances.
        $DB->delete_records_list('grading_instances', 'id', $instances);
        // Get the list of criteria records.
        $criteria = array_keys($DB->get_records('gradingform_erubric_criteria', array('definitionid' => $this->definition->id), '', 'id'));
        // Delete levels.
        $DB->delete_records_list('gradingform_erubric_levels', 'criterionid', $criteria);
        // Delete critera.
        $DB->delete_records_list('gradingform_erubric_criteria', 'id', $criteria);
    }

    /**
     * If instanceid is specified and grading instance exists and it is created by this rater for
     * this item, this instance is returned.
     * If there exists a draft for this raterid+itemid, take this draft (this is the change from parent).
     * Otherwise new instance is created for the specified rater and itemid.
     *
     * @param int $instanceid
     * @param int $raterid
     * @param int $itemid
     * @return gradingform_instance
     */
    public function get_or_create_instance($instanceid, $raterid, $itemid) {
        global $DB;
        if ($instanceid &&
                $instance = $DB->get_record('grading_instances', array('id'  => $instanceid, 'raterid' => $raterid, 'itemid' => $itemid), '*', IGNORE_MISSING)) {
            return $this->get_instance($instance);
        }
        if ($itemid && $raterid) {
            $params = array('definitionid' => $this->definition->id, 'raterid' => $raterid, 'itemid' => $itemid);
            if ($rs = $DB->get_records('grading_instances', $params, 'timemodified DESC', '*', 0, 1)) {
                $record = reset($rs);
                $currentinstance = $this->get_current_instance($raterid, $itemid);
                if ($record->status == gradingform_erubric_instance::INSTANCE_STATUS_INCOMPLETE &&
                        (!$currentinstance || $record->timemodified > $currentinstance->get_data('timemodified'))) {
                    $record->isrestored = true;
                    return $this->get_instance($record);
                }
            }
        }
        return $this->create_instance($raterid, $itemid);
    }

    /**
     * Returns html code to be included in student's feedback.
     *
     * @param moodle_page $page
     * @param int $itemid
     * @param array $gradinginfo result of function grade_get_grades
     * @param string $defaultcontent default string to be returned if no active grading is found
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function render_grade($page, $itemid, $gradinginfo, $defaultcontent, $cangrade) {
        return $this->get_renderer($page)->display_instances($this->get_active_instances($itemid), $defaultcontent, $cangrade);
    }

    /**
     * Prepare the part of the search query to append to the FROM statement
     *
     * @param string $gdid the alias of grading_definitions.id column used by the caller
     * @return string
     */
    public static function sql_search_from_tables($gdid) {
        return " LEFT JOIN {gradingform_erubric_criteria} erc ON (erc.definitionid = $gdid)
                 LEFT JOIN {gradingform_erubric_levels} erl ON (erl.criterionid = erc.id)";
    }

    /**
     * Prepare the parts of the SQL WHERE statement to search for the given token
     *
     * The returned array cosists of the list of SQL comparions and the list of
     * respective parameters for the comparisons. The returned chunks will be joined
     * with other conditions using the OR operator.
     *
     * @param string $token token to search for
     * @return array
     */
    public static function sql_search_where($token) {
        global $DB;

        $subsql = array();
        $params = array();

        // Search in enriched rubric criteria description.
        $subsql[] = $DB->sql_like('erc.description', '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($token).'%';

        // Search in enriched rubric levels definition.
        $subsql[] = $DB->sql_like('erl.definition', '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($token).'%';

        return array($subsql, $params);
    }

    /**
     * Calculates and returns the possible minimum and maximum score (in points) for this enriched rubric.
     *
     * @return array
     */
    public function get_min_max_score() {
        if (!$this->is_form_available()) {
            return null;
        }
        $returnvalue = array('minscore' => 0, 'maxscore' => 0);
        foreach ($this->get_definition()->erubriccriteria as $id => $criterion) {
            $scores = array();
            foreach ($criterion['levels'] as $level) {
                $scores[] = $level['score'];
            }
            sort($scores);
            $returnvalue['minscore'] += $scores[0];
            $returnvalue['maxscore'] += $scores[count($scores) - 1];
        }
        return $returnvalue;
    }

    /**
     * Returns an array that defines the structure of the rubric's criteria. This function is used by the web service functions
     * core_grading_external::get_definitions(), core_grading_external::definition() and core_grading_external::create_definition_object().

     * @return array An array containing a single key/value pair with the 'erubriccriteria' external_multiple_structure.
     * @see gradingform_controller::get_external_definition_details()
     * @since Moodle 2.5
     */
    public static function get_external_definition_details() {
        $erubriccriteria = new external_multiple_structure(
            new external_single_structure(
                array(
                   'id'   => new external_value(PARAM_INT, 'criterion id', VALUE_OPTIONAL),
                   'sortorder' => new external_value(PARAM_INT, 'sortorder', VALUE_OPTIONAL),
                   'description' => new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                   'descriptionformat' => new external_format_value('description', VALUE_OPTIONAL),
                   'criteriontype' => new external_value(PARAM_INT, 'criteriontype', VALUE_OPTIONAL),
                   'collaborationtype' => new external_value(PARAM_INT, 'collaborationtype', VALUE_OPTIONAL),
                   'coursemodules' => new external_value(PARAM_RAW, 'coursemodules', VALUE_OPTIONAL),
                   'operator' => new external_value(PARAM_INT, 'operator', VALUE_OPTIONAL),
                   'referencetype' => new external_value(PARAM_INT, 'referencetype', VALUE_OPTIONAL),
                   'levels' => new external_multiple_structure(
                                   new external_single_structure(
                                       array(
                                        'id' => new external_value(PARAM_INT, 'level id', VALUE_OPTIONAL),
                                        'score' => new external_value(PARAM_FLOAT, 'score', VALUE_OPTIONAL),
                                        'definition' => new external_value(PARAM_RAW, 'definition', VALUE_OPTIONAL),
                                        'definitionformat' => new external_format_value('definition', VALUE_OPTIONAL),
                                        'enrichedvalue' => new external_value(PARAM_FLOAT, 'enrichedvalue', VALUE_OPTIONAL)
                                       )
                                  ), 'levels', VALUE_OPTIONAL
                              )
                   )
              ), 'definition details', VALUE_OPTIONAL
        );
        return array('rubric_criteria' => $erubriccriteria);
    }

    /**
     * Returns an array that defines the structure of the rubric's filling. This function is used by
     * the web service function core_grading_external::get_gradingform_instances().
     *
     * @return An array containing a single key/value pair with the 'criteria' external_multiple_structure
     * @see gradingform_controller::get_external_instance_filling_details()
     * @since Moodle 2.6
     */
    public static function get_external_instance_filling_details() {
        $ecriteria = new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'filling id'),
                    'criterionid' => new external_value(PARAM_INT, 'criterion id'),
                    'levelid' => new external_value(PARAM_INT, 'level id', VALUE_OPTIONAL),
                    'remark' => new external_value(PARAM_RAW, 'remark', VALUE_OPTIONAL),
                    'remarkformat' => new external_format_value('remark', VALUE_OPTIONAL),
                    'enrichedbenchmark' => new external_value(PARAM_FLOAT, 'enrichedbenchmark', VALUE_OPTIONAL),
                    'enrichedbenchmarkstudent' => new external_value(PARAM_FLOAT, 'enrichedbenchmarkstudent', VALUE_OPTIONAL),
                    'enrichedbenchmarkstudents' => new external_value(PARAM_FLOAT, 'enrichedbenchmarkstudents', VALUE_OPTIONAL)
                )
            ), 'filling', VALUE_OPTIONAL
        );
        return array ('criteria' => $ecriteria);
    }
}

/**
 * Class to manage one enriched rubric grading instance.
 *
 * Stores information and performs actions like update, copy, validate, submit, etc.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_erubric_instance extends gradingform_instance {

    /** The e-rubric object which stores all it's data. */
    protected $erubric;

    /**
     * Deletes this (INCOMPLETE) instance from database.
     */
    public function cancel() {
        global $DB;
        parent::cancel();
        $DB->delete_records('gradingform_erubric_fillings', array('instanceid' => $this->get_id()));
    }

    /**
     * Duplicates the instance before editing (optionally substitutes raterid and/or itemid with
     * the specified values).
     *
     * @param int $raterid value for raterid in the duplicate
     * @param int $itemid value for itemid in the duplicate
     * @return int id of the new instance
     */
    public function copy($raterid, $itemid) {
        global $DB;
        $instanceid = parent::copy($raterid, $itemid);
        $currentgrade = $this->get_erubric_filling();
        foreach ($currentgrade['criteria'] as $criterionid => $record) {
            $params = array('instanceid' => $instanceid, 'criterionid' => $criterionid,
                'levelid' => $record['levelid'], 'remark' => $record['remark'], 'remarkformat' => $record['remarkformat'],
                'enrichedbenchmark' => $record['enrichedbenchmark'], 'enrichedbenchmarkstudent' => $record['enrichedbenchmarkstudent'],
                'enrichedbenchmarkstudents' => $record['enrichedbenchmarkstudents']);
            $DB->insert_record('gradingform_erubric_fillings', $params);
        }
        return $instanceid;
    }

    /**
     * Determines whether the submitted form was empty.
     *
     * @param array $elementvalue value of element submitted from the form
     * @return boolean true if the form is empty
     */
    public function is_empty_form($elementvalue) {
        $criteria = $this->get_controller()->get_definition()->erubriccriteria;

        foreach ($criteria as $id => $criterion) {
            if (isset($elementvalue['criteria'][$id]['levelid'])
                    || !empty($elementvalue['criteria'][$id]['remark'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Removes the attempt from the gradingform_guide_fillings table
     * @param array $data the attempt data
     */
    public function clear_attempt($data) {
        global $DB;

        foreach ($data['criteria'] as $criterionid => $record) {
            $DB->delete_records('gradingform_erubric_fillings',
                array('criterionid' => $criterionid, 'instanceid' => $this->get_id()));
        }
    }

    /**
     * Validates that enriched rubric is fully completed and contains valid grade on each criterion.
     *
     * @param array $elementvalue value of element as came in form submit
     * @return boolean true if the form data is validated and contains no errors
     */
    public function validate_grading_element($elementvalue) {
        $criteria = $this->get_controller()->get_definition()->erubriccriteria;
        if (!isset($elementvalue['criteria']) || !is_array($elementvalue['criteria']) || count($elementvalue['criteria']) < count($criteria)) {
            return false;
        }
        foreach ($criteria as $id => $criterion) {
            if (!isset($elementvalue['criteria'][$id]['levelid'])
                    || !array_key_exists($elementvalue['criteria'][$id]['levelid'], $criterion['levels'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Retrieves from DB and returns the data how this enriched rubric was filled.
     *
     * @param boolean $force whether to force DB query even if the data is cached
     * @return array
     */
    public function get_erubric_filling($force = false) {
        global $DB;
        if ($this->erubric === null || $force) {
            $records = $DB->get_records('gradingform_erubric_fillings', array('instanceid' => $this->get_id()));
            $this->erubric = array('criteria' => array());
            foreach ($records as $record) {
                $this->erubric['criteria'][$record->criterionid] = (array)$record;
            }
        }
        return $this->erubric;
    }

    /**
     * Updates the instance with the data received from grading form. This function may be
     * called via AJAX when grading is not yet completed, so it does not change the
     * status of the instance.
     *
     * @param array $data
     */
    public function update($data) {
        global $DB;
        $currentgrade = $this->get_erubric_filling();
        parent::update($data);
        foreach ($data['criteria'] as $criterionid => $record) {
            if (!array_key_exists($criterionid, $currentgrade['criteria'])) {
                $newrecord = array('instanceid' => $this->get_id(), 'criterionid' => $criterionid,
                    'levelid' => $record['levelid'], 'remarkformat' => FORMAT_MOODLE);
                if (isset($record['remark'])) {
                    $newrecord['remark'] = $record['remark'];
                }
                if (isset($record['enrichedbenchmark'])) {
                    $newrecord['enrichedbenchmark'] = ((String)$record['enrichedbenchmark'] != '') ? $record['enrichedbenchmark'] : null;
                }
                if (isset($record['enrichedbenchmarkstudent'])) {
                    $newrecord['enrichedbenchmarkstudent'] = ((String)$record['enrichedbenchmarkstudent'] != '') ? $record['enrichedbenchmarkstudent'] : null;
                }
                if (isset($record['enrichedbenchmarkstudents'])) {
                    $newrecord['enrichedbenchmarkstudents'] = ((String)$record['enrichedbenchmarkstudents'] != '') ? $record['enrichedbenchmarkstudents'] : null;
                }
                $DB->insert_record('gradingform_erubric_fillings', $newrecord);
            } else {
                $newrecord = array('id' => $currentgrade['criteria'][$criterionid]['id']);
                foreach (array('levelid', 'remark', 'enrichedbenchmark', 'enrichedbenchmarkstudent', 'enrichedbenchmarkstudents') as $key) {
                    if (array_key_exists($key, $record)) {
                        $newrecord[$key] = ((String)$record[$key] != ''||$key == 'remark') ? $record[$key] : null;
                    } else {
                        $newrecord[$key] = null;
                    }
                }

                if (count($newrecord) > 1) {
                    $DB->update_record('gradingform_erubric_fillings', $newrecord);
                }
            }
        }
        foreach ($currentgrade['criteria'] as $criterionid => $record) {
            if (!array_key_exists($criterionid, $data['criteria'])) {
                $DB->delete_records('gradingform_erubric_fillings', array('id' => $record['id']));
            }
        }
        $this->get_erubric_filling(true);
    }

    /**
     * Calculates the grade to be pushed to the gradebook.
     *
     * @return float|int the valid grade from $this->get_controller()->get_grade_range()
     */
    public function get_grade() {

        $grade = $this->get_erubric_filling();

        if (!($scores = $this->get_controller()->get_min_max_score()) || $scores['maxscore'] <= $scores['minscore']) {
            return -1;
        }

        $graderange = array_keys($this->get_controller()->get_grade_range());
        if (empty($graderange)) {
            return -1;
        }
        sort($graderange);
        $mingrade = $graderange[0];
        $maxgrade = $graderange[count($graderange) - 1];

        $curscore = 0;
        foreach ($grade['criteria'] as $id => $record) {
            $curscore += $this->get_controller()->get_definition()->erubriccriteria[$id]['levels'][$record['levelid']]['score'];
        }

        $allowdecimals = $this->get_controller()->get_allow_grade_decimals();
        $options = $this->get_controller()->get_options();

        if ($options['lockzeropoints']) {
            // New grade calculation method when 0-level is locked.
            $grade = max($mingrade, $curscore / $scores['maxscore'] * $maxgrade);
            return $allowdecimals ? $grade : round($grade, 0);
        } else {
            // Traditional grade calculation method.
            $gradeoffset = ($curscore - $scores['minscore']) / ($scores['maxscore'] - $scores['minscore']) * ($maxgrade - $mingrade);
            return ($allowdecimals ? $gradeoffset : round($gradeoffset, 0)) + $mingrade;
        }
    }

    /**
     * Returns html for form element of type 'grading'.
     *
     * @param moodle_page $page
     * @param MoodleQuickForm_grading $gradingformelement
     * @return string
     */
    public function render_grading_element($page, $gradingformelement) {
        global $USER;
        if (!$gradingformelement->_flagFrozen) {
            $module = array('name' => 'gradingform_erubric', 'fullpath' => '/grade/grading/form/erubric/js/erubric.js');
            $page->requires->js_init_call('M.gradingform_erubric.init', array(array('name' => $gradingformelement->getName())), true, $module);
            $mode = gradingform_erubric_controller::DISPLAY_EVAL;
        } else {
            if ($gradingformelement->_persistantFreeze) {
                $mode = gradingform_erubric_controller::DISPLAY_EVAL_FROZEN;
            } else {
                $mode = gradingform_erubric_controller::DISPLAY_REVIEW;
            }
        }
        $criteria = $this->get_controller()->get_definition()->erubriccriteria;
        $options = $this->get_controller()->get_options();
        $value = $gradingformelement->getValue();
        $html = '';
        if ($value === null) {
            $value = $this->get_erubric_filling();
        } else if (!$this->validate_grading_element($value)) {
            $html .= html_writer::tag('div', get_string('rubricnotcompleted', 'gradingform_erubric'), array('class' => 'gradingform_erubric-error'));
        }
        $currentinstance = $this->get_current_instance();
        if ($currentinstance && $currentinstance->get_status() == gradingform_instance::INSTANCE_STATUS_NEEDUPDATE) {
            $html .= html_writer::tag('div', get_string('needregrademessage', 'gradingform_erubric'),
                                      array('class' => 'gradingform_erubric-regrade', 'role' => 'alert'));
        }
        $haschanges = false;
        if ($currentinstance) {
            $curfilling = $currentinstance->get_erubric_filling();
            foreach ($curfilling['criteria'] as $criterionid => $curvalues) {
                $value['criteria'][$criterionid]['savedlevelid'] = $curvalues['levelid'];
                $newremark = null;
                $newlevelid = null;
                $newenrichedbenchmark = null;
                $newenrichedbenchmarkstudent = null;
                $newenrichedbenchmarkstudents = null;
                if (isset($value['criteria'][$criterionid]['remark'])) {
                    $newremark = $value['criteria'][$criterionid]['remark'];
                }
                if (isset($value['criteria'][$criterionid]['levelid'])) {
                    $newlevelid = $value['criteria'][$criterionid]['levelid'];
                }
                if (array_key_exists('enrichedbenchmark', $value['criteria'][$criterionid])) {
                    $newenrichedbenchmark = $value['criteria'][$criterionid]['enrichedbenchmark'];
                }
                if (array_key_exists('enrichedbenchmarkstudent', $value['criteria'][$criterionid])) {
                    $newenrichedbenchmarkstudent = $value['criteria'][$criterionid]['enrichedbenchmarkstudent'];
                }
                if (array_key_exists('enrichedbenchmarkstudents', $value['criteria'][$criterionid])) {
                    $newenrichedbenchmarkstudents = $value['criteria'][$criterionid]['enrichedbenchmarkstudents'];
                }
                if ($newlevelid != $curvalues['levelid'] ||
                    $newremark != $curvalues['remark'] ||
                    (string)$newenrichedbenchmark != (string)$curvalues['enrichedbenchmark'] ||
                    (string)$newenrichedbenchmarkstudent != (string)$curvalues['enrichedbenchmarkstudent'] ||
                    (string)$newenrichedbenchmarkstudents != (string)$curvalues['enrichedbenchmarkstudents']) {
                    $haschanges = true;
                }
            }
        }
        if ($this->get_data('isrestored') && $haschanges) {
            $html .= html_writer::tag('div', get_string('restoredfromdraft', 'gradingform_erubric'), array('class' => 'gradingform_erubric-restored'));
        }
        if (!empty($options['showdescriptionteacher'])) {
            $html .= html_writer::tag('div', $this->get_controller()->get_formatted_description(), array('class' => 'gradingform_erubric-description'));
        }
        $html .= $this->get_controller()->get_renderer($page)->display_erubric($criteria, $options, $mode, $gradingformelement->getName(), $value);
        return $html;
    }
}