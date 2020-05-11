Feature: Create and drop a database
    Create and drop a database using the database page
Scenario: Fill a database name and submit, go to Operations and drop the database
    Given user credentials for cookie login
    Given that I am logged in as the test user
    Then I wait for a link that contains "Databases"
    Then I click tab "Databases"
    Then I wait for a text that contains "Create database"
    When I fill #text_create_db with "db_test"
    Then the input #text_create_db should contain "db_test"
    Then I click #buttonGo
    Then I wait for a link that contains "Database: db_test"
    Then I click tab "Operations"
    Then I wait for a text that contains "Remove database"
    Then I wait for a text that contains "Drop the database (DROP)"
    Then I click a link that contains "Drop the database (DROP)"
    Then I wait for a text that contains "You are about to DESTROY a complete database!"
    Then I wait for a text that contains "OK"
    Then I click .submitOK
    Then I wait for a text that contains "Create database"
    Then I logout
