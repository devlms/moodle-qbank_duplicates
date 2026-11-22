<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace qbank_duplicates\local;

/**
 * Finds questions in a category whose text is the same after normalising.
 *
 * Only the latest version of each question counts, and hidden questions are left out.
 *
 * @package    qbank_duplicates
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finder {
    /**
     * Text without HTML, entities, case, punctuation or extra spaces.
     *
     * @param string $html
     * @return string
     */
    public static function normalise(string $html): string {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = \core_text::strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Groups of two or more questions of the same type with the same normalised text.
     *
     * @param int $categoryid
     * @return array[] each group a list of question records (id, name, qtype, questiontext, timemodified)
     */
    public static function in_category(int $categoryid): array {
        global $DB;
        $sql = "SELECT q.id, q.name, q.qtype, q.questiontext, q.timemodified
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid = :cat
                       AND qv.status <> :hidden
                       AND qv.version = (SELECT MAX(v.version)
                                           FROM {question_versions} v
                                          WHERE v.questionbankentryid = qbe.id)
              ORDER BY q.id";
        $questions = $DB->get_records_sql($sql, ['cat' => $categoryid,
            'hidden' => \core_question\local\bank\question_version_status::QUESTION_STATUS_HIDDEN]);
        $bykey = [];
        foreach ($questions as $q) {
            $text = self::normalise((string)$q->questiontext);
            if ($text === '') {
                continue;
            }
            $bykey[$q->qtype . '|' . $text][] = $q;
        }
        return array_values(array_filter($bykey, fn($group) => count($group) > 1));
    }
}
