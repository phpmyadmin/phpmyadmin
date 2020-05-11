Feature: User password login
    Login to phpMyAdmin using user and password
Scenario: Fill inputs and click login
    Given user credentials for cookie login
    Given that I browse "/"
    Then I wait for a text that contains "Welcome to phpMyAdmin"
    Then I empty #input_username
    When I fill #input_username with <username>
    Then the input #input_username should contain <username>
    When I fill #input_password with <password>
    Then the input #input_password should contain <password>
    When I click an input button "Go"
    Then I wait for a link that contains "Server:"
    Then I wait for a link that contains "Databases"
    Then I wait for a text that contains "Server version:"
    Then I logout

Scenario: Login fails
    Given that I browse "/"
    Then I wait for a text that contains "Welcome to phpMyAdmin"
    Then I empty #input_username
    When I fill #input_username with "anonymous"
    Then the input #input_username should contain "anonymous"
    When I fill #input_password with "anonymous"
    Then the input #input_password should contain "anonymous"
    When I click an input button "Go"
    Then I wait for a text that contains "Cannot log in to the MySQL server"
