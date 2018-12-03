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
 * Learning Analytics Enriched Rubric (e-rubric) - Backup
 *
 * Defines learning analytics enriched rubric backup structures.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class contains all necessary definitions needed for a successful backup of all e-rubric data.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_gradingform_erubric_plugin extends backup_gradingform_plugin {

    /**
     * Declares enriched rubric structures to append to the grading form definition.
     */
    protected function define_definition_plugin_structure() {

        // Append data only if the grand-parent element has 'method' set to 'erubric'.
        $plugin = $this->get_plugin_element(null, '../../method', 'erubric');

        // Create a visible container for our data.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect our visible container to the parent.
        $plugin->add_child($pluginwrapper);

        // Define our elements.
        $ecriteria = new backup_nested_element('enrichedcriteria');
        $ecriterion = new backup_nested_element('enrichedcriterion', array('id'), array('sortorder', 'description',
                                                'descriptionformat', 'criteriontype', 'collaborationtype', 'coursemodules',
                                                'operator', 'referencetype'));

        $elevels = new backup_nested_element('enrichedlevels');
        $elevel = new backup_nested_element('enrichedlevel', array('id'), array('score', 'definition', 'definitionformat',
                                            'enrichedvalue'));

        // Build elements hierarchy.
        $pluginwrapper->add_child($ecriteria);
        $ecriteria->add_child($ecriterion);
        $ecriterion->add_child($elevels);
        $elevels->add_child($elevel);

        // Set sources to populate the data.
        $ecriterion->set_source_table('gradingform_erubric_criteria',
                array('definitionid' => backup::VAR_PARENTID));

        $elevel->set_source_table('gradingform_erubric_levels',
                array('criterionid' => backup::VAR_PARENTID));

        // No need to annotate ids or files yet (one day when criterion definition supports
        // embedded files, they must be annotated here).

        return $plugin;
    }

    /**
     * Declares learning analytics enriched rubric structures to append to the grading form instances.
     */
    protected function define_instance_plugin_structure() {

        // Append data only if the ancestor 'definition' element has 'method' set to 'erubric'.
        $plugin = $this->get_plugin_element(null, '../../../../method', 'erubric');

        // Create a visible container for our data.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect our visible container to the parent.
        $plugin->add_child($pluginwrapper);

        // Define our elements.
        $efillings = new backup_nested_element('enrichedfillings');
        $efilling = new backup_nested_element('enrichedfilling', array('id'), array('criterionid', 'levelid', 'remark',
                              'remarkformat', 'enrichedbenchmark', 'enrichedbenchmarkstudent', 'enrichedbenchmarkstudents'));

        // Build elements hierarchy.
        $pluginwrapper->add_child($efillings);
        $efillings->add_child($efilling);

        // Binding criterionid to ensure it's existence.
        $efilling->set_source_sql("SELECT rf.*
                FROM {gradingform_erubric_fillings} rf
                JOIN {grading_instances} gi ON gi.id = rf.instanceid
                JOIN {gradingform_erubric_criteria} rc ON rc.id = rf.criterionid AND gi.definitionid = rc.definitionid
                WHERE rf.instanceid = :instanceid",
                array('instanceid' => backup::VAR_PARENTID));

        // No need to annotate ids or files yet (one day when remark field supports
        // embedded files, they must be annotated here).

        return $plugin;
    }
}