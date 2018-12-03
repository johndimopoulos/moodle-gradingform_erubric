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
 * Learning Analytics Enriched Rubric (e-rubric) - Edit Form Data Processing
 *
 * This file contains the HTML_QuickForm_input class, which is used for all data processing, during form submission.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("HTML/QuickForm/input.php");

/**
 * Form element for handling rubric editor
 *
 * The rubric editor is defined as a separate form element. This allows us to render
 * criteria, levels and buttons using the rubric's own renderer. Also, the required
 * Javascript library is included, which processes, on the client, buttons needed
 * for reordering, adding and deleting criteria.
 *
 * If Javascript is disabled when one of those special buttons is pressed, the form
 * element is not validated and, instead of submitting the form, we process button presses.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_erubriceditor extends HTML_QuickForm_input {
    /** @var string|bool stores the result of the last validation: null - undefined, false - no errors, string - error(s) text. */
    protected $validationerrors = null;
    /** @var bool if the element has already been validated. **/
    protected $wasvalidated = false;
    /** @var bool If non-submit (JS) button was pressed: null - unknown, true/false - button was/wasn't pressed. */
    protected $nonjsbuttonpressed = false;
    /** @var bool Message to display in front of the editor (that student evaluation has occured on this enriched rubric being edited). */
    protected $regradeconfirmation = false;

    /**
     * Specifies that confirmation about re-grading needs to be added to this enriched rubric editor.
     * $changelevel is saved in $this->regradeconfirmation and retrieved in toHtml().
     *
     * @see gradingform_erubric_controller::update_or_check_erubric()
     * @param int $changelevel
     */
    public function add_regrade_confirmation($changelevel) {
        $this->regradeconfirmation = $changelevel;
    }

    /**
     * Returns html string to display this element.
     *
     * @return string
     */
    public function tohtml() {
        global $PAGE;
        $html = $this->_getTabs();
        $renderer = $PAGE->get_renderer('gradingform_erubric');
        $data = $this->prepare_data(null, $this->wasvalidated);
        if (!$this->_flagFrozen) {
            $mode = gradingform_erubric_controller::DISPLAY_EDIT_FULL;
            // Module constants to be used in the javascript include file.
            $module = array('name' => 'gradingform_erubriceditor',
                            'fullpath' => '/grade/grading/form/erubric/js/erubriceditor.js',
                            'requires' => array('base', 'dom', 'event', 'event-touch', 'escape'),
                            'strings' => array(array('confirmdeletecriterion', 'gradingform_erubric'), array('confirmdeletelevel', 'gradingform_erubric'),
                                               array('criterionempty', 'gradingform_erubric'), array('levelempty', 'gradingform_erubric'),
                                               array('intercactionempty', 'gradingform_erubric'), array('collaborationempty', 'gradingform_erubric'),
                                               array('collaborationochoice', 'gradingform_erubric'), array('coursemoduleempty', 'gradingform_erubric'),
                                               array('operatorempty', 'gradingform_erubric'), array('referencetypeempty', 'gradingform_erubric'),
                                               array('enrichedvalueempty', 'gradingform_erubric'), array('confirmdeleteactivity', 'gradingform_erubric'),
                                               array('confirmdeleteresource', 'gradingform_erubric'), array('confirmdeleteassignment', 'gradingform_erubric'),
                                               array('deleteactivity', 'gradingform_erubric'), array('deleteresource', 'gradingform_erubric'),
                                               array('deleteassignment', 'gradingform_erubric'), array('confirmchangecriteriontype', 'gradingform_erubric'),
                                               array('enrichedvaluesuffixtimes', 'gradingform_erubric'), array('enrichedvaluesuffixpercent', 'gradingform_erubric'),
                                               array('enrichedvaluesuffixpoints', 'gradingform_erubric'), array('enrichedvaluesuffixnothing', 'gradingform_erubric'),
                                               array('enrichedvaluesuffixstudents', 'gradingform_erubric'), array('enrichedvaluesuffixfiles', 'gradingform_erubric'))
                            );
            // Define and engage the js class from the js file.
            $PAGE->requires->js_init_call('M.gradingform_erubriceditor.init', array(
                array('name' => $this->getName(),
                    'criteriontemplate' => $renderer->criterion_template($mode, $data['options'], $this->getName()),
                    'enrichedcriteriontemplate' => $renderer->enriched_criterion_template($mode, $data['options'], $this->getName()),
                    'leveltemplate' => $renderer->level_template($mode, $data['options'], $this->getName()),
                    'enrichedleveltemplate' => $renderer->enriched_level_template($mode, $data['options'], $this->getName()),
                    'interactiontypecollaboration' => gradingform_erubric_controller::INTERACTION_TYPE_COLLABORATION,
                    'interactiontypegrade' => gradingform_erubric_controller::INTERACTION_TYPE_GRADE,
                    'interactiontypestudy' => gradingform_erubric_controller::INTERACTION_TYPE_STUDY,
                    'referencestudent' => gradingform_erubric_controller::REFERENCE_STUDENT,
                    'referencestudents' => gradingform_erubric_controller::REFERENCE_STUDENTS,
                    'collaborationpeople' => gradingform_erubric_controller::COLLABORATION_TYPE_INTERACTIONS,
                    'collaborationfiles' => gradingform_erubric_controller::COLLABORATION_TYPE_FILE_ADDS,
                    'moduleicon' => $renderer->moduleicon
                   )),
                true, $module);
        } else {
            // Rubric is frozen, no javascript needed.
            if ($this->_persistantFreeze) {
                $mode = gradingform_erubric_controller::DISPLAY_EDIT_FROZEN;
            } else {
                $mode = gradingform_erubric_controller::DISPLAY_PREVIEW;
            }
        }
        if ($this->regradeconfirmation) { // Display regrade confirmation choices.
            if (!isset($data['regrade'])) {
                $data['regrade'] = 1;
            }
            $html .= $renderer->display_regrade_confirmation($this->getName(), $this->regradeconfirmation, $data['regrade']);
        }
        if ($this->validationerrors) { // Display validation errors.
            $html .= html_writer::div($renderer->notification($this->validationerrors));
        }
        $html .= $renderer->display_erubric($data['criteria'], $data['options'], $mode, $this->getName());
        return $html;
    }

    /**
     * Prepares the data passed in $_POST:
     * - processes the pressed buttons 'addlevel', 'addcriterion', 'moveup', 'movedown', 'delete' (when JavaScript is disabled)
     *   sets $this->nonjsbuttonpressed to true/false if such button was pressed
     * - if options not passed (i.e. we create a new rubric) fills the options array with the default values
     * - if options are passed completes the options array with unchecked checkboxes
     * - if $withvalidation is set, adds 'error_xxx' attributes to elements that contain errors and creates an error string
     *   and stores it in $this->validationerrors
     *
     * @param array $value
     * @param boolean $withvalidation whether to enable data validation
     * @return array
     */
    protected function prepare_data($value = null, $withvalidation = false) {
        if (null === $value) {
            $value = $this->getValue();
        }
        if ($this->nonjsbuttonpressed === null) {
            $this->nonjsbuttonpressed = false;
        }
        $totalscore = 0;
        $errors = array();
        $return = array('criteria' => array(), 'options' => gradingform_erubric_controller::get_default_options());
        if (!isset($value['criteria'])) {
            $value['criteria'] = array();
            $errors['err_nocriteria'] = 1;
        }
        // If options are present in $value, replace default values with submitted values.
        if (!empty($value['options'])) {
            foreach (array_keys($return['options']) as $option) {
                // Special treatment for checkboxes.
                if (!empty($value['options'][$option])) {
                    $return['options'][$option] = $value['options'][$option];
                } else {
                    $return['options'][$option] = null;
                }
            }
        }
        if (is_array($value)) {
            // For other array keys of $value no special treatment needed, copy them to return value as is.
            foreach (array_keys($value) as $key) {
                if ($key != 'options' && $key != 'criteria') {
                    $return[$key] = $value[$key];
                }
            }
        }

        // Iterate through criteria.
        $lastaction = null;
        $lastid = null;
        $overallminscore = $overallmaxscore = 0;
        foreach ($value['criteria'] as $id => $criterion) {

            // Check if user pressed the 'add criterion' button and take appropriate actions...
            if ($id == 'addcriterion') {
                $id = $this->get_next_id(array_keys($value['criteria']));

                // For enrichment, we only need the reference type field declared for the new criterion.
                $criterion = array('description' => '', 'referencetype' => '', 'levels' => array());
                $i = 0;
                // When adding new criterion copy the number of levels and their scores from the last criterion.
                if (!empty($value['criteria'][$lastid]['levels'])) {
                    foreach ($value['criteria'][$lastid]['levels'] as $lastlevel) {
                        $criterion['levels']['NEWID'.($i++)]['score'] = $lastlevel['score'];
                    }
                } else {
                    $criterion['levels']['NEWID'.($i++)]['score'] = 0;
                }

                // Add more levels so there are at least 2 in the new criterion. Increment by 1 the score for each next one.
                for ($i = $i; $i < 2; $i++) {
                    $criterion['levels']['NEWID'.$i]['score'] = $criterion['levels']['NEWID'.($i - 1)]['score'] + 1;
                }
                // Set other necessary fields (definition) for the levels in the new criterion.
                foreach (array_keys($criterion['levels']) as $i) {
                    $criterion['levels'][$i]['definition'] = '';
                }
                $this->nonjsbuttonpressed = true;
            }

            // Enrichement criteria needed for evaluation.
            $allrichcriteria = array('criteriontype', 'coursemodules', 'activity', 'resource', 'assignment', 'operator', 'referencetype');
            $levels = array();
            $minscore = $maxscore = null;
            if (array_key_exists('levels', $criterion)) {

                 // Enrichment checks are conducted if this ($id & $criterion) is a rubric criterion (so it must have levels).
                 // These are the rules:
                 // 1. If one enrichment criterion is selected, all must be.
                 // 2. All course modules should be checked according to criterion type,
                 // ...ie. we can't accept resource modules for assignment(grade) check. We can only accept assignment modules!
                 // ...This should work all by itself when javascript is enabled! :)
                 // 3.a If collaboration is checked as the criterion type, collaboration type must be also checked.
                 // 3.b if file submissions or forum replies are checked as collaboration type, chat course modules can't be chosen.

                // ...****CHECK FOR RULE NO.1****...
                // Preliminary test needed for further evaluation...
                $richcriteriaselected = false;

                foreach ($allrichcriteria as $richcriterion) {
                    if (array_key_exists($richcriterion, $criterion) && $criterion[$richcriterion]) {
                        $richcriteriaselected = true;
                        break;
                    }
                }

                // Get defined enriched criterion types from lib.php!
                $enrichedcriteriontypes = array('activity' => gradingform_erubric_controller::INTERACTION_TYPE_COLLABORATION,
                                                'assignment' => gradingform_erubric_controller::INTERACTION_TYPE_GRADE,
                                                'resource' => gradingform_erubric_controller::INTERACTION_TYPE_STUDY);

                // Further enrichment evaluation for RULE NO.1!
                if ($richcriteriaselected) {

                    // Get enriched criterion type.
                    // If criterion type selected, Rule No.2 is processed...
                    // ...****CHECK FOR RULE NO.2****...
                    if (array_key_exists('criteriontype', $criterion) && $criterion['criteriontype']) {
                        $criteriontype = $criterion['criteriontype'];
                        $selectedmodulestype = null;

                        // Check if there are any selected modules and get their type.
                        if (array_key_exists('coursemodules', $criterion) && is_array($criterion['coursemodules']) && $criterion['coursemodules']) {
                            foreach ($criterion['coursemodules'] as $moduletype => $values) {
                                $selectedmodulestype = $moduletype;
                                break;
                            }
                        }

                        // If the selected course modules are not of the same type, establish the error.
                        if ($selectedmodulestype && $enrichedcriteriontypes[$selectedmodulestype] != $criteriontype) {
                            $errors['err_enrichedmoduleselection'][$id] = 1;
                            $criterion['error_coursemodules'] = true;
                        }

                        // If there are no course modules selected...
                        if ($selectedmodulestype == null) {
                            foreach ($enrichedcriteriontypes as $type => $valueid) {
                                if ($valueid == $criteriontype) {
                                    $selectedmodulestype = $type;
                                    break;
                                }
                            }
                        }

                        // Check if a simple module select field of the same type is selected and then...
                        // ...update coursemodules array and remove possible error notice.
                        if (array_key_exists($selectedmodulestype, $criterion) && $criterion[$selectedmodulestype]) {

                            // If previous modules where not of the same kind, reset them and establish new coursemodules array.
                            if (array_key_exists('err_enrichedmoduleselection', $errors) && $errors['err_enrichedmoduleselection'][$id]) {
                                $criterion['coursemodules'] = array($selectedmodulestype => array($criterion[$selectedmodulestype]));
                                unset($errors['err_enrichedmoduleselection'][$id]);
                                unset($criterion['error_coursemodules']);

                                // If the course modules array has appropriate values, or is empty...
                            } else {
                                // If course modules array exists...
                                if (array_key_exists('coursemodules', $criterion) && is_array($criterion['coursemodules']) && $criterion['coursemodules']) {
                                    array_push($criterion['coursemodules'][$selectedmodulestype], $criterion[$selectedmodulestype]);
                                    // If course modules array is null...
                                } else {
                                    $criterion['coursemodules'] = array($selectedmodulestype => array($criterion[$selectedmodulestype]));
                                }
                            }
                        }

                        // Reset simple selects.
                        foreach ($enrichedcriteriontypes as $type => $valueid) {
                            $tempid = array_search($type, $allrichcriteria);
                            unset($allrichcriteria[$tempid]); // Don't need them any more.
                        }

                        // If collaboration criterion type is selected, Rule No.3 is processed...
                        // Check 3.a first...
                        // ...****CHECK FOR RULE NO.3.a****...
                        if ($criterion['criteriontype'] == gradingform_erubric_controller::INTERACTION_TYPE_COLLABORATION) {

                            // If collaboration type is not selected, establish the error.
                            if (!array_key_exists('collaborationtype', $criterion) || !$criterion['collaborationtype']) {
                                $errors['err_enrichedcriterionmissing'][$id] = 1;
                                $criterion['error_collaborationtype'] = true;

                                // Check 3.b as long as 3.a is ok, and there are course modules selected.
                                // ...****CHECK FOR RULE NO.3.b****...
                            } else if (array_key_exists('coursemodules', $criterion) && !array_key_exists('error_coursemodules', $criterion) &&
                                      ($criterion['collaborationtype'] == gradingform_erubric_controller::COLLABORATION_TYPE_FILE_ADDS ||
                                       $criterion['collaborationtype'] == gradingform_erubric_controller::COLLABORATION_TYPE_REPLIES)) {

                                $temparray = array();
                                if (is_array($criterion['coursemodules'][$selectedmodulestype][0])) { // Already saved criterion.
                                    $temparray = $criterion['coursemodules'][$selectedmodulestype][0];
                                } else { // Currently submited data.
                                    $temparray = $criterion['coursemodules'][$selectedmodulestype];
                                }

                                GLOBAL $PAGE;
                                $renderer = $PAGE->get_renderer('gradingform_erubric');
                                foreach ($temparray as $mdlinstance) {
                                    $tempinstance = explode('->', $mdlinstance);
                                    $moduletype = (int)$tempinstance[0];
                                    // If there are any chat course modules selected establish the error.
                                    if ($moduletype == $renderer->chatmoduleid) {
                                        $errors['err_collaborationhoice'][$id] = 1;
                                        $criterion['error_collaborationmodules'] = true;
                                    }
                                }
                            }
                        } else {
                            // If collaborationtype is selected (when not needed), establish the error.
                            if (array_key_exists('collaborationtype', $criterion) && $criterion['collaborationtype']) {
                                $errors['err_collaborationtypeneedless'][$id] = 1;
                                $criterion['error_collaborationtype'] = true;
                            }
                        }

                        // Even if the criterion type is not selected, handle the course modules.
                    } else {

                        // If course modules are selected, don't check simple course modules selects.
                        if (array_key_exists('coursemodules', $criterion)) {
                            foreach ($enrichedcriteriontypes as $type => $valueid) {
                                $tempid = array_search($type, $allrichcriteria);
                                unset($allrichcriteria[$tempid]); // Don't need them any more!
                            }

                            // Else if at least one simple course module select is selected, trick the procedure bellow...
                            // ...so that coursemodules array is ok...
                        } else {
                            foreach ($enrichedcriteriontypes as $type => $valueid) {
                                $tempid = array_search($type, $allrichcriteria);
                                unset($allrichcriteria[$tempid]); // Don't need it any more!
                                if (array_key_exists($type, $criterion) && array_key_exists('coursemodules', $allrichcriteria)) {
                                    $tempid = array_search('coursemodules', $allrichcriteria);
                                    unset($allrichcriteria[$tempid]); // Don't need it any more!
                                }
                            }
                        }
                    }

                    // First check main enriched criteria.
                    foreach ($allrichcriteria as $richcriterion) {
                        if (empty($criterion[$richcriterion])) {
                            $errors['err_enrichedcriterionmissing'][$id] = 1;
                            $criterion['error_'.$richcriterion] = true;
                        }
                    }
                }

                // If javascript is disabled thus nonjsbuttonpressed and user pressed a deletemodule button...
                // ...remove the appropriate course module from the appropriate criterion.
                // The deletemodules value is string that can give us these data when exploded...
                // 0=>[rubricname] , 1=>['criteria'] , 2=>[criterionid] , 3=>[moduletype] , 4=>[moduleid] , 5=>[moduleinstanceid]!
                if (array_key_exists('deletemodule', $criterion)) {

                    $criterionwithmodule = explode('-', $criterion['deletemodule']);

                    // Remove the module.
                    foreach ($criterion['coursemodules'][$criterionwithmodule[3]] as $key => $valueid) {
                        if ($valueid == $criterionwithmodule[4].'->'.$criterionwithmodule[5]) {
                            unset($criterion['coursemodules'][$criterionwithmodule[3]][$key]);
                        }
                    }

                    // If the last module is deleted, delete the entire coursemodules array.
                    if (count($criterion['coursemodules'][$criterionwithmodule[3]]) == 0) {
                        unset($criterion['coursemodules']);
                    }

                    unset($criterion['deletemodule']); // Don't need it any more...
                    $this->nonjsbuttonpressed = true;
                }
                $uniquelevelscores = array();
                $uniquelevelvalues = array();
                // Iterate through levels...
                foreach ($criterion['levels'] as $levelid => $level) {

                    // Check if user pressed the 'add level' button and take appropriate actions...
                    if ($levelid == 'addlevel') {
                        $levelid = $this->get_next_id(array_keys($criterion['levels']));
                        $level = array(
                            'definition' => '',
                            'score' => 0,
                        );

                        foreach ($criterion['levels'] as $lastlevel) {
                            if (isset($lastlevel['score'])) {
                                $level['score'] = max($level['score'], ceil(unformat_float($lastlevel['score'])) + 1);
                            }
                        }
                        $this->nonjsbuttonpressed = true;
                    }

                    if (!array_key_exists('delete', $level)) {
                        $score = unformat_float($level['score'], true);
                        if ($withvalidation) {
                            if (!strlen(trim($level['definition']))) {
                                $errors['err_nodefinition'] = 1;
                                $level['error_definition'] = true;
                            }
                            if ($score === null || $score === false) {
                                $errors['err_scoreformat'] = 1;
                                $level['error_score'] = true;
                            }
                            if (!empty($uniquelevelscores) && in_array($score, $uniquelevelscores)) {
                                $errors['err_novariationspoints'] = 1;
                                $level['error_score'] = true;
                            } else {
                                $uniquelevelscores[] = $score;
                            }

                            // If this criterion is enriched, check the enriched level value.
                            if ($richcriteriaselected) {
                                $evalue = trim($level['enrichedvalue']);
                                // If enriched value missing, establish the error.
                                if (!strlen($evalue)) {
                                    $errors['err_enrichedvaluemissing'] = 1;
                                    $level['error_enrichedvalue'] = true;

                                    // If enriched value is anything but a positive integer number, establish the error.
                                } else if (!preg_match('#^[\+]?\d*$#', $evalue)) {
                                    $errors['err_enrichedvalueformat'] = 1;
                                    $level['error_enrichedvalue'] = true;
                                }
                                // If enriched value is the same as another in the same criterion, establish the error.
                                if (!empty($uniquelevelvalues) && in_array($evalue, $uniquelevelvalues)) {
                                    $errors['err_novariationsvalues'] = 1;
                                    $level['error_enrichedvalue'] = true;
                                } else {
                                    $uniquelevelvalues[] = $evalue;
                                }
                            }
                        }
                        $levels[$levelid] = $level;
                        if ($minscore === null || $score < $minscore) {
                            $minscore = $score;
                        }
                        if ($maxscore === null || $score > $maxscore) {
                            $maxscore = $score;
                        }
                    } else {
                        $this->nonjsbuttonpressed = true;
                    }
                }
            }
            $totalscore += (float)$maxscore;
            $criterion['levels'] = $levels;

            // Check if user pressed the 'delete criterion' button and take appropriate actions...
            if ($withvalidation && !array_key_exists('delete', $criterion)) {
                if (count($levels) < 2) {
                    $errors['err_mintwolevels'] = 1;
                    $criterion['error_levels'] = true;
                }
                if (!strlen(trim($criterion['description']))) {
                    $errors['err_nodescription'] = 1;
                    $criterion['error_description'] = true;
                }
                $overallmaxscore += $maxscore;
                $overallminscore += $minscore;
            }

            // Check if user pressed the 'move up criterion' button and take appropriate actions...
            if (array_key_exists('moveup', $criterion) || $lastaction == 'movedown') {
                unset($criterion['moveup']);
                if ($lastid !== null) {
                    $lastcriterion = $return['criteria'][$lastid];
                    unset($return['criteria'][$lastid]);
                    $return['criteria'][$id] = $criterion;
                    $return['criteria'][$lastid] = $lastcriterion;
                } else {
                    $return['criteria'][$id] = $criterion;
                }
                $lastaction = null;
                $lastid = $id;
                $this->nonjsbuttonpressed = true;
            } else if (array_key_exists('delete', $criterion)) {
                $this->nonjsbuttonpressed = true;
            } else {

                // Check if user pressed the 'move down criterion' button and take appropriate actions...
                if (array_key_exists('movedown', $criterion)) {
                    unset($criterion['movedown']);
                    $lastaction = 'movedown';
                    $this->nonjsbuttonpressed = true;
                }
                $return['criteria'][$id] = $criterion;
                $lastid = $id;
            }
        }

        if ($totalscore <= 0) {
            $errors['err_totalscore'] = 1;
        }

        // Add sort order field to criteria.
        $csortorder = 1;
        foreach (array_keys($return['criteria']) as $id) {
            $return['criteria'][$id]['sortorder'] = $csortorder++;
        }

        // Create validation error string (if needed).
        if ($withvalidation) {
            if (!$return['options']['lockzeropoints']) {
                if ($overallminscore == $overallmaxscore) {
                    $errors['err_novariations'] = 1;
                }
            }
            if (count($errors)) {
                $rv = array();
                $prefix = '';
                if (count($errors) > 1) {
                    $prefix = '&bull;&nbsp;';
                }
                foreach ($errors as $error => $v) {
                    $rv[] = $prefix.get_string($error, 'gradingform_erubric');
                }
                $this->validationerrors = join('<br />', $rv);
            } else {
                $this->validationerrors = false;
            }
            $this->wasvalidated = true;
        }
        return $return;
    }

    /**
     * Scans array $ids to find the biggest element ! NEWID*, increments it by 1 and returns
     *
     * @param array $ids
     * @return string
     */
    protected function get_next_id($ids) {
        $maxid = 0;
        foreach ($ids as $id) {
            if (preg_match('/^NEWID(\d+)$/', $id, $matches) && ((int)$matches[1]) > $maxid) {
                $maxid = (int)$matches[1];
            }
        }
        return 'NEWID'.($maxid + 1);
    }

    /**
     * Checks if a submit button was pressed which is supposed to be processed on client side by JS
     * but user seem to have disabled JS in the browser.
     * (buttons 'add criteria', 'add level', 'move up', 'move down', etc.)
     * In this case the form containing this element is prevented from being submitted
     *
     * @param array $value
     * @return boolean true if non-submit button was pressed and not processed by JS
     */
    public function non_js_button_pressed($value) {
        if ($this->nonjsbuttonpressed === null) {
            $this->prepare_data($value);
        }
        return $this->nonjsbuttonpressed;
    }

    /**
     * Validates that the rubric has at least one criterion, at least two levels within one criterion,
     * each level has a valid score, all levels have filled definitions and all criteria
     * have filled descriptions
     *
     * @param array $value
     * @return string|false error text or false if no errors found
     */
    public function validate($value) {
        if (!$this->wasvalidated) {
            $this->prepare_data($value, true);
        }
        return $this->validationerrors;
    }

    /**
     * Prepares the data for saving
     * @see prepare_data()
     * @param array $submitValues
     * @param boolean $assoc
     * @return array
     */
    public function exportvalue(&$submitvalues, $assoc = false) {
        $value = $this->prepare_data($this->_findValue($submitvalues));
        return $this->_prepareValue($value, $assoc);
    }
}