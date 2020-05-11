Feature: Sql query box
    Sql query box
Scenario: Try empty sql query
    Given user credentials for cookie login
    Given that I am logged in as the test user
    Then I wait for a link that contains "SQL"
    Then I click tab "SQL"
    Then I wait for a text that contains "Run SQL query/queries on server"
    Then I will confirm a dialog that says "Missing value in the form!"
    When I click #button_submit_query
    Then I logout
