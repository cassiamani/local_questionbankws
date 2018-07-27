<?php
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
 * External Web Service Template
 *
 * @package    local_questionbankws
 * @copyright  2018 Bruno Fabricio Cassiamani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
class local_questionbankws_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_questionbankws_parameters() {
        return new external_function_parameters(
                array('courseid' => new external_value(PARAM_INT, 'courseid'))
        );
    }
    /**
     * Returns Categories, questions and answers
     * @return array Categories, questions and answers
     */
    public static function get_questionbankws($courseid) {
        global $DB;
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_questionbankws_parameters(),
                array('courseid' => $courseid));

        $categories = $DB->get_records_sql('SELECT qc.id, qc.name, qc.parent
                                            FROM {question_categories} qc
                                            INNER JOIN {context} ct ON qc.contextid = ct.id
                                            WHERE ct.instanceid = ?
                                            AND qc.parent != ?', array($params['courseid'], 0));

        foreach($categories as $category) {
            $questions = $DB->get_records_select('question', "category = $category->id AND questiontextformat != 0");

            $value["id"] = $category->id;
            $value["name"] = $category->name;
            $value["questions"] = array();
            foreach($questions as $question) {
                $answers = $DB->get_records_select('question_answers', "question = $question->id");

                $questionsvalue["questionname"] = $question->name;
                $questionsvalue["questiontext"] = strip_tags($question->questiontext);
                $questionsvalue["questionanswer"] = array();
                foreach($answers as $answer) {
                    $questionsvalue["questionanswer"][] = array(
                        "answer" => strip_tags($answer->answer),
                        "fraction" => strip_tags($answer->fraction),
                    );
                }

                $value["questions"][] = $questionsvalue;
            }
            $value["parent"] = $category->parent;
            
            $result[] = $value;
        }

        return $result;
    }
    /** 
     * Returns description of method result value
     * @return external_description
     */
    public static function get_questionbankws_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Category ID'),
                    'name' => new external_value(PARAM_TEXT, 'Category Name'),
                    'questions' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'questionname' => new external_value(PARAM_TEXT, 'Question Name'),
                                'questiontext' => new external_value(PARAM_TEXT, 'Question Text'),
                                'questionanswer' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                            'answer' => new external_value(PARAM_TEXT, 'Question Answer'),
                                            'fraction' => new external_value(PARAM_TEXT, 'Question fraction'),
                                        )
                                    )
                                ),
                            )
                        )
                    ),
                    'parent' => new external_value(PARAM_INT, 'Category Parent'),
                )
            )
        );
    }
}