<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_ajaxResponse from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_ajaxResponse_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_ajaxResponse_test extends PHPUnit_Extensions_OutputTestCase
{
    function testAjaxResponseText()
    {
        $message = 'text';

        $this->expectOutputString('{"success":true,"message":"' . $message . '"}');
        PMA_ajaxResponse($message);
    }

    function testAjaxResponseTextWithExtra()
    {
        $message = 'text';
        $exra = array('str_val' => 'te\x/t"1', 'int_val' => 10);

        $this->expectOutputString('{"success":true,"message":"' . $message . '","str_val":"te\\\\x\/t\"1","int_val":10}');
        PMA_ajaxResponse($message, true, $exra);
    }

    function testAjaxResponseTextError()
    {
        $message = 'error_text';

        $this->expectOutputString('{"success":false,"error":"' . $message . '"}');
        PMA_ajaxResponse($message, false);
    }

    function testAjaxResponseMessage()
    {
        $message = new PMA_Message("Message Text", 1);

        $this->expectOutputString('{"success":true,"message":"<div class=\"success\">Message Text<\/div>"}');
        PMA_ajaxResponse($message);
    }

    function testAjaxResponseMessageWithExtra()
    {

        $message = new PMA_Message("Message Text", 1);
        $exra = array('str_val' => 'te\x/t"1', 'int_val' => 10);

        $this->expectOutputString('{"success":true,"message":"<div class=\"success\">Message Text<\/div>","str_val":"te\\\\x\/t\"1","int_val":10}');
        PMA_ajaxResponse($message, true, $exra);
    }

    function testAjaxResponseMessageError()
    {

        $message = new PMA_Message("Error Message Text", 1);

        // TODO: class for output div should be "error"
        $this->expectOutputString('{"success":false,"error":"<div class=\"success\">Error Message Text<\/div>"}');
        PMA_ajaxResponse($message, false);
    }

}