@qformat @qformat_gift
Feature: Test importing questions from GIFT format.
  In order to reuse questions
  As an teacher
  I need to be able to import them in GIFT format.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher  | Teacher   |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "activities" exist:
      | activity   | name    | course | idnumber |
      | qbank      | Qbank 1 | C1     | qbank1   |
    And I am on the "Qbank 1" "core_question > question import" page logged in as "teacher"

  @javascript @_file_upload
  Scenario: import some GIFT questions
    When I set the field "id_format_gift" to "1"
    And I upload "question/format/gift/tests/fixtures/questions.gift.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 9 questions from file"
    And I should see "What's between orange and green in the spectrum?"
    When I press "Continue"
    Then I should see "colours"

    # Now export again.
    And I am on the "Qbank 1" "core_question > question export" page
    And I set the field "id_format_gift" to "1"
    And I press "Export questions to file"
    And following "click here" should download a file that:
      | Has mimetype  | text/plain                                       |
      | Contains text | What's between orange and green in the spectrum? |

  @javascript @_file_upload
  Scenario: import a GIFT file which specifies the category
    When I set the field "id_format_gift" to "1"
    And I upload "question/format/gift/tests/fixtures/questions_in_category.gift.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 4 questions from file"
    And I should see "Match the activity to the description."
    When I press "Continue"
    Then I should see "Moodle activities"

  @javascript @_file_upload
  Scenario: import some GIFT questions with unsupported encoding
    When I set the field "id_format_gift" to "1"
    And I upload "question/format/gift/tests/fixtures/questions_encoding_windows-1252.gift.txt" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "The file you selected does not use UTF-8 character encoding. GIFT format files must use UTF-8."
