Feature: Create and remove an user

   Create the user and then remove the created user.

Scenario: Create the user and then remove the created user
    Given user credentials for cookie login
    Given that I am logged in as the test user
    Then I wait for a link that contains "User accounts"
    Then I click tab "User accounts"
    Then I wait for a link that contains "Add user account"
    Then I wait for a text that contains "User accounts overview"
    Then I can not find "testuser" into "#tableuserrights"
    Then I click #add_user_anchor
    Then I wait for a text that contains "Add user account"
    Then I wait for a text that contains "Re-type:"

    When I fill #pma_username with "testuser"
    Then the input #pma_username should contain "testuser"

    Then I empty #pma_hostname
    When I fill #pma_hostname with "%"
    Then the input #pma_hostname should contain "%"

    When I fill #text_pma_pw with "a"
    Then I wait for a text that contains "Extremely weak"
    Then I empty #text_pma_pw
    Then the input #text_pma_pw should not contain "a"
    When I fill #text_pma_pw with "DiffiCultPassw0rd!"
    Then the input #text_pma_pw should contain "DiffiCultPassw0rd!"
    Then I wait for a text that contains "Good"
    Then I click #button_generate_password
    Then I wait for a text that contains "Strong"
    Then the input #text_pma_pw should not contain "DiffiCultPassw0rd!"

    When I click #adduser_submit
    Then I wait for a text that contains "You have added a new user"
    Then I wait for a text that contains "User account"
    Then I wait for a text that contains "testuser"
    Then I wait for a link that contains "User accounts"
    Then I click tab "User accounts"
    Then I wait for a link that contains "Add user account"
    Then I can find "testuser" into "#tableuserrights"

    Then I check the checkbox for the label "testuser"
    Then I click #buttonGo
    Then I wait for a text that contains "Do you really want to revoke the selected user(s) ?"
    Then I wait for a text that contains "OK"
    Then I click .submitOK
    Then I wait for a text that contains "The selected users have been deleted successfully."
    Then I logout
