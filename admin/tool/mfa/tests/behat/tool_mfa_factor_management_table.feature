@tool @tool_mfa
Feature: An administrator can manage Factor plugins table
  In order to alter the user experience
  As an admin
  I can manage Factors settings

  @javascript
  Scenario: An administrator can control the enabled state and ordering of Factor plugins using JavaScript
    Given I am logged in as "admin"
    And I navigate to "Plugins > Admin tools > Multi-factor authentication > Manage multi-factor authentication" in site administration
    # Enable and disable Factor.
    When I toggle the "Enable Trust this device" admin switch "on"
    And I should see "Trust this device enabled."
    And I reload the page
    And I should see "Disable Trust this device"
    And I toggle the "Disable Trust this device" admin switch "off"
    And I should see "Trust this device disabled."
    # Ordering Factors.
    Then I toggle the "Enable Trust this device" admin switch "on"
    And I toggle the "Enable Optional MFA" admin switch "on"
    And I click on "Move up" "link" in the "Optional MFA" "table_row"
    And "Optional MFA" "table_row" should appear before "Trust this device" "table_row"
    And I click on "Move down" "link" in the "Optional MFA" "table_row"
    And "Optional MFA" "table_row" should appear after "Trust this device" "table_row"
