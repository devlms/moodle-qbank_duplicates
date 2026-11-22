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

/**
 * Duplicate questions in the current question bank category.
 *
 * @package    qbank_duplicates
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

use qbank_duplicates\local\finder;

require_login();
core_question\local\bank\helper::require_plugin_enabled('qbank_duplicates');
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] =
    question_edit_setup('questions', '/question/bank/duplicates/duplicates.php');

[$catid] = explode(',', $pagevars['cat']);
$category = $DB->get_record('question_categories', ['id' => $catid], '*', MUST_EXIST);
$categorycontext = context::instance_by_id($category->contextid);
require_capability('moodle/question:viewall', $categorycontext);

$PAGE->set_url($thispageurl);
$PAGE->set_title(get_string('duplicates', 'qbank_duplicates'));
$PAGE->set_heading($COURSE->fullname);
$PAGE->activityheader->disable();
$PAGE->set_secondary_active_tab('questionbank');

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('core_question', 'bank');
echo $renderer->render(new \core_question\output\qbank_action_menu($thispageurl));
echo $OUTPUT->heading(get_string('duplicates', 'qbank_duplicates'));
echo html_writer::tag('p', get_string(
    'intro',
    'qbank_duplicates',
    format_string($category->name, true, ['context' => $categorycontext])
));

$groups = finder::in_category((int)$category->id);
if (!$groups) {
    echo $OUTPUT->notification(get_string('none', 'qbank_duplicates'), 'success', false);
}
$returnurl = $thispageurl->out_as_local_url(false);
foreach ($groups as $group) {
    $first = reset($group);
    $a = (object)['count' => count($group), 'qtype' => question_bank::get_qtype_name($first->qtype)];
    $items = [];
    foreach ($group as $q) {
        $edit = new moodle_url(
            '/question/bank/editquestion/question.php',
            ['id' => $q->id, 'cmid' => $cmid, 'returnurl' => $returnurl]
        );
        $items[] = html_writer::link($edit, format_string($q->name, true, ['context' => $categorycontext])) .
            ' · ' . userdate($q->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
    }
    $text = shorten_text(format_text($first->questiontext, FORMAT_HTML, ['context' => $categorycontext]), 200);
    echo html_writer::div(
        $OUTPUT->heading(get_string('group', 'qbank_duplicates', $a), 4) .
        html_writer::div($text, 'text-muted mb-2') .
        html_writer::alist($items),
        'qbank-duplicates-group mb-4'
    );
}
echo $OUTPUT->footer();
