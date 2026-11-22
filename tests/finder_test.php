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

namespace qbank_duplicates;

use qbank_duplicates\local\finder;

/**
 * Tests for finding duplicate questions in a category.
 *
 * @package    qbank_duplicates
 * @copyright  2026 DevLMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_duplicates\local\finder
 */
final class finder_test extends \advanced_testcase {
    /**
     * Make a question with this text.
     *
     * @param \core_question_generator $qgen
     * @param int $catid
     * @param string $qtype
     * @param string $name
     * @param string $text
     * @return \stdClass
     */
    private function q($qgen, int $catid, string $qtype, string $name, string $text): \stdClass {
        global $DB;
        $q = $qgen->create_question($qtype, null, ['category' => $catid, 'name' => $name]);
        $DB->set_field('question', 'questiontext', $text, ['id' => $q->id]);
        return $q;
    }

    public function test_same_text_and_type_are_grouped(): void {
        $this->resetAfterTest();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $qgen->create_question_category();
        $a = $this->q($qgen, $cat->id, 'truefalse', 'A', '<p>Moodle is written in PHP.</p>');
        $b = $this->q($qgen, $cat->id, 'truefalse', 'B', '<p>moodle  is written in <b>PHP</b></p>');
        $this->q($qgen, $cat->id, 'truefalse', 'C', '<p>Moodle is written in Java.</p>');
        $this->q($qgen, $cat->id, 'shortanswer', 'D', '<p>Moodle is written in PHP.</p>');

        $groups = finder::in_category($cat->id);
        $this->assertCount(1, $groups);
        $ids = array_map(fn($q) => (int)$q->id, $groups[0]);
        sort($ids);
        $this->assertSame([(int)$a->id, (int)$b->id], $ids);
    }

    public function test_only_the_latest_version_counts(): void {
        $this->resetAfterTest();
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $qgen->create_question_category();
        $this->q($qgen, $cat->id, 'truefalse', 'A', 'Same text');
        $b = $this->q($qgen, $cat->id, 'truefalse', 'B', 'Same text');
        // Editing B saves a new version with new text; the old version must not count.
        $new = $qgen->update_question($b, null, ['name' => 'B2']);
        global $DB;
        $DB->set_field('question', 'questiontext', 'Different text now', ['id' => $new->id]);
        $this->assertSame([], finder::in_category($cat->id));
    }

    public function test_normalise(): void {
        $this->assertSame('what is 2 2', finder::normalise('<p>What is <b>2 + 2</b>?</p>'));
        $this->assertSame('café au lait', finder::normalise('Caf&eacute;&nbsp;au   LAIT!'));
    }
}
