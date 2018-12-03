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
 * Learning Analytics Enriched Rubric (e-rubric) - Edit Form Definition
 *
 * This file defines the edit form class for the e-rubric, which is later used by edit.php to be published.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once(__DIR__.'/erubriceditor.php');
MoodleQuickForm::registerElementType('erubriceditor', $CFG->dirroot.'/grade/grading/form/erubric/erubriceditor.php', 'MoodleQuickForm_erubriceditor');

/**
 * Defines the enriched rubric edit form
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_erubric_editrubric extends moodleform {

    /**
     * Form element definition
     */
    public function definition() {
        $form = $this->_form;

        $form->addElement('hidden', 'areaid');
        $form->setType('areaid', PARAM_INT);

        $form->addElement('hidden', 'returnurl');
        $form->setType('returnurl', PARAM_LOCALURL);

        // Name.
        $form->addElement('text', 'name', get_string('name', 'gradingform_erubric'), array('size' => 52, 'aria-required' => 'true'));
        $form->addRule('name', get_string('required'), 'required', null, 'client');
        $form->setType('name', PARAM_TEXT);

        // Description.
        $options = gradingform_erubric_controller::description_form_field_options($this->_customdata['context']);
        $form->addElement('editor', 'description_editor', get_string('description', 'gradingform_erubric'), null, $options);
        $form->setType('description_editor', PARAM_RAW);

        // Enriched rubric completion status.
        $choices = array();
        $choices[gradingform_controller::DEFINITION_STATUS_DRAFT] = html_writer::tag('span', get_string('statusdraft', 'core_grading'), array('class' => 'status draft'));
        $choices[gradingform_controller::DEFINITION_STATUS_READY] = html_writer::tag('span', get_string('statusready', 'core_grading'), array('class' => 'status ready'));
        $form->addElement('select', 'status', get_string('rubricstatus', 'gradingform_erubric'), $choices)->freeze();

        // Enriched rubric editor.
        $element = $form->addElement('erubriceditor', 'erubric', get_string('erubric', 'gradingform_erubric'));
        $form->setType('erubric', PARAM_RAW);

        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'saverubric', get_string('saverubric', 'gradingform_erubric'));

        if ($this->_customdata['allowdraft']) {
            $buttonarray[] = &$form->createElement('submit', 'saverubricdraft', get_string('saverubricdraft', 'gradingform_erubric'));
        }

        $editbutton = &$form->createElement('submit', 'editrubric', ' ');
        $editbutton->freeze();
        $buttonarray[] = &$editbutton;
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    /**
     * Setup the form depending on current values. This method is called after definition(),
     * data submission and set_data().
     * All form setup that is dependent on form values should go in here.
     *
     * We remove the element status if there is no current status (i.e. enriched rubric is only being created)
     * so that the users do not get confused.
     */
    public function definition_after_data() {
        $form = $this->_form;
        $el = $form->getElement('status');
        if (!$el->getValue()) {
            $form->removeElement('status');
        } else {
            $vals = array_values($el->getValue());
            if ($vals[0] == gradingform_controller::DEFINITION_STATUS_READY) {
                $this->findbutton('saverubric')->setValue(get_string('save', 'gradingform_erubric'));
            }
        }
    }

    /**
     * Form validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *               or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $err = parent::validation($data, $files);
        $err = array();
        $form = $this->_form;
        $rubricel = $form->getElement('erubric');
        if ($rubricel->non_js_button_pressed($data['erubric'])) {
            // If JS is disabled and button such as 'Add criterion' is pressed - prevent from submit.
            $err['rubricdummy'] = 1;
        } else if (isset($data['editrubric'])) {
            // Continue editing...
            $err['rubricdummy'] = 1;
        } else if (isset($data['saverubric']) && $data['saverubric']) {
            // If user attempts to make rubric active - it needs to be validated.
            if ($rubricel->validate($data['erubric']) !== false) {
                $err['rubricdummy'] = 1;
            }
        }
        return $err;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if (!empty($data->saverubric)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_READY;
        } else if (!empty($data->saverubricdraft)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_DRAFT;
        }
        return $data;
    }

    /**
     * Check if there are changes in the enriched rubric and it is needed to ask user whether to
     * mark the current grades for re-grading. User may confirm re-grading and continue,
     * return to editing or cancel the changes
     *
     * @param gradingform_erubric_controller $controller
     */
    public function need_confirm_regrading($controller) {
        $data = $this->get_data();
        if (isset($data->erubric['regrade'])) {
            // We have already displayed the confirmation on the previous step.
            return false;
        }
        if (!isset($data->saverubric) || !$data->saverubric) {
            // We only need confirmation when button 'Save rubric' is pressed.
            return false;
        }
        if (!$controller->has_active_instances()) {
            // Nothing to re-grade, confirmation not needed.
            return false;
        }
        $changelevel = $controller->update_or_check_erubric($data);

        if ($changelevel == 0) {
            // No changes in the enriched rubric, no confirmation needed.
            return false;
        }

        // Freeze form elements and pass the values in hidden fields.
        // TODO description_editor does not freeze the normal way!
        $form = $this->_form;
        foreach (array('erubric', 'name') as $fieldname) {
            $el =& $form->getElement($fieldname);
            $el->freeze();
            $el->setPersistantFreeze(true);
            if ($fieldname == 'erubric') {
                $el->add_regrade_confirmation($changelevel);
            }
        }

        // Replace button text 'saverubric' and unfreeze 'Back to edit' button.
        $this->findbutton('saverubric')->setValue(get_string('continue'));
        $el =& $this->findbutton('editrubric');
        $el->setValue(get_string('backtoediting', 'gradingform_erubric'));
        $el->unfreeze();

        return true;
    }

    /**
     * Returns a form element (submit button) with the name $elementname
     *
     * @param string $elementname
     * @return HTML_QuickForm_element
     */
    protected function &findbutton($elementname) {
        $form = $this->_form;
        $buttonar =& $form->getElement('buttonar');
        $elements =& $buttonar->getElements();
        foreach ($elements as $el) {
            if ($el->getName() == $elementname) {
                return $el;
            }
        }
        return null;
    }
}