<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Advisor class
 *
 * @package phpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Advisor.class.php';

class Advisor_test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider escapeStrings
     */
    public function testEscape($text, $expected)
    {
        $this->assertEquals(Advisor::escapePercent($text), $expected);
    }

    public function escapeStrings() {
        return array(
            array('80%', '80%%'),
            array('%s%', '%s%%'),
            array('80% foo', '80%% foo'),
            array('%s% foo', '%s%% foo'),
            );
    }

    public function testParse()
    {
        $advisor = new Advisor();
        $parseResult = $this->parseRulesFile();
        $this->assertEquals($parseResult['errors'], array());
    }
}
?>
