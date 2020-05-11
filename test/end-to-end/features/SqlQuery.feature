Feature: SQL query test insert data
    SQL query test
Scenario: Select known data and see it
    Given user credentials for cookie login
    Given that I am logged in as the test user
    Then I wait for a link that contains "SQL"
    Then I click tab "SQL"
    Then I wait for a text that contains "Rollback when finished"
    Then I fill SQL CodeMirror with "SET @t1=1, @t2=2, @t3:=4; SELECT 1 as `id`, @t1, @t2, @t3, @t4 := @t1+@t2+@t3;"
    Then I click an input button "Go"
    Then I wait for a text that contains "@t4 := @t1+@t2+@t3"
    Then I see the following data results in "table_results"
        |   |   |   |   |   |
        | 1 | 1 | 2 | 4 | 7 |

Scenario: Create a table and insert data
    Given the homepage as an already logged in user
    Then I wait for a link that contains "SQL"
    Then I click tab "SQL"
    Then I wait for a text that contains "Rollback when finished"
    Then I fill SQL CodeMirror with "DROP DATABASE IF EXISTS `__test__selenium__feature`; CREATE DATABASE `__test__selenium__feature`; use `__test__selenium__feature`; CREATE TABLE `test_table` (`id` int(11) NOT NULL AUTO_INCREMENT, `val` int(11) NOT NULL, PRIMARY KEY (`id`));"
    Then I click an input button "Go"
    Then I wait for a text that contains "empty result set"
    Then I click tab "SQL"
    Then I wait for a text that contains "Rollback when finished"
    Then I fill SQL CodeMirror with "use `__test__selenium__feature`; INSERT INTO `test_table` (val) VALUES (2), (3), (4), (5);"
    Then I click an input button "Go"
    Then I wait for a text that contains "empty result set"
    Then I wait for a text that contains "4 rows inserted."
    Then I wait for a link that contains "SQL"
    Then I click tab "SQL"
    Then I wait for a text that contains "Rollback when finished"
    Then I fill SQL CodeMirror with "SET @t1=1, @t2=2, @t3:=4; SELECT 1 as `id`, @t1, @t2, @t3, @t4 := @t1+@t2+@t3;"
    Then I click an input button "Go"
    Then I wait for a text that contains "empty result set"
    Then I wait for a link that contains "__test__selenium__feature"
    Then I click a link "__test__selenium__feature"
    Then I wait for a link that contains "test_table"
    Then I click a link "test_table"
    Then I wait for a text that contains "Table: test_table"
    Then I see the following data results in "table_results"
        |      |      |      |        |   |   |
        |      | Edit | Copy | Delete | 1 | 2 |
        |      | Edit | Copy | Delete | 2 | 3 |
        |      | Edit | Copy | Delete | 3 | 4 |
        |      | Edit | Copy | Delete | 4 | 5 |
    Then I logout
