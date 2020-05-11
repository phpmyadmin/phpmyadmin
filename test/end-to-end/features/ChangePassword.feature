Feature: Change the user password
    Change the user password and set back to original value
Scenario: Change the user password to a random password and set back to original value
    Given user credentials for cookie login
    Given that I am logged in as a created user
    Then I wait for a link that contains "Change password"
    Then I click a link that contains "Change password"
    Then I wait for a text that contains "Generate password"
    Then the input #text_pma_change_pw should contain ""
    Then the input #text_pma_change_pw2 should contain ""
    Then the input #generated_pw should contain ""
    Then I click an input button "Generate"
    Then I wait for a text that contains "Strong"
    Then the input #text_pma_change_pw should not contain ""
    Then the input #text_pma_change_pw2 should not contain ""
    Then the input #generated_pw should not contain ""
    Then I click a button "Go"
    Then I wait for request to end
    Then I wait for a text that contains "Create PHP code"
    Then I should see a success message "The profile has been updated."
    Then I click a link that contains "Server: "
    Then I click a link that contains "Change password"
    Then I wait for a text that contains "Generate password"
    Then the input #text_pma_change_pw should contain ""
    Then the input #text_pma_change_pw2 should contain ""
    Then the input #generated_pw should contain ""
    Then I fill #text_pma_change_pw with <passwordForChangePasswordUser>
    Then I fill #text_pma_change_pw2 with <passwordForChangePasswordUser>
    Then I click a button "Go"
    Then I wait for request to end
    Then I wait for a text that contains "Create PHP code"
    Then I should see a success message "The profile has been updated."
    Then I drop the created user

Scenario: Try to change the password to an empty value and set back to original value
    Given user credentials for cookie login
    Given that I am logged in as a created user
    Then I wait for a link that contains "Change password"
    Then I click a link that contains "Change password"
    Then I wait for a text that contains "Generate password"
    Then I will confirm a dialog that says "The password is empty!"
    Then I click a button "Go"
    Then I check the radio for the label "No Password"
    Then I click a button "Go"
    Then I wait for request to end
    Then I wait for a text that contains "Create PHP code"
    Then I should see a success message "The profile has been updated."
    Then I click a link that contains "Server: "
    Then I click a link that contains "Change password"
    Then I wait for a text that contains "Generate password"
    Then I fill #text_pma_change_pw with <passwordForChangePasswordUser>
    Then I fill #text_pma_change_pw2 with <passwordForChangePasswordUser>
    Then I click a button "Go"
    Then I wait for request to end
    Then I wait for a text that contains "Create PHP code"
    Then I should see a success message "The profile has been updated."
    Then I drop the created user
    Then I logout
