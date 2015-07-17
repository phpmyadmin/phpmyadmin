<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
* tests for sql-formatter/lib/SqlFormatter.php
*
* @package PhpMyAdmin-test
*/

/*
* Include to test.
*/
require_once 'libraries/sql-formatter/lib/SqlFormatter.php';

/**
* tests for SqlFormatter
*
* @package PhpMyAdmin-test
*/
class SqlFormatter_Test extends PHPUnit_Framework_TestCase
{
    /**
    * Data provider for testSqlFormatter_format
    *
    * @return array with test data
    */
    public function formatDataProvider() {
        return array(
array(
"SELECT * FROM `test`",
"SELECT 
	* 
FROM 
	`test`",
),

array(
"SELECT customer_id, customer_name, COUNT(order_id) as total FROM customers
INNER JOIN orders ON customers.customer_id = orders.customer_id GROUP BY customer_id,
customer_name HAVING COUNT(order_id) > 5 ORDER BY COUNT(order_id) DESC;",
"SELECT 
	customer_id, 
	customer_name, 
	COUNT(order_id) as total 
FROM 
	customers 
	INNER JOIN orders ON customers.customer_id = orders.customer_id 
GROUP BY 
	customer_id, 
	customer_name 
HAVING 
	COUNT(order_id) > 5 
ORDER BY 
	COUNT(order_id) DESC;"
),

array(
"SELECT a,b as c FROM `ab`; UPDATE `cd` SET `col` = REPLACE(col, 'find', 'replace')
WHERE row_id in (SELECT row_id FROM new_table WHERE col = 's' AND col2 = '3') LIMIT 256",
"SELECT 
	a, 
	b as c 
FROM 
	`ab`; 
UPDATE 
	`cd` 
SET 
	`col` = REPLACE(col, 'find', 'replace') 
WHERE 
	row_id in (
		SELECT 
			row_id 
		FROM 
			new_table 
		WHERE 
			col = 's' 
			AND col2 = '3'
	) 
LIMIT 
	256"
),

array(
"INSERT INTO `a_long_table_name_it_is_really_log_but_still_not_that_long`
(a, b, c, d, e, f, g, a, b, c, d, e, f, c, d, e) 
VALUES (1, 0, '', 1, NOW(), NOW(), 0),
(1, 0, 'helloabcdefgijk', 1, 'hello_world_again', NOW(), 0)",
"INSERT INTO `a_long_table_name_it_is_really_log_but_still_not_that_long` (
	a, b, c, d, e, f, g, a, b, c, d, e, f, c, d, e
) 
VALUES 
	(1, 0, '', 1, NOW(), NOW(), 0), 
	(
		1, 0, 'helloabcdefgijk', 1, 'hello_world_again', 
		NOW(), 0
	)"
),

array(
"ALTER TABLE `PREFIX_product` DROP `reduction_price`,DROP `reduction_percent`, 
DROP `reduction_from`, DROP `reduction_to`",
"ALTER TABLE 
	`PREFIX_product` 
DROP 
	`reduction_price`, 
DROP 
	`reduction_percent`, 
DROP 
	`reduction_from`, 
DROP 
	`reduction_to`"
),
        );
    }

    /**
    * Test for SqlFormatter::format
    *
    * @return void
    *
    * @dataProvider formatDataProvider
    */
    public function testSqlFormatter_format($query, $expected)
    {
        SqlFormatter::$tab = "\t";
        $this->assertEquals(
            $expected,
            SqlFormatter::format($query, false)
        );
    }
}
