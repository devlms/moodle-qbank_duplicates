@qbank @qbank_duplicates @javascript
Feature: Find duplicate questions
  In order to clean up a question bank after imports and copies
  As a teacher
  I need to see questions whose text is the same

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | teacher1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Duplicates in a question bank activity (Moodle 5.0 and later)
    Given the site is running Moodle version 5.0 or higher
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |
    And the following "questions" exist:
      | questioncategory   | qtype     | name     | questiontext                 |
      | Default for Qbank 1 | truefalse | Original | Moodle is written in PHP.    |
      | Default for Qbank 1 | truefalse | Copy     | moodle is written in php     |
      | Default for Qbank 1 | truefalse | Other    | Moodle is written in Java.   |
    When I am on the "Qbank 1" "core_question > question bank" page logged in as "teacher1"
    And I select "Duplicates" from the "Question bank tertiary navigation" singleselect
    Then I should see "2 questions of type True/False"
    And I should see "Original"
    And I should see "Copy"
    And I should not see "Other"

  Scenario: Duplicates in a course question bank (Moodle 4.5)
    Given the site is running Moodle version 4.5 or lower
    And the following "questions" exist:
      | questioncategory | qtype     | name     | questiontext              |
      | Default for C1   | truefalse | Original | Moodle is written in PHP. |
      | Default for C1   | truefalse | Copy     | moodle is written in php  |
    When I am on the "Course 1" "core_question > course question bank" page logged in as "teacher1"
    And I select "Duplicates" from the "Question bank tertiary navigation" singleselect
    Then I should see "2 questions of type True/False"
