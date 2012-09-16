<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for parsing of Routine parameters
 *
 * @package PhpMyAdmin-test
 */

/*
 * Needed for PMA_Util::unQuote() and PMA_SQP_parse()
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.lib.php';

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';


class PMA_RTN_parameterParser_test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider definer_provider
     */
    public function test_parseDefiner($source, $target)
    {
        PMA_RTN_setGlobals();
        $this->assertEquals($target, PMA_RTN_parseRoutineDefiner(PMA_SQP_parse($source)));
    }

    public function definer_provider()
    {
        return array(
            array('CREATE PROCEDURE FOO() SELECT NULL', ''),
            array('CREATE DEFINER=`root`@`localhost` PROCEDURE FOO() SELECT NULL', 'root@localhost'),
            array('CREATE DEFINER=`root\\`@`localhost` PROCEDURE FOO() SELECT NULL', 'root\\@localhost'),
        );
    }

    /**
     * @dataProvider param_provider
     */
    public function test_parseOneParameter($source, $target)
    {
        PMA_RTN_setGlobals();
        $this->assertEquals($target, PMA_RTN_parseOneParameter($source));
    }

    public function param_provider()
    {
        return array(
            array('`foo` TEXT', array('', 'foo', 'TEXT', '', '')),
            array('`foo` INT(20)', array('', 'foo', 'INT', '20', '')),
            array('DECIMAL(5,5)', array('', '', 'DECIMAL', '5,5', '')),
            array('IN `fo``fo` INT UNSIGNED', array('IN', 'fo`fo', 'INT', '', 'UNSIGNED')),
            array('OUT bar VARCHAR(1) CHARSET utf8', array('OUT', 'bar', 'VARCHAR', '1', 'utf8')),
            array('`"baz\'\'` ENUM(\'a\', \'b\') CHARSET latin1', array('', '"baz\'\'', 'ENUM', '\'a\',\'b\'', 'latin1')),
            array('INOUT `foo` DECIMAL(5,2) UNSIGNED ZEROFILL', array('INOUT', 'foo', 'DECIMAL', '5,2', 'UNSIGNED ZEROFILL')),
            array('`foo``s func` SET(\'test\'\'esc"\',   \'more\\\'esc\')', array('', 'foo`s func', 'SET', '\'test\'\'esc"\',\'more\\\'esc\'', ''))
        );
    }

    /**
     * @depends test_parseOneParameter
     * @dataProvider query_provider
     */
    public function test_parseAllParameters($query, $type, $target)
    {
        PMA_RTN_setGlobals();
        $this->assertEquals($target, PMA_RTN_parseAllParameters(PMA_SQP_parse($query), $type));
    }

    public function query_provider()
    {
        return array(
            array(
                'CREATE PROCEDURE `foo`() SET @A=0',
                'PROCEDURE',
                array(
                    'num' => 0,
                    'dir' => array(),
                    'name' => array(),
                    'type' => array(),
                    'length' => array(),
                    'opts' => array()
                )
            ),
            array(
                'CREATE DEFINER=`user\\`@`somehost``(` FUNCTION `foo```(`baz` INT) BEGIN SELECT NULL; END',
                'FUNCTION',
                array(
                    'num' => 1,
                    'dir' => array(
                        0 => ''
                    ),
                    'name' => array(
                        0 => 'baz'
                    ),
                    'type' => array(
                        0 => 'INT'
                    ),
                    'length' => array(
                        0 => ''
                    ),
                    'opts' => array(
                        0 => ''
                    )
                )
            ),
            array(
                'CREATE PROCEDURE `foo`(IN `baz\\)` INT(25) zerofill unsigned) BEGIN SELECT NULL; END',
                'PROCEDURE',
                array(
                    'num' => 1,
                    'dir' => array(
                        0 => 'IN'
                    ),
                    'name' => array(
                        0 => 'baz\\)'
                    ),
                    'type' => array(
                        0 => 'INT'
                    ),
                    'length' => array(
                        0 => '25'
                    ),
                    'opts' => array(
                        0 => 'UNSIGNED ZEROFILL'
                    )
                )
            ),
            array(
                'CREATE PROCEDURE `foo`(IN `baz\\` INT(001) zerofill, out bazz varchar(15) charset UTF8) BEGIN SELECT NULL; END',
                'PROCEDURE',
                array(
                    'num' => 2,
                    'dir' => array(
                        0 => 'IN',
                        1 => 'OUT'
                    ),
                    'name' => array(
                        0 => 'baz\\',
                        1 => 'bazz'
                    ),
                    'type' => array(
                        0 => 'INT',
                        1 => 'VARCHAR'
                    ),
                    'length' => array(
                        0 => '1',
                        1 => '15'
                    ),
                    'opts' => array(
                        0 => 'ZEROFILL',
                        1 => 'utf8'
                    )
                )
            ),
        );
    }
}
?>
