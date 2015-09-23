<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/config/messages.inc.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for libraries/config/messages.inc.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_MessagesIncTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setup()
    {
    }

    /**
     * Tests messages variables
     *
     * @return void
     * @group medium
     */
    function testMessages()
    {
        $strConfigAllowArbitraryServer_name = '';
        $strConfigAllowThirdPartyFraming_name = '';
        $strConfigblowfish_secret_name = '';
        $strConfigExport_htmlword_structure_or_data_name = '';
        $strConfigForm_TableStructure = '';
        $strConfigSQLQuery_Explain_name = '';
        $strConfigSendErrorReports_name = '';

        include 'libraries/config/messages.inc.php';

        $this->assertEquals(
            'Allow login to any MySQL server',
            $strConfigAllowArbitraryServer_name
        );
        $this->assertEquals(
            'Allow third party framing',
            $strConfigAllowThirdPartyFraming_name
        );
        $this->assertEquals(
            'Blowfish secret',
            $strConfigblowfish_secret_name
        );
        $this->assertEquals(
            'Dump table',
            $strConfigExport_htmlword_structure_or_data_name
        );
        $this->assertEquals(
            'Table structure',
            $strConfigForm_TableStructure
        );
        $this->assertEquals(
            'Explain SQL',
            $strConfigSQLQuery_Explain_name
        );
        $this->assertEquals(
            'Send error reports',
            $strConfigSendErrorReports_name
        );
    }
}
