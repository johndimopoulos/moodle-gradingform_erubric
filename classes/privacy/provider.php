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
 * Learning Analytics Enriched Rubric (e-rubric) - Privacy Provider
 *
 * This file contains the grading method interface and privacy class for handling and processing user data.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2018 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradingform_erubric\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;

/**
 * This class contains all necessary functions needed for describing, exporting and deleting user data.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos <johndimopoulos@sch.gr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\subsystem_provider,
    \core_grading\privacy\gradingform_provider_v2 {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialized collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('gradingform_erubric_fillings', [
            'instanceid' => 'privacy:metadata:fillings:instanceid',
            'criterionid' => 'privacy:metadata:fillings:criterionid',
            'levelid' => 'privacy:metadata:fillings:levelid',
            'remark' => 'privacy:metadata:fillings:remark',
            'enrichedbenchmark' => 'privacy:metadata:fillings:enrichedbenchmark',
            'enrichedbenchmarkstudent' => 'privacy:metadata:fillings:enrichedbenchmarkstudent',
            'enrichedbenchmarkstudents' => 'privacy:metadata:fillings:enrichedbenchmarkstudents',
        ], 'privacy:metadata:fillings');

        return $collection;
    }

    /**
     * This method is used to export any user data this sub-plugin has using the object to get the context and userid.
     *
     * @param \context $context Context owner of the data.
     * @param \stdClass $definition Grading definition entry to export.
     * @param  int $userid The user whose information is to be exported.
     *
     * @return \stdClass The data to export.
     */
    public static function get_gradingform_export_data(\context $context, $definition, int $userid = 0) {
        global $DB;

        $select = "SELECT gd.name AS erubricname,
                          gef.id AS gradefillingid,
                          gec.description AS criterion,
                          gel.definition AS level,
                          gef.remark AS levelremark,
                          gef.enrichedbenchmark AS criterionbenchmark,
                          gef.enrichedbenchmarkstudent AS benchmarkstudent,
                          gef.enrichedbenchmarkstudents AS benchmarkstudents,
                          ag.grader AS graderid,
                          ag.userid AS studentid";
        $from = " FROM {grading_instances} gi
                  LEFT JOIN {gradingform_erubric_fillings} gef
                    ON gi.id = gef.instanceid
                  RIGHT JOIN {gradingform_erubric_criteria} gec
                    ON gec.id = gef.criterionid
                  RIGHT JOIN {gradingform_erubric_levels} gel
                    ON gel.id = gef.levelid
                  RIGHT JOIN {grading_definitions} gd
                    ON gd.id = gi.definitionid
                  RIGHT JOIN {assign_grades} ag
                         ON gi.itemid = ag.id";
        $where = " WHERE gi.definitionid = :definitionid";
        $params['definitionid'] = $definition->id;

        if ($userid) {

            $where .= " AND (ag.grader = :graderid
                         OR gd.usercreated = :usercreated
                         OR gd.usermodified = :usermodified
                         OR ag.userid = :studentid)";
            $params = [
                'graderid' => $userid,
                'usercreated' => $userid,
                'usermodified' => $userid,
                'studentid' => $userid
            ];
        }

        $sql = $select.$from.$where;
        $gradingfillings = $DB->get_recordset_sql($sql, $params);

        foreach ($gradingfillings as $gradingfilling) {
            $fillingsdata = [
                'erubricname' => $gradingfilling->erubricname,
                'fillingid_'.$gradingfilling->gradefillingid =>
                    ['criterion' => $gradingfilling->criterion,
                    'level' => $gradingfilling->level,
                    'levelremark' => $gradingfilling->levelremark,
                    'finalbenchmark' => $gradingfilling->criterionbenchmark,
                    'studentbenchmark' => $gradingfilling->benchmarkstudent,
                    'studentsbenchmark' => $gradingfilling->benchmarkstudents,
                    'grader' => transform::user($gradingfilling->graderid),
                    'student' => transform::user($gradingfilling->studentid)]
            ];
        }

        return $fillingsdata;
    }

    /**
     * Export user data relating to an instance ID.
     *
     * @param  \context $context Context to use with the export writer.
     * @param  int $instanceid The instance ID to export data for.
     * @param  array $subcontext The directory to export this data to.
     */
    public static function export_gradingform_instance_data(\context $context, int $instanceid, array $subcontext) {
        global $DB;
        // Get records from the provided params.
        $params = ['instanceid' => $instanceid];
        $sql = "SELECT rc.description, rl.definition, rl.score, rf.remark,
                       rf.enrichedbenchmark,
                       rf.enrichedbenchmarkstudent,
                       rf.enrichedbenchmarkstudents,
                  FROM {gradingform_erubric_fillings} rf
                  JOIN {gradingform_erubric_criteria} rc ON rc.id = rf.criterionid
                  JOIN {gradingform_erubric_levels} rl ON rf.levelid = rl.id
                 WHERE rf.instanceid = :instanceid";
        $records = $DB->get_records_sql($sql, $params);
        if ($records) {
            $subcontext = array_merge($subcontext, [get_string('erubric', 'gradingform_erubric'), $instanceid]);
            \core_privacy\local\request\writer::with_context($context)->export_data($subcontext, (object) $records);
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined.
     *
     * @param \context $context Context owner of the data.
     */
    public static function delete_gradingform_for_context(\context $context) {
        global $DB;

        $select = "SELECT gi.id AS instanceid";
        $from = " FROM {grading_instances} gi
                      RIGHT JOIN {grading_definitions} gd
                        ON gd.id = gi.definitionid
                      RIGHT JOIN {grading_areas} ga
                        ON ga.id = gd.areaid
                      RIGHT JOIN {context} c
                        ON c.id = ga.contextid AND c.contextlevel = :contextlevel";
        $where = " WHERE ga.contextid = :contextid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'contextid'    => $context->id
        ];

        $sql = $select.$from.$where;
        $gradinginstances = $DB->get_recordset_sql($sql, $params);

        foreach ($gradinginstances as $gradinginstance) {
            $db->delete_records('gradingform_erubric_fillings', ['instanceid' => $gradinginstance->instanceid]);
        }
    }

    /**
     * A call to this method should delete user data (where practicle) from the userid and context.
     *
     * @param int $userid The user to delete.
     * @param \context $context the context to refine the deletion.
     */
    public static function delete_gradingform_for_userid(int $userid, \context $context) {
        global $DB;

        $select = "SELECT gi.id AS instanceid";
        $from = " FROM {grading_instances} gi
                      RIGHT JOIN {grading_definitions} gd
                        ON gd.id = gi.definitionid
                      RIGHT JOIN {grading_areas} ga
                        ON ga.id = gd.areaid
                      RIGHT JOIN {context} c
                        ON c.id = ga.contextid AND c.contextlevel = :contextlevel
                      RIGHT JOIN {assign_grades} ag
                        ON gi.itemid = ag.id";
        $where = " WHERE ga.contextid = :contextid
                        AND (ag.grader = :graderid
                         OR gd.usercreated = :usercreated
                         OR gd.usermodified = :usermodified
                         OR ag.userid = :studentid)";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'contextid'    => $context->id,
            'graderid' => $userid,
            'usercreated' => $userid,
            'usermodified' => $userid,
            'studentid' => $userid
        ];

        $sql = $select.$from.$where;
        $gradinginstances = $DB->get_recordset_sql($sql, $params);

        foreach ($gradinginstances as $gradinginstance) {
            $db->delete_records('gradingform_erubric_fillings', ['instanceid' => $gradinginstance->instanceid]);
        }
    }

    /**
     * Deletes all user data related to the provided instance IDs.
     *
     * @param  array  $instanceids The instance IDs to delete information from.
     */
    public static function delete_gradingform_for_instances(array $instanceids) {
        global $DB;
        $DB->delete_records_list('gradingform_erubric_fillings', 'instanceid', $instanceids);
    }
}