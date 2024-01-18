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
 * @package    gradingform
 * @subpackage Learinng Analytics Enriched Rubric (e-rubric)
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//dimopoulos
require_once($CFG->libdir.'/gradelib.php');
require_once("$CFG->libdir/resourcelib.php");


/**
 * Grading method plugin renderer
 * @package    gradingform
 * @subpackage Learinng Analytics Enriched Rubric (e-rubric)
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_erubric_renderer extends plugin_renderer_base {

    //** Array to encapsulate the course resources (Book, File, URL etc.) that can be used for enrichment as 'moduleid->instanceid' => 'instancename'. */
    protected $resources = array();
    //** Array to encapsulate the course assignments (moodle 2.2 & 2.3 compatible) that can be used for enrichment as 'moduleid->instanceid' => 'instancename'. */
    protected $assignments = array();
    //** Array to encapsulate the course activities (of type forum or chat) that can be used for enrichment as 'moduleid->instanceid' => 'instancename'. */
    protected $activities = array();
    //** Array to encapsulate the icons for all this course's resources, assignments and activities (defined in here), as 'moduleid'=>'htmlimagestring' .*/
    public $moduleicon = array();
    //** Boolean to show missing course modules error message. *//
    protected $missingmodules = null;

    // Define the modules (chat-forum-old assignment-new assignment) ids that will be retrieved from modules table during _contruct.
    public $chatmoduleid         = null; // Make this public to use in erubriceditor.php.
    protected $forummoduleid     = null;
    protected $oldassignmoduleid = null;
    protected $newassignmoduleid = null;

    /**
     * Constructor
     *
     * The constructor takes two arguments. The first is the page that the renderer
     * has been created to assist with, and the second is the target.
     * The target is an additional identifier that can be used to load different
     * renderers for different options.
     *
     * In order to create and initialize the appropriate arrays for resources, activities, assignments and icons,
     * we must update the declared constructor of the parent class, and since we can't just update it, we declare it again
     * adding the appropriate commands that suit our purpose.
     *
     * @param moodle_page $page the page we are doing output for.
     * @param string $target one of rendering target constants
     * @see core:renderer::__construct()
     */
    public function __construct(moodle_page $page, $target) {

        // Find and update course modules ids.
        GLOBAL $DB;
        $this->chatmoduleid      = $DB->get_field_sql('SELECT id FROM {modules} WHERE name="chat"', null);
        $this->forummoduleid     = $DB->get_field_sql('SELECT id FROM {modules} WHERE name="forum"', null);
        $this->oldassignmoduleid = $DB->get_field_sql('SELECT id FROM {modules} WHERE name="assignment"', null);
        $this->newassignmoduleid = $DB->get_field_sql('SELECT id FROM {modules} WHERE name="assign"', null);

        $this->output = $page->get_renderer('core', null, $target);
        parent::__construct($page, $target);

        // These are needed for the enriched rubric renderer construction.
        $this->resources = $this->get_modules_array('resources', $this->moduleicon);
        $this->assignments = $this->get_modules_array('assignments', $this->moduleicon);
        $this->activities = $this->get_modules_array('activities', $this->moduleicon);
    }

    /**
     * Function to update the renderer's icons array, according to modules used for the current course.
     *
     * @param array $moduleicons the renderer's icons for modules used.
     * @param int $moduleid the id of the course module
     * @param string $modulename the name of the course module to use it in order to obtain the icon url and to put it in image title tag
     */
    private function check_update_modules_icon (&$moduleicons, $moduleid, $modulename) {
        if (!array_key_exists($moduleid, $moduleicons)){
            GLOBAL $OUTPUT;
            $moduleicons[$moduleid] =  '<img src="'.$OUTPUT->pix_url('icon', $modulename).'" title="'.ucfirst($modulename).'">';
        }
    }

    /**
     * Get list of Resources, Activities and Assingments for this course and this enriched rubric.
     * As activities we take by default chat and forum modules, corresponding to students collaboration.
     * For future adding of collaboration activities, the coresponding module ids are passed inside the
     * moduleactivitiesids array.
     * This function also calls the update function for the icons array of these course modules.
     *
     * @param array $moduleicons the renderer's icons for modules used
     * @param string $mdtype the type description of the course module
     */
    private function get_modules_array($mdtype, &$moduleicons) {

        global $COURSE;
        global $DB;

        // Check cuurent moodle version against moodle version for new assignment type (Moodle 2.3).
        global $CFG;
        $moodle_2_3_0_version = '2012062500';
        $newassignmentmodule = false;
        if ($CFG->version >= $moodle_2_3_0_version)
            $newassignmentmodule = true;

        $moduleactivitiesids = array($this->chatmoduleid, $this->forummoduleid);
        $returnArray = array();
        $params = null;

        // Make SQL statement according to module type.
        switch ($mdtype) {

            case 'assignments':
                global $PAGE;

                // In case this page is accessed for activity reporting do nothing.
                if (!isset($PAGE->cm->modname)) return('');

                $curmodule = $PAGE->cm->modname;
                $curmoduleid = (int)$PAGE->cm->module;
                $curmoduleinstance = $PAGE->cm->instance;

                //**** For moodle 2.3 onwards ****//
                if ($newassignmentmodule){
                    $sql = "SELECT cm.id AS uniqueID, cm.module, cm.instance, module.name, module.id, cm.course
                               FROM {course_modules} cm
                            INNER JOIN {modules} module ON (cm.module = module.id)
                            WHERE cm.course = $COURSE->id ";

                    if ($curmoduleid==$this->newassignmoduleid){
                        $sql .= "AND (cm.module = $this->oldassignmoduleid OR (cm.module = $this->newassignmoduleid AND cm.instance != $curmoduleinstance))";
                    }else{
                        $sql .= "AND (cm.module = $this->newassignmoduleid OR (cm.module = $this->oldassignmoduleid AND cm.instance != $curmoduleinstance))";
                    }

                //**** For moodle 2.2 versions ****//
                }else{
                    $sql = "SELECT cm.id AS uniqueID, cm.module, cm.instance, module.name, module.id, cm.course
                               FROM {course_modules} cm
                            INNER JOIN {modules} module ON (cm.module = module.id)
                            WHERE cm.course = $COURSE->id AND cm.module = $this->oldassignmoduleid AND cm.instance != $curmoduleinstance";
                }
                break;
            case 'activities':
                $sql = "SELECT cm.id AS uniqueID, cm.module, cm.instance, module.name, module.id, cm.course
                           FROM {course_modules} cm
                        INNER JOIN {modules} module ON (cm.module = module.id) ";

                if ($newassignmentmodule){
                    $sql .= "WHERE cm.course = $COURSE->id AND cm.module != $this->oldassignmoduleid AND cm.module != $this->newassignmoduleid ";
                }else{
                    $sql .= "WHERE cm.course = $COURSE->id AND cm.module != $this->oldassignmoduleid ";
                }
                $sizeofids = count($moduleactivitiesids);
                if ($sizeofids>0){
                    $sql .= 'AND (';
                    foreach ($moduleactivitiesids as $i => $modid) {
                        $sql .= 'cm.module = '.$moduleactivitiesids[$i];
                        if ($i<$sizeofids-1) $sql .= ' || ';
                    }
                    $sql .= ')';
                }
                break;
            case 'resources':
                $sql = "SELECT cm.id AS uniqueID, cm.module, cm.instance, module.name, module.id, cm.course
                           FROM {course_modules} cm
                        INNER JOIN {modules} module ON (cm.module = module.id) ";

                if ($newassignmentmodule){
                    $sql .= "WHERE cm.course = $COURSE->id AND cm.module != $this->oldassignmoduleid AND cm.module != $this->newassignmoduleid ";
                }else{
                    $sql .= "WHERE cm.course = $COURSE->id AND cm.module != $this->oldassignmoduleid ";
                }
                $sizeofids = count($moduleactivitiesids);
                if ($sizeofids>0){
                    foreach ($moduleactivitiesids as $i => $modid) {
                        $sql .= 'AND cm.module != '.$moduleactivitiesids[$i].' ';
                    }
                }
                break;
        }

        $rs = $DB->get_records_sql($sql, $params);

        if ($mdtype=='resources' && !empty($rs)) {
            $areresources = array(); // Put modules id for the ones that are already checked.
            foreach ($rs as $record) {

                // This module instance refers to a resource module. No need to check again. Just update the return array and moduleicons array.
                if (in_array($record->module, $areresources)){
                    $modInstName = $DB->get_field($record->name, 'name', array('id'=>$record->instance));
                    $returnArray[$record->module.'->'.$record->instance] = $modInstName;
                    $this->check_update_modules_icon ($moduleicons, $record->module, $record->name); // Update icons array.

                // Check if this module instance belongs to a resource module.
                }else{
                    $archetype = plugin_supports('mod', $record->name, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                    if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                        $areresources[] = $record->module; // Add new module id in the array to avoid further check of module folder.
                        $modInstName = $DB->get_field($record->name, 'name', array('id'=>$record->instance));
                        $returnArray[$record->module.'->'.$record->instance] = $modInstName;
                        $this->check_update_modules_icon ($moduleicons, $record->module, $record->name); // Update icons array.
                    }
                }
            }
            asort($returnArray);

        }else if (!empty($rs)){
            foreach ($rs as $record) {
                $modInstName = $DB->get_field($record->name, 'name', array('id'=>$record->instance));
                $returnArray[$record->module.'->'.$record->instance] = $modInstName;
                $this->check_update_modules_icon ($moduleicons, $record->module, $record->name); // Update icons array.
            }
            asort($returnArray);
        }
        return $returnArray;
    }

    /**
     * This function returns html code for displaying the tr of a simple criterion. Depending on $mode it may be the
     * code to edit the enriched rubric, to preview it, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_erubric() to display the whole enriched rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty criteria to the
     * enriched rubric being designed.
     * In this case it will use macros like {NAME}, {LEVELS}, {CRITERION-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode enriched rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $criterion criterion data
     * @param string $levelsstr evaluated templates for this criterion levels
     * @param array|null $value (only in view mode) teacher's feedback on this criterion
     * @return string
     */
    public function criterion_template($mode, $options, $elementname = '{NAME}', $criterion = null, $levelsstr = '{LEVELS}', $value = null) {
        // TODO description format, remark format
        if ($criterion === null || !is_array($criterion) || !array_key_exists('id', $criterion)) {
            $criterion = array('id' => '{CRITERION-id}', 'description' => '{CRITERION-description}', 'sortorder' => '{CRITERION-sortorder}', 'class' => '{CRITERION-class}', 'criteriontype' => '{CRITERION-criteriontype}');
        } else {
            foreach (array('sortorder', 'description', 'class', 'criteriontype') as $key) {
                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists($key, $criterion)) {
                    $criterion[$key] = '';
                }
            }
        }
        $criteriontemplate = html_writer::start_tag('tr', array('class' => 'criterion'. $criterion['class'], 'id' => '{NAME}-criteria-{CRITERION-id}'));
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $criteriontemplate .= html_writer::start_tag('td', array('class' => 'controls'));
            foreach (array('moveup', 'delete', 'movedown') as $key) {
                $value = get_string('criterion'.$key, 'gradingform_erubric');
                $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}]['.$key.']',
                    'id' => '{NAME}-criteria-{CRITERION-id}-'.$key, 'value' => $value, 'title' => $value, 'tabindex' => -1));
                $criteriontemplate .= html_writer::tag('div', $button, array('class' => $key));
            }
            $criteriontemplate .= html_writer::end_tag('td'); // .controls
            $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]', 'value' => $criterion['sortorder']));
            $description = html_writer::tag('textarea', htmlspecialchars($criterion['description']), array('name' => '{NAME}[criteria][{CRITERION-id}][description]', 'cols' => '10', 'rows' => '5'));

        } else {
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][sortorder]', 'value' => $criterion['sortorder']));
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][description]', 'value' => $criterion['description']));
            }
            $description = $criterion['description'];

        }
        $descriptionclass = 'description';
        if (isset($criterion['error_description'])) {
            $descriptionclass .= ' error';
        }
        $criteriontemplate .= html_writer::tag('td', $description, array('class' => $descriptionclass, 'id' => '{NAME}-criteria-{CRITERION-id}-description'));
        $levelsstrtable = html_writer::tag('table', html_writer::tag('tr', $levelsstr, array('id' => '{NAME}-criteria-{CRITERION-id}-levels')));
        $levelsclass = 'levels';
        if (isset($criterion['error_levels'])) {
            $levelsclass .= ' error';
        }
        $criteriontemplate .= html_writer::tag('td', $levelsstrtable, array('class' => $levelsclass));
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('criterionaddlevel', 'gradingform_erubric');
            $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][addlevel]',
                'id' => '{NAME}-criteria-{CRITERION-id}-levels-addlevel', 'value' => $value, 'title' => $value));
            $criteriontemplate .= html_writer::tag('td', $button, array('class' => 'addlevel'));
        }
        $displayremark = ($options['enableremarks'] && ($mode != gradingform_erubric_controller::DISPLAY_VIEW || $options['showremarksstudent']));
        if ($displayremark) {
            $currentremark = '';
            if (isset($value['remark'])) {
                $currentremark = $value['remark'];
            }
            if ($mode == gradingform_erubric_controller::DISPLAY_EVAL) {
                $input = html_writer::tag('textarea', htmlspecialchars($currentremark), array('name' => '{NAME}[criteria][{CRITERION-id}][remark]', 'rows' => '5'));
                $criteriontemplate .= html_writer::tag('td', $input, array('class' => 'remark'));
            } else if ($mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN) {
                $criteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][remark]', 'value' => $currentremark));
            }else if ($mode == gradingform_erubric_controller::DISPLAY_REVIEW || $mode == gradingform_erubric_controller::DISPLAY_VIEW) {
                $criteriontemplate .= html_writer::tag('td', $currentremark, array('class' => 'remark'));
            }
        }
        $criteriontemplate .= html_writer::end_tag('tr');

        $criteriontemplate = str_replace('{NAME}', $elementname, $criteriontemplate);
        $criteriontemplate = str_replace('{CRITERION-id}', $criterion['id'], $criteriontemplate);
        return $criteriontemplate;
    }

    /**
     * This function returns html code for displaying the tr of an enriched criterion. Depending on $mode it may be the
     * code to edit the enriched rubric, to preview it, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_erubric() to display the whole enriched rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty criteria to the
     * enriched rubric being designed.
     * In this case it will use macros like {NAME}, {LEVELS}, {CRITERION-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode enriched rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $criterion criterion data
     * @param string $levelsstr evaluated templates for this criterion levels
     * @param array|null $value (only in view mode) teacher's feedback on this criterion
     * @return string
     */
    public function enriched_criterion_template($mode, $options, $elementname = '{NAME}', $criterion = null, $levelsstr = '{LEVELS}', $value = null) {

        // Display enriched criteria according to options.
        if (!$options['showenrichedcriteriateacher'] && in_array($mode, array(gradingform_erubric_controller::DISPLAY_EVAL, gradingform_erubric_controller::DISPLAY_EVAL_FROZEN, gradingform_erubric_controller::DISPLAY_REVIEW))) {
            return '';
        }
        if (!$options['showenrichedcriteriastudent'] && ($mode == gradingform_erubric_controller::DISPLAY_VIEW || $mode == gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED)) {
            return '';
        }

        if ($criterion === null || !is_array($criterion) || !array_key_exists('id', $criterion)) {
            $criterion = array('id' => '{CRITERION-id}', 'class' => '{CRITERION-class}', 'criteriontype' => '{CRITERION-criteriontype}',
                                'collaborationtype' => '{CRITERION-collaborationtype}', 'coursemodules' => '{CRITERION-coursemodules}',
                                'operator' => '{CRITERION-operator}', 'referencetype' => '{CRITERION-referencetype}');
        } else {
            foreach (array('class', 'criteriontype', 'collaborationtype', 'coursemodules', 'referencetype', 'operator') as $key) {
                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists($key, $criterion)) {
                    $criterion[$key] = '';
                }
            }
        }

        $enrichedcriteriontemplate = html_writer::start_tag('tr', array('class' => 'enrichedcriterion'. $criterion['class'], 'id' => '{NAME}-enriched-criteria-{CRITERION-id}'));

        // Get the course modules for the criterion.
        $moduletypename = '';
        if ($criterion['coursemodules'] && is_array($criterion['coursemodules'])) {

            foreach ($criterion['coursemodules'] as $modulename => $moduleids) { // This is one iteration step, just to get the modulename.
                $moduletypename = $modulename;
                // Get the already created array of modules according to the type of criterion aka. $modulename.
                $moduleselectarray = array();
                switch ($modulename) {
                    case 'assignment':
                        $moduleselectarray = $this->assignments;
                        $deletebuttontitle = get_string('deleteassignment', 'gradingform_erubric');
                        break;
                    case 'activity':
                        $moduleselectarray = $this->activities;
                        $deletebuttontitle = get_string('deleteactivity', 'gradingform_erubric');
                        break;
                    case 'resource':
                        $moduleselectarray = $this->resources;
                        $deletebuttontitle = get_string('deleteresource', 'gradingform_erubric');
                        break;
                }
            }

        }

        // Criterion type options array.
        $criteriontypevalues = array(gradingform_erubric_controller::INTERACTION_TYPE_COLLABORATION=>get_string('selectcollaboration', 'gradingform_erubric'),
                                       gradingform_erubric_controller::INTERACTION_TYPE_GRADE=>get_string('selectgrade', 'gradingform_erubric'),
                                       gradingform_erubric_controller::INTERACTION_TYPE_STUDY=>get_string('selectstudy', 'gradingform_erubric'));

        // Collaboration type options array.
        $collaborationtypevalues = array(gradingform_erubric_controller::COLLABORATION_TYPE_ENTRIES=>get_string('collaborationtypeentries', 'gradingform_erubric'),
                                       gradingform_erubric_controller::COLLABORATION_TYPE_FILE_ADDS=>get_string('collaborationtypefileadds', 'gradingform_erubric'),
                                       gradingform_erubric_controller::COLLABORATION_TYPE_REPLIES=>get_string('collaborationtypereplies', 'gradingform_erubric'),
                                       gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS=>get_string('collaborationtypeinteractions', 'gradingform_erubric'));

        // Operator options array.
        $operatorvalues = array( gradingform_erubric_controller::OPERATOR_EQUAL => get_string('criterionoperatorequals', 'gradingform_erubric'),
                                 gradingform_erubric_controller::OPERATOR_MORE_THAN => get_string('criterionoperatormorethan', 'gradingform_erubric'));

        // Value type options array.
        $referencetypevalues = array( gradingform_erubric_controller::REFERENCE_STUDENT => get_string('referencetypenumber', 'gradingform_erubric'),
                                 gradingform_erubric_controller::REFERENCE_STUDENTS => get_string('referencetypepercentage', 'gradingform_erubric'));
        // Output select fields.
        $criteriontype = '';
        $collaborationtype = '';
        $coursemodules = '';
        $operator = '';
        $referencetype = '';

        // Output fields classes in case of errors.
        $errorclasses = array('criteriontype'=>'', 'collaborationtype'=>'', 'coursemodules'=>'', 'operator'=>'', 'referencetype'=>'');

        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {

            // Check for errors and update $errorclasses array.
            foreach ($errorclasses as $key=>$values) {
                if (array_key_exists('error_'.$key, $criterion)){
                    $errorclasses[$key] = ' error';
                }
            }

            //*** Course Modules for the criterion ***//
            if ($criterion['coursemodules'] && is_array($criterion['coursemodules'])) {

                // When showing raw form data from a not validated form,
                // change the coursemodules array to be the same with the array created from the database JSON string.
                if (!is_array($criterion['coursemodules'][$moduletypename][0])) $criterion['coursemodules'][$moduletypename][0] = $criterion['coursemodules'][$moduletypename];

                foreach ($criterion['coursemodules'][$moduletypename][0] as $mdlinstance) { // This loop iterates according to the number of course modules added

                    // Check if someone is trying to use this form as a template from another course,
                    // or if any of the previously stored enriched course modules have been erased
                    // and display error message.
                    if (!array_key_exists($mdlinstance, $moduleselectarray)){
                        $coursemodules .= html_writer::start_tag('li');
                        $coursemodules .= html_writer::start_tag('span', array('class'=>'missing'));
                        $coursemodules .= get_string('err_missingcoursemodule', 'gradingform_erubric');
                        $coursemodules .= html_writer::end_tag('span');
                        $coursemodules .= html_writer::end_tag('li');

                        // Update missing course modules error if needed.
                        if (!$this->missingmodules) $this->missingmodules = true;

                    }else{ // Proceed as normal.

                        // Create the appropriate instance array.
                        $tempinstance = explode('->', $mdlinstance);
                        $moduleid = $tempinstance[0];
                        $instanceid = $tempinstance[1];

                        $errorclass = '';
                        // Check for errors referring to collaboration criterion type.
                        if (array_key_exists('error_collaborationmodules', $criterion) && $moduleid==$this->chatmoduleid){
                            $errorclass = 'error';
                        }

                        $coursemodules .= html_writer::start_tag('li', array('id' => '{NAME}-criteria-{CRITERION-id}-'.$moduletypename.'-'.$moduleid.'-'.$instanceid, 'class'=>$errorclass));
                        $coursemodules .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][coursemodules]['.$moduletypename.'][]', 'value' => $mdlinstance));
                        $coursemodules .= $this->moduleicon[$moduleid].'&nbsp;'; // Display module icon.
                        $coursemodules .= html_writer::tag('span', $moduleselectarray[$mdlinstance], array('title'=>$moduleselectarray[$mdlinstance], 'class'=>'nameoverflowedit'));
                        $coursemodules .= html_writer::start_tag('div', array('class' => 'delete'));
                        $coursemodules .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}][deletemodule]',
                                            'id' => '{NAME}-criteria-{CRITERION-id}-'.$moduletypename.'-'.$moduleid.'-'.$instanceid.'-deletemodule',
                                            'value' => '{NAME}-criteria-{CRITERION-id}-'.$moduletypename.'-'.$moduleid.'-'.$instanceid, 'title' => $deletebuttontitle, 'tabindex'=> '-1'));
                        $coursemodules .= html_writer::end_tag('div');
                        $coursemodules .= html_writer::end_tag('li');
                    }
                }
            }

            // Enrichement select fields.
            $criteriontype     = html_writer::select($criteriontypevalues, '{NAME}[criteria][{CRITERION-id}][criteriontype]', $criterion['criteriontype'], array(''=>' '), array('class'=>'criteriontype', 'id' => '{NAME}-criteria-{CRITERION-id}-criteriontype'));
            $activity          = html_writer::select($this->activities, '{NAME}[criteria][{CRITERION-id}][activity]', '', array(''=>get_string('addnew', 'gradingform_erubric')), array('class'=>'activity', 'id' => '{NAME}-criteria-{CRITERION-id}-activity'));
            $assignment        = html_writer::select($this->assignments, '{NAME}[criteria][{CRITERION-id}][assignment]', '', array(''=>get_string('addnew', 'gradingform_erubric')), array('class'=>'assignment', 'id' => '{NAME}-criteria-{CRITERION-id}-assignment'));
            $resource          = html_writer::select($this->resources, '{NAME}[criteria][{CRITERION-id}][resource]', '', array(''=>get_string('addnew', 'gradingform_erubric')), array('class'=>'resource', 'id' => '{NAME}-criteria-{CRITERION-id}-resource'));
            $collaborationtype = html_writer::select($collaborationtypevalues, '{NAME}[criteria][{CRITERION-id}][collaborationtype]', $criterion['collaborationtype'], array(''=>' '), array('class'=>'collaborationtype', 'id' => '{NAME}-criteria-{CRITERION-id}-collaborationtype'));
            $operator          = html_writer::select($operatorvalues, '{NAME}[criteria][{CRITERION-id}][operator]', $criterion['operator'], array(''=>' '), array('class'=>'operator', 'id' => '{NAME}-criteria-{CRITERION-id}-operator'));
            $referencetype     = html_writer::select($referencetypevalues, '{NAME}[criteria][{CRITERION-id}][referencetype]', $criterion['referencetype'], array(''=>' '), array('class'=>'referencetype', 'id' => '{NAME}-criteria-{CRITERION-id}-referencetype'));

        } else {
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                $enrichedcriteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][criteriontype]', 'value' => $criterion['criteriontype']));
                $enrichedcriteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][collaborationtype]', 'value' => $criterion['collaborationtype']));
                $enrichedcriteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][operator]', 'value' => $criterion['operator']));
                $enrichedcriteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][referencetype]', 'value' => $criterion['referencetype']));
            }

            // If there are course modules to display.
            if ($criterion['coursemodules'] && is_array($criterion['coursemodules'])) {

                // If showing raw form data from a not validated form,
                // change the coursemodules array to be the same with the array created from the database JSON string.
                if (!is_array($criterion['coursemodules'][$moduletypename][0])) $criterion['coursemodules'][$moduletypename][0] = $criterion['coursemodules'][$moduletypename];

                foreach ($criterion['coursemodules'][$moduletypename][0] as $mdlinstance) { // This loop iterates according to the number of course modules added.

                    // Check if someone is trying to use this form as a template from another course
                    // or if any of the previously stored enriched course modules has been erased
                    // and display error message unless this form is of mode DISPLAY_VIEW or DISPLAY_PREVIEW_GRADED.
                    if (!array_key_exists($mdlinstance, $moduleselectarray)){
                        if ($mode != gradingform_erubric_controller::DISPLAY_VIEW && $mode != gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED){
                            $coursemodules .= html_writer::start_tag('li');
                            $coursemodules .= html_writer::start_tag('span', array('class'=>'missing'));
                            $coursemodules .= get_string('err_missingcoursemodule', 'gradingform_erubric');
                            $coursemodules .= html_writer::end_tag('span');
                            $coursemodules .= html_writer::end_tag('li');

                            // Update missing course modules error if needed.
                            if (!$this->missingmodules) $this->missingmodules = true;
                        }
                    }else{ // Proceed as normal.
                        $tempinstance = explode('->', $mdlinstance); // Create the appropriate instance array.
                        $moduleid = $tempinstance[0];
                        $instanceid = $tempinstance[1];

                        $coursemodules .= html_writer::start_tag('li', array('id' => '{NAME}-criteria-{CRITERION-id}-'.$moduletypename.'-'.$moduleid.'-'.$instanceid));
                        $coursemodules .= $this->moduleicon[$moduleid].'&nbsp;'; // Display module icon

                        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                            $enrichedcriteriontemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][coursemodules]['.$moduletypename.'][]', 'value' => $mdlinstance));
                        }

                        $coursemodules .= html_writer::tag('span', $moduleselectarray[$mdlinstance], array('title'=>$moduleselectarray[$mdlinstance], 'class'=>'nameoverflow'));
                        $coursemodules .= html_writer::end_tag('li');
                    }
                }
            }

            if ($criterion['criteriontype']) $criteriontype = html_writer::tag('div', $criteriontypevalues[$criterion['criteriontype']], array('class'=>'plainvaluerich'));
            if ($criterion['collaborationtype']) $collaborationtype = html_writer::tag('div', $collaborationtypevalues[$criterion['collaborationtype']], array('class'=>'plainvaluerich'));
            if ($criterion['operator']) $operator = html_writer::tag('div', $operatorvalues[$criterion['operator']], array('class'=>'plainvaluerich'));
            if ($criterion['referencetype']) $referencetype = html_writer::tag('div', $referencetypevalues[$criterion['referencetype']], array('class'=>'plainvaluerich'));
        }

        // Handle tr's first 2 columns.
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $enrichedcriteriontemplate .= html_writer::start_tag('td', array('colspan' => '2', 'class' => 'enrichedcriteria', 'id' => '{NAME}-criteria-{CRITERION-id}-enriched_interaction'));
        }else{
            $enrichedcriteriontemplate .= html_writer::start_tag('td', array('class' => 'enrichedcriteria', 'id' => '{NAME}-criteria-{CRITERION-id}-enriched_interaction'));
        }
        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'enriched-wrapper'));
        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'rich'.$errorclasses['criteriontype']));
        $enrichedcriteriontemplate .= get_string('participationin', 'gradingform_erubric');
        $enrichedcriteriontemplate .= $criteriontype;
        $enrichedcriteriontemplate .= html_writer::end_tag('div');

        // Check if collaboration type should be displayed.
        if ($collaborationtype || $mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'rich'.$errorclasses['collaborationtype']));
            $enrichedcriteriontemplate .= get_string('collaborationtype', 'gradingform_erubric');
            $enrichedcriteriontemplate .= $collaborationtype;
            $enrichedcriteriontemplate .= html_writer::end_tag('div');
        }

        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'rich coursemodule'.$errorclasses['coursemodules']));
        $enrichedcriteriontemplate .= get_string('coursemoduleis', 'gradingform_erubric');
        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'modulecontainer'));
        $enrichedcriteriontemplate .= html_writer::start_tag('ul');
        $enrichedcriteriontemplate .= $coursemodules;
        $enrichedcriteriontemplate .= html_writer::end_tag('ul');

        // Check if course modules selects should be displayed.
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $enrichedcriteriontemplate .= $activity;
            $enrichedcriteriontemplate .= $resource;
            $enrichedcriteriontemplate .= $assignment;
        }

        $enrichedcriteriontemplate .= html_writer::end_tag('div'); // End of module container with contents and select fields
        $enrichedcriteriontemplate .= html_writer::end_tag('div');
        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'rich'.$errorclasses['operator']));
        $enrichedcriteriontemplate .= get_string('participationis', 'gradingform_erubric');
        $enrichedcriteriontemplate .= $operator;
        $enrichedcriteriontemplate .= html_writer::end_tag('div');
        $enrichedcriteriontemplate .= html_writer::start_tag('div', array('class' => 'rich'.$errorclasses['referencetype']));
        $enrichedcriteriontemplate .= get_string('participationon', 'gradingform_erubric');
        $enrichedcriteriontemplate .= $referencetype;
        $enrichedcriteriontemplate .= html_writer::end_tag('div');
        $enrichedcriteriontemplate .= html_writer::end_tag('div');
        $enrichedcriteriontemplate .= html_writer::end_tag('td');

        // Create the levels table.
        $levelsstrtable = html_writer::tag('table', html_writer::tag('tr', $levelsstr, array('id' => '{NAME}-enriched-criteria-{CRITERION-id}-levels')));
        $levelsclass = 'levels';
        if (isset($criterion['error_levels'])) {
            $levelsclass .= ' error';
        }

        $enrichedcriteriontemplate .= html_writer::tag('td', $levelsstrtable, array('class' => $levelsclass)); // Attach the levels table.

        // Check if help icon for enrichment should be displayed.
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            global $OUTPUT;
            $identifier = 'enrichment';
            $component = 'gradingform_erubric';
            $linktext = '';
            $helpicon = $OUTPUT->help_icon($identifier, $component, $linktext);
            $enrichedcriteriontemplate .= html_writer::tag('td', $helpicon, array('class' => 'addlevel'));
        }

        // Check if the benchmark from evaluation should be displayed or leave an empty td.
        $displayremark = ($options['enableremarks'] && $mode == gradingform_erubric_controller::DISPLAY_VIEW && $options['showremarksstudent']);
        $displaybenchmark = (($options['showenrichedbenchmarkstudent'] && $mode == gradingform_erubric_controller::DISPLAY_VIEW) ||
                             ($options['showenrichedbenchmarkteacher'] && ($mode == gradingform_erubric_controller::DISPLAY_EVAL ||
                                                                           $mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN)));

        if ($displaybenchmark) { // Display benchmark.

            // Get the benchmarks (if they exists) from values array, to display them to student.
            if (!array_key_exists('enrichedbenchmark', $criterion) && isset($value['enrichedbenchmark']))
                $criterion['enrichedbenchmark'] = $value['enrichedbenchmark'];
            if (!array_key_exists('enrichedbenchmarkstudent', $criterion) && isset($value['enrichedbenchmarkstudent']))
                $criterion['enrichedbenchmarkstudent'] = $value['enrichedbenchmarkstudent'];
            if (!array_key_exists('enrichedbenchmarkstudents', $criterion) && isset($value['enrichedbenchmarkstudents']))
                $criterion['enrichedbenchmarkstudents'] = $value['enrichedbenchmarkstudents'];

            $benchmarkstr = '';

            // If there are benchmarks in the criterion data (even null).
            if (array_key_exists('enrichedbenchmark', $criterion) || array_key_exists('enrichedbenchmarkstudents', $criterion)){

                //** In case of evalluation, put value in hidden field to update enriched rubric fillings. **//
                if (($mode == gradingform_erubric_controller::DISPLAY_EVAL || $mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN)){
                    // Criterion benchmark.
                    if (strlen((String)$criterion['enrichedbenchmark'])>0){
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmark]', 'value' => $criterion['enrichedbenchmark']));
                    }else{
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmark]', 'value' => ''));
                    }
                    // Student benchmark.
                    if (strlen((String)$criterion['enrichedbenchmarkstudent'])>0){
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmarkstudent]', 'value' => $criterion['enrichedbenchmarkstudent']));
                    }else{
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmarkstudent]', 'value' => ''));
                    }
                    // Students benchmark.
                    if (strlen((String)$criterion['enrichedbenchmarkstudents'])>0) {
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmarkstudents]', 'value' => $criterion['enrichedbenchmarkstudents']));
                    }else{
                        $benchmarkstr .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][enrichedbenchmarkstudents]', 'value' => ''));
                    }
                }

                // If there was a reference according to students avaluation, show student and students avereges.
                if ($criterion['referencetype'] == gradingform_erubric_controller::REFERENCE_STUDENTS && array_key_exists('enrichedbenchmarkstudents', $criterion) && !is_null($criterion['enrichedbenchmarkstudents'])) {
                    $temparr = array('student'=> $criterion['enrichedbenchmarkstudent'], 'students'=>$criterion['enrichedbenchmarkstudents']);
                    $temparr = (object)$temparr;
                    $benchmarkstr .= get_string('benchmarkinfoall', 'gradingform_erubric', $temparr);
                // If the student benchmark is valid, display it.
                } else if (array_key_exists('enrichedbenchmark', $criterion) && !is_null($criterion['enrichedbenchmark'])) {
                    $benchmarkstr .= get_string('benchmarkinfo', 'gradingform_erubric', $criterion['enrichedbenchmark']);
                // Student or sudents benchmarks have no valid value.
                }else{
                    $benchmarkstr .= get_string('benchmarkinfonull', 'gradingform_erubric');
                }

            // If there are no benchmarks in the criterion and this form is displayed to a student.
            }else if ($mode == gradingform_erubric_controller::DISPLAY_VIEW) {
                $benchmarkstr .= get_string('benchmarkinfonull', 'gradingform_erubric');
            }
            $enrichedcriteriontemplate .= html_writer::tag('td', $benchmarkstr, array('class' => 'addlevel'));
        }

        if ($displayremark && !$displaybenchmark) { // Display empty td.
            $enrichedcriteriontemplate .= html_writer::tag('td', '', array('class' => 'addlevel'));
        }

        $enrichedcriteriontemplate .= html_writer::end_tag('tr'); // Close the enriched criterion table row.
        $enrichedcriteriontemplate = str_replace('{NAME}', $elementname, $enrichedcriteriontemplate);
        $enrichedcriteriontemplate = str_replace('{CRITERION-id}', $criterion['id'], $enrichedcriteriontemplate);
        return $enrichedcriteriontemplate;
    }

    /**
     * This function returns html code for displaying one simple level of one criterion. Depending on $mode
     * it may be the code to edit enriched rubric, to preview it, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_erubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty level to the
     * criterion during the design of the enriched rubric.
     * In this case it will use macros like {NAME}, {CRITERION-id}, {LEVEL-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have property $level['checked'] whether this level is checked
     * @return string
     */
    public function level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {

        if (!isset($level['id'])) {
            $level = array('id' => '{LEVEL-id}', 'definition' => '{LEVEL-definition}', 'score' => '{LEVEL-score}', 'class' => '{LEVEL-class}', 'checked' => false, 'enrichedvalue' => '{LEVEL-enrichedvalue}');
        } else {
            foreach (array('score', 'definition', 'class', 'checked', 'enrichedvalue') as $key) {
                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists($key, $level)) {
                    $level[$key] = '';
                }
            }
        }

        // Template for one level within one criterion.
        $tdattributes = array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}', 'class' => 'level'. $level['class']);
        if (isset($level['tdwidth'])) {
            $tdattributes['width'] = round($level['tdwidth']).'%';
        }
        $leveltemplate = html_writer::start_tag('td', $tdattributes);
        $leveltemplate .= html_writer::start_tag('div', array('class' => 'level-wrapper'));
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $definition = html_writer::tag('textarea', htmlspecialchars($level['definition']), array('name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'cols' => '10', 'rows' => '4'));
            $score = html_writer::empty_tag('input', array('type' => 'text', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]', 'size' => '3', 'value' => $level['score']));
        } else {
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'value' => $level['definition']));
                $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]', 'value' => $level['score']));
            }
            $definition = $level['definition'];
            $score = $level['score'];

        }
        if ($mode == gradingform_erubric_controller::DISPLAY_EVAL) {
            $input = html_writer::empty_tag('input', array('type' => 'radio', 'name' => '{NAME}[criteria][{CRITERION-id}][levelid]', 'value' => $level['id']) +
                    ($level['checked'] ? array('checked' => 'checked') : array()));
            $leveltemplate .= html_writer::tag('div', $input, array('class' => 'radio'));
        }
        if ($mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN && $level['checked']) {
            $leveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levelid]', 'value' => $level['id']));
        }
        $score = html_writer::tag('span', $score, array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-score', 'class' => 'scorevalue'));

        $definitionclass = 'definition';
        if (isset($level['error_definition'])) {
            $definitionclass .= ' error';
        }
        $leveltemplate .= html_writer::tag('div', $definition, array('class' => $definitionclass, 'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition'));
        $displayscore = true;
        if (!$options['showscoreteacher'] && in_array($mode, array(gradingform_erubric_controller::DISPLAY_EVAL, gradingform_erubric_controller::DISPLAY_EVAL_FROZEN, gradingform_erubric_controller::DISPLAY_REVIEW))) {
            $displayscore = false;
        }
        if (!$options['showscorestudent'] && in_array($mode, array(gradingform_erubric_controller::DISPLAY_VIEW, gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED))) {
            $displayscore = false;
        }
        if ($displayscore) {
            $scoreclass = 'score';
            if (isset($level['error_score'])) {
                $scoreclass .= ' error';
            }

            $leveltemplate .= html_writer::tag('div', get_string('scorepostfix', 'gradingform_erubric', $score), array('class' => $scoreclass));

        }
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('leveldelete', 'gradingform_erubric');
            $button = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][delete]', 'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-delete', 'value' => $value, 'title' => $value, 'tabindex' => -1));
            $leveltemplate .= html_writer::tag('div', $button, array('class' => 'delete'));
        }
        $leveltemplate .= html_writer::end_tag('div'); // .level-wrapper
        $leveltemplate .= html_writer::end_tag('td'); // .level

        $leveltemplate = str_replace('{NAME}', $elementname, $leveltemplate);
        $leveltemplate = str_replace('{CRITERION-id}', $criterionid, $leveltemplate);
        $leveltemplate = str_replace('{LEVEL-id}', $level['id'], $leveltemplate);
        return $leveltemplate;
    }

    /**
     * This function returns html code for displaying one enriched level of one criterion. Depending on $mode
     * it may be the code to edit enriched rubric, to preview it, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_erubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty level to the
     * criterion during the design of the enriched rubric.
     * In this case it will use macros like {NAME}, {CRITERION-id}, {LEVEL-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have property $level['checked'] whether this level is checked
     * @return string
     */
    public function enriched_level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {

        if (!isset($level['id'])) {
            $level = array('id' => '{LEVEL-id}', 'class' => '{LEVEL-class}', 'checked' => false, 'enrichedvalue' => '{LEVEL-enrichedvalue}', 'enrichedvaluesuffix'=>'');
        } else {
            foreach (array('class', 'checked', 'enrichedvalue', 'enrichedvaluesuffix') as $key) {
                // Set missing array elements to empty strings to avoid warnings.
                if (!array_key_exists($key, $level)) {
                    $level[$key] = '';
                }
            }
        }

        // Template for one level within one criterion.
        $tdattributes = array('id' => '{NAME}-enriched-criteria-{CRITERION-id}-levels-{LEVEL-id}', 'class' => 'enrichedlevel'. $level['class']);
        if (isset($level['tdwidth'])) {
            $tdattributes['width'] = round($level['tdwidth']).'%';
        }
        $enrichedleveltemplate = html_writer::start_tag('td', $tdattributes);

        // Check the options about showing the levels' enriched value.
        $displayvalue = true;
        $enrichedvalueclass = 'richvalue';
        if (!$options['showenrichedvalueteacher'] && in_array($mode, array(gradingform_erubric_controller::DISPLAY_EVAL, gradingform_erubric_controller::DISPLAY_EVAL_FROZEN, gradingform_erubric_controller::DISPLAY_REVIEW))) {
            $displayvalue = false;
        }
        if (!$options['showenrichedvaluestudent'] && ($mode == gradingform_erubric_controller::DISPLAY_VIEW || $mode == gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED)) {
            $displayvalue = false;
        }
        if ($displayvalue && (isset($level['error_enrichedvalueformat']) || isset($level['error_enrichedvaluemissing']))) {
                $enrichedvalueclass .= ' error';
        }
        if ($mode == gradingform_erubric_controller::DISPLAY_EVAL) {
            if ($level['checked']) $enrichedvalueclass .= ' checked';
        }
        $enrichedleveltemplate .= html_writer::start_tag('div', array('class' => $enrichedvalueclass));
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $enrichedvalue = html_writer::empty_tag('input', array('type' => 'text', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][enrichedvalue]', 'size' => '3', 'value' => $level['enrichedvalue']));
        } else {
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                $enrichedleveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][enrichedvalue]', 'value' => $level['enrichedvalue']));
            }

            $enrichedvalue = $level['enrichedvalue'];
        }

        if ($mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN && $level['checked']) {
            $enrichedleveltemplate .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][enrichedvalue]', 'value' => $level['enrichedvalue']));
        }

        $enrichedvalue = html_writer::tag('span', $enrichedvalue, array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-enrichedvalue', 'class' => 'enrichedvalue'));
        $enrichedvalue .= html_writer::tag('i', $level['enrichedvaluesuffix'], array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-enrichedvaluesuffix'));

        if ($displayvalue) {
            $enrichedleveltemplate .= $enrichedvalue;
        }

        $enrichedleveltemplate .= html_writer::end_tag('div'); // .richvalue
        $enrichedleveltemplate .= html_writer::end_tag('td'); // .level

        $enrichedleveltemplate = str_replace('{NAME}', $elementname, $enrichedleveltemplate);
        $enrichedleveltemplate = str_replace('{CRITERION-id}', $criterionid, $enrichedleveltemplate);
        $enrichedleveltemplate = str_replace('{LEVEL-id}', $level['id'], $enrichedleveltemplate);
        return $enrichedleveltemplate;
    }

    /**
     * This function returns html code for displaying enriched rubric template (content before and after
     * criteria list). Depending on $mode it may be the code to edit the eriched rubric, to preview it,
     * to evaluate somebody or to review the evaluation.
     *
     * This function is called from display_erubric() to display the whole rubric.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this rubric's criteria
     * @return string
     */
    protected function erubric_template($mode, $options, $elementname, $criteriastr) {
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode.
        switch ($mode) {
            case gradingform_erubric_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable'; break;
            case gradingform_erubric_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';  break;
            case gradingform_erubric_controller::DISPLAY_PREVIEW:
            case gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';  break;
            case gradingform_erubric_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable'; break;
            case gradingform_erubric_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';  break;
            case gradingform_erubric_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';  break;
            case gradingform_erubric_controller::DISPLAY_VIEW:
                $classsuffix = ' view';  break;
        }

        $enrichedrubrictemplate = '';

        // Display error message in case of missing course modules.
        if ($this->missingmodules && $mode != gradingform_erubric_controller::DISPLAY_VIEW && $mode != gradingform_erubric_controller::DISPLAY_PREVIEW_GRADED){
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL || $mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN){
                $enrichedrubrictemplate .= $this->box(
                    html_writer::tag('div', get_string('err_missingcoursemodulesedit', 'gradingform_erubric'), array('class' => 'missingmodule'))
                    , 'generalbox');
            }else{
                $enrichedrubrictemplate .= $this->box(
                    html_writer::tag('div', get_string('err_missingcoursemodules', 'gradingform_erubric'), array('class' => 'missingmodule'))
                    , 'generalbox');
            }
        }

        $enrichedrubrictemplate .= html_writer::start_tag('div', array('id' => 'erubric-{NAME}', 'class' => 'clearfix gradingform_erubric'.$classsuffix));
        $enrichedrubrictemplate .= html_writer::tag('table', $criteriastr, array('class' => 'criteria', 'id' => '{NAME}-criteria'));
        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('addcriterion', 'gradingform_erubric');
            $input = html_writer::empty_tag('input', array('type' => 'submit', 'name' => '{NAME}[criteria][addcriterion]', 'id' => '{NAME}-criteria-addcriterion', 'value' => $value, 'title' => $value));
            $enrichedrubrictemplate .= html_writer::tag('div', $input, array('class' => 'addcriterion'));
        }
        $enrichedrubrictemplate .= $this->erubric_edit_options($mode, $options);
        $enrichedrubrictemplate .= html_writer::end_tag('div');

        return str_replace('{NAME}', $elementname, $enrichedrubrictemplate);
    }

    /**
     * Generates html template to view/edit the enriched rubric options. Expression {NAME} is used in
     * template for the form element name.
     *
     * @param int $mode
     * @param array $options
     * @return string
     */
    protected function erubric_edit_options($mode, $options) {
        if ($mode != gradingform_erubric_controller::DISPLAY_EDIT_FULL
                && $mode != gradingform_erubric_controller::DISPLAY_EDIT_FROZEN
                && $mode != gradingform_erubric_controller::DISPLAY_PREVIEW) {
            // Options are displayed only in edit mode.
            return;
        }

        $html = html_writer::start_tag('div', array('class' => 'options'));
        $html .= html_writer::tag('div', get_string('rubricoptions', 'gradingform_erubric'), array('class' => 'optionsheading'));
        $attrs = array('type' => 'hidden', 'name' => '{NAME}[options][optionsset]', 'value' => 1);
        foreach ($options as $option => $value) {
            $html .= html_writer::start_tag('div', array('class' => 'option '.$option));
            $attrs = array('name' => '{NAME}[options]['.$option.']', 'id' => '{NAME}-options-'.$option);
            switch ($option) {
                case 'sortlevelsasc':
                    // Display option as dropdown.
                    $html .= html_writer::tag('span', get_string($option, 'gradingform_erubric'), array('class' => 'label'));
                    $value = (int)(!!$value); // Make sure $value is either 0 or 1.
                    if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL) {
                        $selectoptions = array(0 => get_string($option.'0', 'gradingform_erubric'), 1 => get_string($option.'1', 'gradingform_erubric'));
                        $valuestr = html_writer::select($selectoptions, $attrs['name'], $value, false, array('id' => $attrs['id']));
                        $html .= html_writer::tag('span', $valuestr, array('class' => 'value'));
                        // TODO add here button 'Sort levels'
                    } else {
                        $html .= html_writer::tag('span', get_string($option.$value, 'gradingform_erubric'), array('class' => 'value'));
                        if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN) {
                            $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                        }
                    }
                    break;
                case 'enrichmentoptions':
                    // Seperator for enrichment options.
                    $html .= html_writer::tag('span', get_string($option, 'gradingform_erubric'), array('class' => 'optionsheading'));
                    break;
                default:
                    if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN && $value) {
                        $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
                    }
                    // Display option as checkbox.
                    $attrs['type'] = 'checkbox';
                    $attrs['value'] = 1;
                    if ($value) {
                        $attrs['checked'] = 'checked';
                    }
                    if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FROZEN || $mode == gradingform_erubric_controller::DISPLAY_PREVIEW) {
                        $attrs['disabled'] = 'disabled';
                        unset($attrs['name']);
                    }
                    $html .= html_writer::empty_tag('input', $attrs);
                    $html .= html_writer::tag('label', get_string($option, 'gradingform_erubric'), array('for' => $attrs['id']));
                    break;
            }
            $html .= html_writer::end_tag('div'); // .option
        }
        $html .= html_writer::end_tag('div'); // .options
        return $html;
    }

    /**
     * This function returns html code for displaying the enriched rubric. Depending on $mode it may be the code
     * to edit the enriched rubric, to preview it, to evaluate somebody or to review the evaluation.
     *
     * It is very unlikely that this function needs to be overriden by theme. It does not produce
     * any html code, it just prepares data about rubric design and evaluation, adds the CSS
     * class to elements and calls the functions level_template, criterion_template and
     * erubric_template
     *
     * JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param array $criteria data about the rubric design
     * @param int $mode rubric display mode @see gradingform_erubric_controller
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array $values evaluation result
     * @return string
     */
    public function display_erubric($criteria, $options, $mode, $elementname = null, $values = null) {

        $criteriastr = '';
        $cnt = 0;
        foreach ($criteria as $id => $criterion) {
            $criterion['class'] = $this->get_css_class_suffix($cnt++, sizeof($criteria) -1);
            $criterion['id'] = $id;
            $levelsstr = '';

            $levelcnt = 0;
            if (isset($values['criteria'][$id])) {
                $criterionvalue = $values['criteria'][$id];
            } else {
                $criterionvalue = null;
            }

            $enrichedlevelstr = '';
            $valuesuffixstr = '';
            if (!array_key_exists('criteriontype', $criterion)) // Avoid warnings...
                $criterion['criteriontype'] = '';

            if ($criterion['criteriontype']){
                if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENT){
                    if ($criterion['criteriontype']==gradingform_erubric_controller::INTERACTION_TYPE_GRADE){
                        $valuesuffixstr = get_string('enrichedvaluesuffixpoints', 'gradingform_erubric');
                    }else if (array_key_exists('collaborationtype', $criterion) && $criterion['collaborationtype']==gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS) {
                        $valuesuffixstr = get_string('enrichedvaluesuffixstudents', 'gradingform_erubric');
                    }else{
                        $valuesuffixstr = get_string('enrichedvaluesuffixtimes', 'gradingform_erubric');
                    }
                }else{
                    $valuesuffixstr = get_string('enrichedvaluesuffixpercent', 'gradingform_erubric');
                }
            }

            // In case of evaluation if the criterion is enriched, determine criterion value (aka. level checked) and update criterion.
            if ($criterion['criteriontype'] && ($mode == gradingform_erubric_controller::DISPLAY_EVAL || $mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN)) {
                $this->evaluate_enrichment($criterion, $options);
            }

            foreach ($criterion['levels'] as $levelid => $level) {
                $level['id'] = $levelid;
                $level['class'] = $this->get_css_class_suffix($levelcnt++, sizeof($criterion['levels']) -1);
                $level['tdwidth'] = 100/count($criterion['levels']);

                // If this level belongs to a just evaluated enriched criterion.
                if (array_key_exists('checkedenrich', $criterion)){
                    if (array_key_exists('checked', $level)){
                        $level['class'] .= ' currentchecked';
                    }

                    // In case enrichment evaluation failed and can be overridden.
                    if ($options['overideenrichmentevaluation'] && is_null($criterion['checkedenrich'])){

                        // If this was already checked by evaluator display the choice.
                        $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));
                        if ($level['checked']) {
                            $level['class'] .= ' currentchecked checked';
                        }
                        // Display unfrozen criterion levels for (re-)evaluation.
                        $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);

                        // Uncheck level in order not to affect enriched levels.
                        unset($level['checked']);

                    // Enrichment evaluation succesful, freeze levels!
                    }else{
                        $level['class'] .= ' currentenenriched';
                        $levelsstr .= $this->level_template(gradingform_erubric_controller::DISPLAY_EVAL_FROZEN, $options, $elementname, $id, $level);
                    }

                // Simple criteria.
                }else{

                    $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));

                    if ($level['checked'] && ($mode == gradingform_erubric_controller::DISPLAY_EVAL_FROZEN || $mode == gradingform_erubric_controller::DISPLAY_REVIEW || $mode == gradingform_erubric_controller::DISPLAY_VIEW)) {
                        $level['class'] .= ' checked';
                        // In mode DISPLAY_EVAL the class 'checked' will be added by JS if it is enabled. If JS is not enabled, the 'checked' class will only confuse.
                    }

                    if (isset($criterionvalue['savedlevelid']) && ((int)$criterionvalue['savedlevelid'] === $levelid)) {
                        $level['class'] .= ' currentchecked';
                    }

                    $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);
                }

                $level['enrichedvaluesuffix'] = $valuesuffixstr;
                $enrichedlevelstr .= $this->enriched_level_template($mode, $options, $elementname, $id, $level);
            }

            $criteriastr .= $this->criterion_template($mode, $options, $elementname, $criterion, $levelsstr, $criterionvalue);

            // If this rubric criterion is not enriched (for example, does not have a criterion type defined) and is not DISPLAY_EDIT_FULL
            // Do not display the enrichment fields.
            if ($mode == gradingform_erubric_controller::DISPLAY_EDIT_FULL || $criterion['criteriontype'])
            $criteriastr .= $this->enriched_criterion_template($mode, $options, $elementname, $criterion, $enrichedlevelstr, $criterionvalue);

        }

        /*
        if ($this->missingmodules){    // Display error message in case of missing course modules.
            $enrichedrubrictemplate .= $this->box(
                html_writer::tag('div', get_string('err_missingcoursemodulesedit', 'gradingform_erubric'), array('class' => 'missingmodule'))
                , 'generalbox');
        }
        */
        return $this->erubric_template($mode, $options, $elementname, $criteriastr);
    }

    /**
     * Automatic student evaluation
     *
     * This function automatically avaluates the student according to enrichment criteria
     * which are cross referenced with the data obtained through Learning Analytics data mining procedures.
     *
     * @param array $criterion data about the rubric design
     * @param array $options enriched rubric options
     */
    protected function evaluate_enrichment(&$criterion, $options) {
        global $DB;
        global $PAGE;
        $moduletypename         = null;
        $benchmarkstudent       = null;
        $benchmarkstudents      = null;
        $benchmarkcriterion     = null;
        $untiltime              = null;
        $fromtime               = null;
        $selectallstudents      = null;
        $participatingstudents  = null;
        $iterations             = 0;
        $sql                    = null;

        // Get the current assignment data.
        $curmoduleid = (int)$PAGE->cm->module;
        $curmodulename = $PAGE->cm->modname;
        $curcmid = $PAGE->cm->id;
        $gradingmoduleid = $PAGE->cm->instance;
        $courseid = $PAGE->cm->course;

        //**** For moodle 2.2 versions assignment modules ****//
        $studentid = optional_param('userid', '', PARAM_INT);

        //**** For moodle 2.3 onwards assignment module ****//
        if (!$studentid) {
            global $CFG;
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            $context = context_module::instance($PAGE->cm->id);
            $assignment = new assign($context, $PAGE->cm, $PAGE->cm->course);
            $useridlist = array_keys($assignment->list_participants(0, true));
            sort($useridlist); // The users list containing the IDs of students evaluated.
            $rownum = $_REQUEST['rownum'];

            // Check if next or previous buttons are pressed in the avaluation form.
            if (array_key_exists('saveandshownext', $_REQUEST) || array_key_exists('nosaveandnext', $_REQUEST)) {
                $rownum++;
            }
            if (array_key_exists('nosaveandprevious', $_REQUEST)) {
                $rownum--;
            }
            $studentid = $useridlist[$rownum];
        }

        $selectindividual = "= $studentid";

        // SQL for including all course enroled students.
        $selectallstudents = "IN (SELECT u.id
                                  FROM {user} u
                                      INNER JOIN {role_assignments} ra ON u.id = ra.userid
                                      INNER JOIN {role} r ON ra.roleid = r.id
                                      INNER JOIN {context} c ON ra.contextid = c.id
                                  WHERE c.contextlevel = 50
                                      AND c.instanceid = $courseid
                                      AND r.name = 'Student')";

        // Timestamp enrichment calculations according to assignment module (old or new type assignments).
        // In case of old type assignment...
        if ($curmoduleid==$this->oldassignmoduleid) {
            // Get potential enrichment due date.
            if ($options['timestampenrichmentend']){
                $sql = 'SELECT asmnt.timedue AS duedate FROM {assignment} asmnt WHERE asmnt.id = '.$gradingmoduleid;
                $untiltime = $DB->get_field_sql($sql, null);
            }
            // Get potential availability start time.
            if ($options['timestampenrichmentstart']){
                $sql = 'SELECT asmnt.timeavailable AS startdate FROM {assignment} asmnt WHERE asmnt.id = '.$gradingmoduleid;
                $fromtime = $DB->get_field_sql($sql, null);
            }

        // In case of new type assignment...
        }else if ($curmoduleid==$this->newassignmoduleid) {
            // Get potential enrichment due date.
            if ($options['timestampenrichmentend']){
                $sql = 'SELECT asmnt.duedate AS duedate FROM {assign} asmnt WHERE asmnt.id = '.$gradingmoduleid;
                $untiltime = $DB->get_field_sql($sql, null);
            }
            // Get potential availability start time.
            if ($options['timestampenrichmentstart']){
                $sql = 'SELECT asmnt.allowsubmissionsfromdate AS startdate FROM {assign} asmnt WHERE asmnt.id = '.$gradingmoduleid;
                $fromtime = $DB->get_field_sql($sql, null);
            }
        }

        // Retrieve Learning Analytics data according to criterion type (collaboration - study - grade).
        switch ($criterion['criteriontype']) {

            // In case of checking student studying, perform data mining from study logs of selected course modules (resources).
            case gradingform_erubric_controller::INTERACTION_TYPE_STUDY:
                $moduletypename = 'resource';
                foreach ($criterion['coursemodules'][$moduletypename][0] as $mdlinstance) { // Iterate through course modules.
                    $tempinstance = explode('->', $mdlinstance);
                    $moduleid = $tempinstance[0];
                    $instanceid = $tempinstance[1];

                    $sql = "SELECT COUNT(lg.id) AS TOTALS
                            FROM {log} lg
                                INNER JOIN {course_modules} cm ON (lg.cmid = cm.id)
                            WHERE   lg.userid $selectindividual
                                AND lg.action = 'view'
                                AND cm.course = $courseid
                                AND cm.module = $moduleid
                                AND cm.instance = $instanceid ";

                    $this->get_value_from_learning_analytics($benchmarkstudent, $sql, 'lg', 'time', $fromtime, $untiltime, 1);

                    // If the criterion has a global reference according to all students participating.
                    if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){
                        $sql = "SELECT COUNT(u.id) AS TOTALS
                                  FROM {user} u
                                      INNER JOIN {role_assignments} ra ON u.id = ra.userid
                                      INNER JOIN {role} r ON ra.roleid = r.id
                                      INNER JOIN {context} c ON ra.contextid = c.id
                                  WHERE c.contextlevel = 50
                                      AND c.instanceid = $courseid
                                      AND r.name = 'Student'";
                        $count = $DB->get_field_sql($sql, null);

                        if ($count && $count>0) { // If there is at least one student.
                            $iterations++;
                            $sql = "SELECT COUNT(lg.id) AS TOTALS
                                    FROM {log} lg
                                        INNER JOIN {course_modules} cm ON (lg.cmid = cm.id)
                                    WHERE   lg.userid $selectallstudents
                                        AND lg.action = 'view'
                                        AND cm.course = $courseid
                                        AND cm.module = $moduleid
                                        AND cm.instance = $instanceid ";

                            $this->get_value_from_learning_analytics($benchmarkstudents, $sql, 'lg', 'time', $fromtime, $untiltime, $count);
                        }
                    }
                }
                break;

            // In case of checking student grades, perform data mining from grade logs of selected course modules (assignments).
            case gradingform_erubric_controller::INTERACTION_TYPE_GRADE:
                $moduletypename = 'assignment';
                foreach ($criterion['coursemodules'][$moduletypename][0] as $mdlinstance) { // Iterate through course modules.
                    $tempinstance = explode('->', $mdlinstance);
                    $moduleid = $tempinstance[0];
                    $instanceid = $tempinstance[1];
                    $iterations++;
                    $tempstudentcurrgrade = null;

                    $sql = "SELECT gr.finalgrade AS Grade
                            FROM {grade_grades} gr
                                INNER JOIN {grade_items} gi ON (gi.id = gr.itemid)
                                INNER JOIN {modules} md ON (md.name = gi.itemmodule)
                            WHERE   gr.userid $selectindividual
                                AND gi.courseid = $courseid
                                AND md.id = $moduleid
                                AND gi.iteminstance = $instanceid ";

                    $this->get_value_from_learning_analytics($tempstudentcurrgrade, $sql, null, null, null, null, 1);

                    // If the student has not been graded for this course assignment,
                    // there is no point on calculating his or the others benchmark.
                    if (!is_null($tempstudentcurrgrade)) {
                        // Get maximum grade score for current assignment module in order to define student performance according to 100 scale.
                        $sql = "SELECT assmnt.grade AS maxgrade ";
                        if ($moduleid==$this->newassignmoduleid) {
                            $sql .= "FROM {assign} assmnt ";    // New type assignment modules (Moodle 2.3+)
                        }else{
                            $sql .= "FROM {assignment} assmnt "; // Old type assignment modules (Moodle 2.2)
                        }
                        $sql .= "WHERE   assmnt.id = $instanceid";
                        $tempassignmax = (float)$DB->get_field_sql($sql, null);
                        $benchmarkstudent += (float)round($tempstudentcurrgrade/$tempassignmax*100, 2);

                        // If the criterion has a global reference according to all students grades.
                        if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){
                            $sql = "SELECT AVG(gr.finalgrade) AS AvgGrades
                                FROM {grade_grades} gr
                                    INNER JOIN {grade_items} gi ON (gi.id = gr.itemid)
                                    INNER JOIN {modules} md ON (md.name = gi.itemmodule)
                                WHERE   gr.userid $selectallstudents
                                    AND gi.courseid = $courseid
                                    AND md.id = $moduleid
                                    AND gi.iteminstance = $instanceid ";

                            $tempstudentsgrade = $DB->get_field_sql($sql, null);
                            if (!is_null($tempstudentsgrade)){ // If the value is valid.
                                //$tempstudentsgrade = (float)$DB->get_field_sql($sql, null);
                                $benchmarkstudents += (float)round($tempstudentsgrade/$tempassignmax*100, 2);
                            }
                        }
                    }
                }
                break;

            // In case of checking student collaboration, perform data mining according to collaboration type:
            // - Entries, for simple log occurences in selected course modules,
            // - File Adds, for summing number of file adds (not number of files!), from course modules of type forum.
            // - Replies, for summing number of replied posts, from course modules of type forum.
            // - Interactions, for total number of students the evaluated student interacted during using the selected course modules.
            case gradingform_erubric_controller::INTERACTION_TYPE_COLLABORATION:
                $moduletypename = 'activity';

                // In case of students interactions, benchmarks will be calculated after all modules have been processed.
                if ($criterion['collaborationtype']==gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS){
                    $distinctUsersFound = array();
                }

                // In case of students file submissions, benchmarks will be initialised with zero values.
                if ($criterion['collaborationtype']==gradingform_erubric_controller::COLLABORATION_TYPE_FILE_ADDS){
                    $benchmarkstudent = 0;
                    if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS) $benchmarkstudents = 0;
                }

                foreach ($criterion['coursemodules'][$moduletypename][0] as $mdlinstance) { // Iterate through course modules.
                    $tempinstance = explode('->', $mdlinstance);
                    $moduleid = $tempinstance[0];
                    $instanceid = $tempinstance[1];

                    switch ($criterion['collaborationtype']) {

                        // In case of checking simple entries in forums and chats,
                        // just check for 'add post' or 'talk' actions inside moodle log.
                        case gradingform_erubric_controller::COLLABORATION_TYPE_ENTRIES:
                            $sql = "SELECT COUNT(lg.id) AS TOTALS
                                    FROM {log} lg
                                        INNER JOIN {course_modules} cm ON (lg.cmid = cm.id)
                                    WHERE   lg.userid $selectindividual
                                        AND (lg.action = 'add post' OR lg.action = 'talk')
                                        AND cm.course = $courseid
                                        AND cm.module = $moduleid
                                        AND cm.instance = $instanceid ";

                            $this->get_value_from_learning_analytics($benchmarkstudent, $sql, 'lg', 'time', $fromtime, $untiltime, 1);

                            // If the criterion has a global reference according to all students collaborations.
                            if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){
                                $count = 0;

                                $sql = "SELECT DISTINCT (lg.userid) AS userids
                                        FROM {log} lg
                                            INNER JOIN {course_modules} cm ON (lg.cmid = cm.id)
                                        WHERE   lg.userid $selectallstudents
                                            AND (lg.action = 'add post' OR lg.action = 'talk')
                                            AND cm.course = $courseid
                                            AND cm.module = $moduleid
                                            AND cm.instance = $instanceid ";

                                $participatingstudents = $this->timestamp_and_count_active_studends_involved($count, $sql, 'lg', 'time', $fromtime, $untiltime);
                                if ($count>0) { // If there is at least one student.
                                    $iterations++;
                                    $sql = "SELECT COUNT(lg.id) AS TOTALS
                                            FROM {log} lg
                                                INNER JOIN {course_modules} cm ON (lg.cmid = cm.id)
                                            WHERE   lg.userid IN ($participatingstudents)
                                                AND (lg.action = 'add post' OR lg.action = 'talk')
                                                AND cm.course = $courseid
                                                AND cm.module = $moduleid
                                                AND cm.instance = $instanceid ";

                                    $this->get_value_from_learning_analytics($benchmarkstudents, $sql, 'lg', 'time', $fromtime, $untiltime, $count);
                                }
                            }
                            break;

                        // In case of checking addition occurences of files in forums,
                        // just count the attachement occurences (should be number of files, but it isn't...) in forum posts.
                        case gradingform_erubric_controller::COLLABORATION_TYPE_FILE_ADDS:
                            $sql = "SELECT SUM(fp.attachment) AS TOTALS
                                    FROM {forum_posts} fp
                                        INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                    WHERE   fp.userid $selectindividual
                                        AND fd.forum = $instanceid ";

                            $this->get_value_from_learning_analytics($benchmarkstudent, $sql, 'fp', 'created', $fromtime, $untiltime, 1);

                            // If the criterion has a global reference according to all students file submissions.
                            if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){
                                $count = 0;

                                $sql = "SELECT DISTINCT (fp.userid) AS userids
                                        FROM {forum_posts} fp
                                            INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                        WHERE   fp.userid $selectallstudents
                                            AND fp.attachment > 0
                                            AND fd.forum = $instanceid ";

                                $participatingstudents = $this->timestamp_and_count_active_studends_involved($count, $sql, 'fp', 'created', $fromtime, $untiltime);
                                if ($count>0) { // If there is at least one student.
                                    $iterations++;
                                    $sql = "SELECT SUM(fp.attachment) AS TOTALS
                                            FROM {forum_posts} fp
                                                INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                            WHERE   fp.userid IN ($participatingstudents)
                                                AND fd.forum = $instanceid ";

                                    $this->get_value_from_learning_analytics($benchmarkstudents, $sql, 'fp', 'created', $fromtime, $untiltime, $count);
                                }
                            }
                            break;

                        // In case of checking student replies in forums,
                        // just count all student posts except self-replies and the ones referring to root post.
                        case gradingform_erubric_controller::COLLABORATION_TYPE_REPLIES:
                            $sql = "SELECT COUNT(fp.id) AS TOTALS
                                    FROM {forum_posts} fp
                                        INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                    WHERE fp.userid $selectindividual
                                        AND fp.parent <> 0
                                        AND fp.parent NOT IN (SELECT fp2.id AS tempids
                                                              FROM {forum_posts} fp2
                                                                  INNER JOIN {forum_discussions} fd2 ON (fd2.id = fp2.discussion)
                                                              WHERE fp2.userid $selectindividual
                                                                  AND fp2.parent <> 0
                                                                  AND fd2.forum = $instanceid)
                                        AND fd.forum = $instanceid ";

                            $this->get_value_from_learning_analytics($benchmarkstudent, $sql, 'fp', 'created', $fromtime, $untiltime, 1);

                            // If the criterion has a global reference according to all students replies.
                            if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){
                                $count = 0;

                                $sql = "SELECT DISTINCT (fp.userid) AS userids
                                        FROM {forum_posts} fp
                                            INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                        WHERE fp.userid $selectallstudents
                                            AND fp.parent <> 0
                                            AND fp.parent NOT IN (SELECT fp2.id AS tempids
                                                                  FROM {forum_posts} fp2
                                                                      INNER JOIN {forum_discussions} fd2 ON (fd2.id = fp2.discussion)
                                                                  WHERE fp2.userid = fp.userid
                                                                      AND fp2.parent <> 0
                                                                      AND fd2.forum = $instanceid)
                                            AND fd.forum = $instanceid ";

                                $participatingstudents = $this->timestamp_and_count_active_studends_involved($count, $sql, 'fp', 'created', $fromtime, $untiltime);
                                if ($count>0) { // If there is at least one student.
                                    $iterations++;
                                    $sql = "SELECT COUNT(fp.id) AS TOTALS
                                            FROM {forum_posts} fp
                                                INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                            WHERE   fp.userid IN ($participatingstudents)
                                                AND fp.parent <> 0
                                                AND fp.parent NOT IN (SELECT fp2.id AS tempids
                                                                  FROM {forum_posts} fp2
                                                                      INNER JOIN {forum_discussions} fd2 ON (fd2.id = fp2.discussion)
                                                                  WHERE fp2.userid = fp.userid
                                                                      AND fp2.parent <> 0
                                                                      AND fd2.forum = $instanceid)
                                                AND fd.forum = $instanceid ";
                                    $this->get_value_from_learning_analytics($benchmarkstudents, $sql, 'fp', 'created', $fromtime, $untiltime, $count);
                                }
                            }
                            break;

                        // In case of checking the number of distinct students, the evaluated student has interacted, in forums or chats...
                        // 1. Retrieve all student ids (including the evaluated) for each course module.
                        // 2. Check if current evaluated student's id exists in each module of interacted student's ids.
                        // 3. Update the pile of current evaluated student's, interacted user's ids.
                        // 4. Count the pile's size to retieve the number of all student's ids,
                        //    thus get the nubmer of all students the current one interacted.
                        // In case of checking all students do the above for every student interacted in each course module...
                        //    (!!!Run time possible delay or script timeout for checking many users!!!)
                        case gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS:

                            if ($moduleid==$this->forummoduleid) { // Check forum modules.

                                // Get each forum discussion and check interactions.
                                $tempsql = "SELECT fd.id AS discussionid FROM {forum_discussions} fd WHERE fd.forum = $instanceid";
                                $discussions = $DB->get_records_sql($tempsql, null);
                                foreach ($discussions as $dscn){
                                    $discussionid = $dscn->discussionid;
                                    $sql = "SELECT DISTINCT(fp.userid) AS usersid
                                            FROM {forum_posts} fp
                                                INNER JOIN {forum_discussions} fd ON (fd.id = fp.discussion)
                                            WHERE fd.forum = $instanceid
                                                AND fd.id = $discussionid
                                                AND fp.userid $selectallstudents "; // This line added to ensure that only students are accounted.

                                    $tempusersarray = null;
                                    $this->get_value_from_learning_analytics($tempusersarray, $sql, 'fp', 'created', $fromtime, $untiltime, null);

                                    if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENT){ // Check only current student evaluated.
                                        if (!is_null($tempusersarray)) {
                                            $studentin = false;

                                            // Check all participating students to see if current student's id was in.
                                            foreach($tempusersarray as $tempid){
                                                if (isset($tempid->usersid) && $tempid->usersid==$studentid) {
                                                    $studentin = true;
                                                    break;
                                                }
                                            }

                                            if ($studentin){ // If student took part in that discussion.
                                                foreach($tempusersarray as $tempid){ // Check all participating students.
                                                    // If temp user id not the same with the student's and is unique, add to pile.
                                                    if ($tempid->usersid!=$studentid &&
                                                        (!isset($distinctUsersFound[$studentid]) ||
                                                            !in_array($tempid->usersid, $distinctUsersFound[$studentid])
                                                        )) $distinctUsersFound[$studentid][] = $tempid->usersid;
                                                }
                                            }
                                        }

                                    // Check all students along with the current student evaluated, if there are any.
                                    }else if(!is_null($tempusersarray)){
                                        // Check all participating students in order to create and update
                                        // each one's unique pile of interactions.
                                        foreach($tempusersarray as $tempcurrentid){ // loop A
                                            // Check all participating students again, to update the pile of the user selected by previous loop.
                                            foreach($tempusersarray as $tempid){ // loop B
                                                // If temp user's id (loop B) is unique and not the same with user's id (loop A),
                                                // add to pile (of user A).
                                                if (isset($tempid->usersid) &&
                                                    isset($tempcurrentid->usersid) &&
                                                    $tempid->usersid!=$tempcurrentid->usersid &&
                                                    (!isset($distinctUsersFound[$tempcurrentid->usersid]) ||
                                                        !in_array($tempid->usersid, $distinctUsersFound[$tempcurrentid->usersid])
                                                    )) $distinctUsersFound[$tempcurrentid->usersid][] = $tempid->usersid;
                                            }
                                        }
                                    }
                                }
                            }else{ // Check chat modules.
                                $tempusersarray = $this->get_interacted_users_according_to_chat_sessions($instanceid, $fromtime, $untiltime);

                                if (empty($tempusersarray)) {
                                    break;
                                }

                                if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENT){ // Check only current student evaluated.

                                    // Go through all chat sessions to distinct interacted students.
                                    foreach($tempusersarray as $tempsession){
                                        if (in_array($studentid, $tempsession)){ // If user exists in current chat session.
                                            foreach($tempsession as $tempuserid){
                                                // If temp user id not the same with the student's and is unique, add to pile.
                                                if ($tempuserid!=$studentid &&
                                                    (!isset($distinctUsersFound[$studentid]) ||
                                                        !in_array($tempuserid, $distinctUsersFound[$studentid])
                                                    )) $distinctUsersFound[$studentid][] = $tempuserid;
                                            }
                                        }
                                    }

                                }else {

                                    // Check all participating students in order to create and update
                                    // each one's unique pile of interactions.
                                    foreach($tempusersarray as $tempsession){ // Iterate through all chat sessions.
                                        foreach($tempsession as $tempuserid1){ // Get users list.
                                            foreach($tempsession as $tempuserid2){ // Get users list again.
                                                // If temp2 user id not the same with temp1's and is unique, add to temp1's pile.
                                                if ($tempuserid1!=$tempuserid2 &&
                                                    (!isset($distinctUsersFound[$tempuserid1]) ||
                                                        !in_array($tempuserid2, $distinctUsersFound[$tempuserid1])
                                                    )) $distinctUsersFound[$tempuserid1][] = $tempuserid2;
                                            }
                                        }
                                    }

                                }
                            }
                            break;
                    }
                }
                break;
        }

        // When user interactions are checked, benchmarks are calculated after all course modules are processed and only if there are interactions.
        if ($criterion['collaborationtype']==gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS && !empty($distinctUsersFound)){
            if (array_key_exists($studentid, $distinctUsersFound)) { // Check current student evaluated.
                $benchmarkstudent = count($distinctUsersFound[$studentid]);
            }else{
                $benchmarkstudent = 0;
            }

            if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){ // Check all students participated.
                $benchmarkstudents = 0;
                $iterations = count($distinctUsersFound);
                foreach($distinctUsersFound as $tempstudentid=>$otherstudents){
                    $benchmarkstudents += count($distinctUsersFound[$tempstudentid]);
                }
            }
        }

        if (!is_null($benchmarkstudent)){
            if ($criterion['collaborationtype']<>gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS &&
                ($criterion['criteriontype']==gradingform_erubric_controller::INTERACTION_TYPE_GRADE ||
                 $criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS)){
                $benchmarkstudent = (int)round($benchmarkstudent/$iterations, 0);
            }

            if ($criterion['referencetype']==gradingform_erubric_controller::REFERENCE_STUDENTS){ // Check students benchmark.
                if ($benchmarkstudents){
                    // To avoid division by zero for small students benchmarks, make two point precision rounding.
                    // For numbers greater than 1 make zero point precision rounding.
                    if ($benchmarkstudents>=1){
                        $benchmarkstudents = (int)round($benchmarkstudents/$iterations, 0);
                        $benchmarkcriterion = (int)round($benchmarkstudent*100/$benchmarkstudents);
                    }else{
                        $benchmarkstudents = (float)round($benchmarkstudents/$iterations, 2);
                        $benchmarkcriterion = (int)round($benchmarkstudent*100/$benchmarkstudents);
                        $benchmarkstudents = (int)round($benchmarkstudents, 0);
                    }
                }else{
                    $benchmarkcriterion = null;
                }
            }else{
                $benchmarkcriterion = (int)$benchmarkstudent; // Just convert student benchmark to integer.
            }
        }

        // Run enrichment procedure to return the appropriate selected level (if exists),
        // and store criterion benchmarks for future reference.
        $criterion['checkedenrich'] = $this->get_enriched_level_from_benchmark($criterion['levels'], $benchmarkcriterion, $criterion['operator'], $options['sortlevelsasc']);
        $criterion['enrichedbenchmark'] = $benchmarkcriterion;
        $criterion['enrichedbenchmarkstudent'] = $benchmarkstudent;
        $criterion['enrichedbenchmarkstudents'] = $benchmarkstudents;
    }

    /**
     * Retrieve the value from Learning Analytics.
     *
     * This function uses specific sql queries to retrieve the corresponding value from log data entries
     * according to the enriched criterion type, which defines the initial sql string, the table and the corresponding column
     * that may be used for applying time stamps.
     * If a valid value results, it gets converted to float and added to the corresponding variable.
     * If not, the corresponding variable remains as is.
     *
     * @param float/array &$var the corresponded variable
     * @param string $sqlstr the initial sql string to begin the query
     * @param string $table the enriched criterion operator
     * @param string $col the assortment of levels according to enriched rubric options
     * @param string $from the begin time for a potential time stamp
     * @param string $until the end time for a potential time stamp
     */
    protected function get_value_from_learning_analytics(&$var, $sqlstr, $table, $col, $from, $until, $totals) {
        GLOBAL $DB;
        // Update SQL query in case of time stamps.
        if ($from) {
            $sqlstr .= "AND ".$table.".".$col." >= $from ";
        }
        if ($until) {
            $sqlstr .= "AND ".$table.".".$col." <= $until ";
        }

        if (strpos($sqlstr, 'DISTINCT')==7) { // If we want a list of values for checking Interactions.
            $temp = $DB->get_records_sql($sqlstr, null);
        }else{
            $temp = $DB->get_field_sql($sqlstr, null);
        }

        if (!is_null($temp) && strlen((String)$temp)>0){ // If the value is valid (even zero).
            if (!is_array($temp)){ // Convert to float only numbers.
                $temp = (float)$temp/$totals;
                $temp = round($temp, 2);
                $var += $temp;
            }else{
                foreach($temp as $key=>$obj){ // This will be a one step loop.
                    if (property_exists($obj, 'usersid')){ // If this is an array requested for 'Interactions' collaboration type.
                        $var = $temp;
                    }else{ // Else get the first element, whitch is the value of 'TOTALS' from query results.
                        $temp = array_keys($temp);
                        $temp = (float)$temp[0]/$totals;
                        $temp = round($temp, 2);
                        $var += $temp;
                    }
                    break;
                }

            }
        }
    }

    /**
     * Timestamp SQL query to retrieve participating students and count students.
     *
     * This function timestamps the specific sql queries to retrieve the corresponding student ids
     * of all students existing actively in a specific course module and updates their number.
     * Only users of 'student' role are accounted and only those existing in a potentially active period of time(defined by timestamps).
     *
     * @param int &$count the number of students found
     * @param string $sqlstr the initial sql string to begin the query
     * @param string $table the enriched criterion operator
     * @param string $col the assortment of levels according to enriched rubric options
     * @param string $from the begin time for a potential time stamp
     * @param string $until the end time for a potential time stamp
     * @return string
     */
    protected function timestamp_and_count_active_studends_involved(&$count, $sqlstr, $table, $col, $from, $until) {
        GLOBAL $DB;
        // Update SQL query in case of time stamps.
        if ($from) {
            $sqlstr .= "AND ".$table.".".$col." >= $from ";
        }
        if ($until) {
            $sqlstr .= "AND ".$table.".".$col." <= $until ";
        }

        // Get the number of active students and update $count.
        $temp = $DB->get_records_sql($sqlstr, null);
        $count = count($temp);

        return $sqlstr;
    }

    /**
     * Get all interacted students for each session from chat messages of a specific chat instance.
     *
     * Checking interacted users in chat modules is a little bit tricky.
     * We have to split chat messages in sessions and check interacted users in each one.
     * For this purpose we will use the corresponding code from mod/chat/report.php file with few modifications.
     *
     * @param int $instanceid the chat instance id
     * @param string $from the begin time for a potential time stamp
     * @param string $until the end time for a potential time stamp
     * @return array
     */
    protected function get_interacted_users_according_to_chat_sessions($instanceid, $from, $until) {
        GLOBAL $DB;
        $sessionuserids = array();
        $params = array('chatid'=>$instanceid, 'start'=>$from, 'end'=>$until);

        /// Get the messages.
        if ($messages = $DB->get_records_select('chat_messages', "chatid = :chatid AND timestamp >= :start AND timestamp <= :end ", $params, "timestamp DESC")) {
            /// Get all the sessions.
            $sessiongap        = 5 * 60;    // 5 minutes silence means a new session.
            $sessionnbr        = 0;
            $sessionend        = 0;
            $sessionstart      = 0;
            $lasttime          = 0;

            $messagesleft = count($messages);

            foreach ($messages as $message) {  // We are walking BACKWARDS through the messages.
                $messagesleft --;              // Countdown.
                if (!$lasttime) {
                    $lasttime = $message->timestamp;
                }
                if (!$sessionend) {
                    $sessionend = $message->timestamp;
                }
                if ((($lasttime - $message->timestamp) < $sessiongap) and $messagesleft) {  // Same session.
                    // If current session has no user ids or current user id not in this session, add it.
                    if ((empty($sessionuserids) || !array_key_exists($sessionnbr, $sessionuserids) || !in_array($message->userid, $sessionuserids[$sessionnbr])) && !$message->system) {
                        $sessionuserids[$sessionnbr] = array();
                        array_push($sessionuserids[$sessionnbr], $message->userid);
                    }
                } else {
                    $sessionstart = $lasttime;
                    $is_complete = ($sessionend - $sessionstart > 60 and array_key_exists($sessionnbr, $sessionuserids));
                    if ($is_complete) {
                        $sessionnbr++;
                    }
                    $sessionend = $message->timestamp;
                    if (!$message->system) {
                        $sessionuserids[$sessionnbr] = array();
                        array_push($sessionuserids[$sessionnbr], $message->userid);
                    }
                }
                $lasttime = $message->timestamp;
            }
        }
        return $sessionuserids;
    }

    /**
     * Selection of appropriate level according to enrichment evaluation.
     *
     * This function uses the benchmarks calculated from enrichment evaluation function and accordingly
     * updates the selected level of each criterion. The checking of levels is conducted according to
     * the assortment of the simple rubric levels. If the enriched values of levels are not assorted accordingly,
     * logical calculation errors may occur.
     *
     * @param array $criterionlevels data about the levels of the current criterion
     * @param int $benchmark the criterion benchmark calculated from enrichment evaluation function
     * @param int $operator the enriched criterion operator
     * @param int $levelasc the assortment of levels according to enriched rubric options
     * @return int
     * @see self::evaluate_enrichment()
     */
    protected function get_enriched_level_from_benchmark(&$criterionlevels, $benchmark, $operator, $levelasc) {
        if (is_null($benchmark)) return null;
        $levelfound = null;
        foreach ($criterionlevels as $levelid => $level) { // Iterate through levels.
            if ($operator == gradingform_erubric_controller::OPERATOR_EQUAL){
                if ((int)$benchmark==(int)$level['enrichedvalue']) {
                    $levelfound = $levelid;
                }
            }else{
                if ((int)$benchmark>=(int)$level['enrichedvalue']) {
                    $levelfound = $levelid;
                }
            }
            // If level values are descending, break on first find.
            if (!$levelasc && $levelfound) {
                break;
            }
        }
        // If any level is found, update cuurent level.
        if ($levelfound){
            $criterionlevels[$levelfound]['checked'] = true;
        }
        return $levelfound;
    }

    /**
     * Help function to return CSS class names for element (first/last/even/odd) with leading space.
     *
     * @param int $idx index of this element in the row/column
     * @param int $maxidx maximum index of the element in the row/column
     * @return string
     */
    protected function get_css_class_suffix($idx, $maxidx) {
        $class = '';
        if ($idx == 0) {
            $class .= ' first';
        }
        if ($idx == $maxidx) {
            $class .= ' last';
        }
        if ($idx%2) {
            $class .= ' odd';
        } else {
            $class .= ' even';
        }
        return $class;
    }

    /**
     * Displays for the student the list of instances or default content if no instances found.
     *
     * @param array $instances array of objects of type gradingform_erubric_instance
     * @param string $defaultcontent default string that would be displayed without advanced grading
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function display_instances($instances, $defaultcontent, $cangrade) {
        $return = '';
        if (sizeof($instances)) {
            $return .= html_writer::start_tag('div', array('class' => 'advancedgrade'));
            $idx = 0;
            foreach ($instances as $instance) {
                $return .= $this->display_instance($instance, $idx++, $cangrade);
            }
            $return .= html_writer::end_tag('div');
        }
        return $return. $defaultcontent;
    }

    /**
     * Displays one grading instance.
     *
     * @param gradingform_erubric_instance $instance
     * @param int idx unique number of instance on page
     * @param boolean $cangrade whether current user has capability to grade in this context
     */
    public function display_instance(gradingform_erubric_instance $instance, $idx, $cangrade) {
        $criteria = $instance->get_controller()->get_definition()->erubric_criteria;
        $options = $instance->get_controller()->get_options();
        $values = $instance->get_erubric_filling();
        if ($cangrade) {
            $mode = gradingform_erubric_controller::DISPLAY_REVIEW;
            $showdescription = $options['showdescriptionteacher'];
        } else {
            $mode = gradingform_erubric_controller::DISPLAY_VIEW;
            $showdescription = $options['showdescriptionstudent'];
        }
        $output = '';
        if ($showdescription) {
            $output .= $this->box($instance->get_controller()->get_formatted_description(), 'gradingform_erubric-description');
        }
        $output .= $this->display_erubric($criteria, $options, $mode, 'erubric'.$idx, $values);
        return $output;
    }

    /**
     * Displays a confirmation message after a regrade has occured.
     *
     * @param string $elementname
     * @param int $changelevel
     * @param int $value The regrade option that was used
     * @return string
     */
    public function display_regrade_confirmation($elementname, $changelevel, $value) {
        $html = html_writer::start_tag('div', array('class' => 'gradingform_rubric-regrade'));
        if ($changelevel<=2) {
            $html .= get_string('regrademessage1', 'gradingform_erubric');
            $selectoptions = array(
                0 => get_string('regradeoption0', 'gradingform_erubric'),
                1 => get_string('regradeoption1', 'gradingform_erubric')
            );
            $html .= html_writer::select($selectoptions, $elementname.'[regrade]', $value, false);
        } else {
            $html .= get_string('regrademessage5', 'gradingform_erubric');
            $html .= html_writer::empty_tag('input', array('name' => $elementname.'[regrade]', 'value' => 1, 'type' => 'hidden'));
        }
        $html .= html_writer::end_tag('div');
        return $html;
    }

    /**
     * Generates and returns HTML code to display information box about how the enriched rubric score is converted to the grade.
     *
     * @param array $scores
     * @return string
     */
    public function display_erubric_mapping_explained($scores) {
        $html = '';
        if (!$scores) {
            return $html;
        }
        $html .= $this->box(
                html_writer::tag('h4', get_string('rubricmapping', 'gradingform_erubric')).
                html_writer::tag('div', get_string('rubricmappingexplained', 'gradingform_erubric', (object)$scores)).
                html_writer::tag('h4', get_string('enrichedrubricinfo', 'gradingform_erubric')).
                html_writer::tag('div', get_string('enrichedrubricinfoexplained', 'gradingform_erubric'))
                , 'generalbox rubricmappingexplained');
        return $html;
    }
}