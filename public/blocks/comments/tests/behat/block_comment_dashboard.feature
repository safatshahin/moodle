@block @block_comments
Feature: Enable Block comments on the dashboard and view comments
  In order to enable the comments block on a the dashboard
  As a teacher
  I can add the comments block to my dashboard

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | First | teacher1@example.com |

  # The dashboard is a JavaScript application (the flexible dashboard grid), so there is no
  # JavaScript-free way to add the block there any more.
  @javascript
  Scenario: Add the comments block on the dashboard and add comments
    When I log in as "teacher1"
    And I turn editing mode on
    And I add the "Comments" block
    And I add "I'm a comment from the teacher" comment to comments block
    Then I should see "I'm a comment from the teacher"
