<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under user_preferences library
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */
use PMA\libraries\config\ConfigFile;

require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sanitizing.lib.php';


/**
 * tests for methods under user_preferences library
 *
 * @package PhpMyAdmin-test
 */
class PMA_User_Preferences_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Setup various pre conditions
     *
     * @return void
     */
    function setUp()
    {
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for PMA_userprefsPageInit
     *
     * @return void
     */
    public function testUserPrefPageInit()
    {
        $GLOBALS['cfg'] = array(
            'Server/hide_db' => 'testval123',
            'Server/only_db' => 'test213'
        );
        $GLOBALS['cfg']['AvailableCharsets'] = array();
        $GLOBALS['forms'] = array(
            'form1' => array(
                array('Servers/1/hide_db', 'bar'),
                array('test' => 'val')
            )
        );

        PMA_userprefsPageInit(new ConfigFile());

        $this->assertEquals(
            array(
                'Servers' => array(
                    1 => array(
                        'hide_db' => 'testval123'
                    )
                )
            ),
            $_SESSION['ConfigFile' . $GLOBALS['server']]
        );
    }

    /**
     * Test for PMA_loadUserprefs
     *
     * @return void
     */
    public function testLoadUserprefs()
    {
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;

        $_SESSION['relation'][$GLOBALS['server']]['userconfigwork'] = null;
        unset($_SESSION['userconfig']);

        $result = PMA_loadUserprefs();

        $this->assertCount(
            3,
            $result
        );

        $this->assertEquals(
            array(),
            $result['config_data']
        );

        $this->assertEquals(
            time(),
            $result['mtime'],
            '',
            2
        );

        $this->assertEquals(
            'session',
            $result['type']
        );

        // case 2
        $_SESSION['relation'][$GLOBALS['server']]['userconfigwork'] = 1;
        $_SESSION['relation'][$GLOBALS['server']]['db'] = "pma'db";
        $_SESSION['relation'][$GLOBALS['server']]['userconfig'] = "testconf";
        $_SESSION['relation'][$GLOBALS['server']]['user'] = "user";
        $GLOBALS['controllink'] = null;

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts '
            . 'FROM `pma\'db`.`testconf` WHERE `username` = \'user\'';

        $dbi->expects($this->once())
            ->method('fetchSingleRow')
            ->with($query, 'ASSOC', null)
            ->will(
                $this->returnValue(
                    array(
                        'ts' => '123',
                        'config_data' => json_encode(array(1, 2))
                    )
                )
            );
        $GLOBALS['dbi'] = $dbi;

        $result = PMA_loadUserprefs();

        $this->assertEquals(
            array(
                'config_data' => array(1, 2),
                'mtime' => 123,
                'type' => 'db'
            ),
            $result
        );
    }

    /**
     * Test for PMA_saveUserprefs
     *
     *  @return void
     */
    public function testSaveUserprefs()
    {
        $GLOBALS['server'] = 2;
        $_SESSION['relation'][2]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][2]['userconfigwork'] = null;
        unset($_SESSION['userconfig']);

        $result = PMA_saveUserprefs(array(1));

        $this->assertTrue(
            $result
        );

        $this->assertCount(
            2,
            $_SESSION['userconfig']
        );

        $this->assertEquals(
            array(1),
            $_SESSION['userconfig']['db']
        );

        /* TODO: This breaks sometimes as there might be time difference! */
        $this->assertEquals(
            time(),
            $_SESSION['userconfig']['ts'],
            '',
            2
        );

        $assert = true;

        if (isset($_SESSION['cache']['server_2']['userprefs'])) {
            $assert = false;
        }

        $this->assertTrue(
            $assert
        );

        // case 2
        $_SESSION['relation'][$GLOBALS['server']]['userconfigwork'] = 1;
        $_SESSION['relation'][$GLOBALS['server']]['db'] = "pmadb";
        $_SESSION['relation'][$GLOBALS['server']]['userconfig'] = "testconf";
        $_SESSION['relation'][$GLOBALS['server']]['user'] = "user";
        $GLOBALS['controllink'] = null;

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` '
            . 'WHERE `username` = \'user\'';

        $query2 = 'UPDATE `pmadb`.`testconf` SET `timevalue` = NOW(), `config_data` = \''
            . json_encode(array(1)) . '\' WHERE `username` = \'user\'';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, 0, null)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, null)
            ->will($this->returnValue(true));

        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(
            PMA_saveUserprefs(array(1))
        );

        // case 3

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` '
            . 'WHERE `username` = \'user\'';

        $query2 = 'INSERT INTO `pmadb`.`testconf` (`username`, `timevalue`,`config_data`) '
            . 'VALUES (\'user\', NOW(), \'' . json_encode(array(1)) . '\')';

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, 0, null)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, null)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->with(null)
            ->will($this->returnValue("err1"));

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_saveUserprefs(array(1));

        $this->assertEquals(
            'Could not save configuration <br /><br /> err1',
            $result->getMessage()
        );
    }

    /**
     * Test for PMA_applyUserprefs
     *
     * @return void
     */
    public function testApplyUserprefs()
    {
        $GLOBALS['cfg']['UserprefsDisallow'] = array(
            'test' => 'val',
            'foo' => 'bar'
        );
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;
        $result = PMA_applyUserprefs(
            array(
                'DBG/sql' => true,
                'ErrorHandler/display' => true,
                'ErrorHandler/gather' => false,
                'Servers/foobar' => '123',
                'Server/hide_db' => true
            )
        );

        $this->assertEquals(
            array(
                'Server' => array(
                    'hide_db' => 1
                )
            ),
            $result
        );
    }

    /**
     * Test for PMA_readUserprefsFieldNames
     *
     * @return void
     */
    public function testReadUserprefsFieldNames()
    {
        $this->assertGreaterThan(
            0,
            count(PMA_readUserprefsFieldNames())
        );

        $forms = array(
            'form1' => array(
                array('Servers/1/hide_db', 'bar'),
                array('test' => 'val')
            )
        );

        $this->assertEquals(
            array('Servers/1/hide_db', 'bar', 'test'),
            PMA_readUserprefsFieldNames($forms)
        );
    }

    /**
     * Test for PMA_persistOption
     *
     * @return void
     */
    public function testPersistOption()
    {
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['userconfigwork'] = null;
        $_SESSION['userconfig'] = array();
        $_SESSION['userconfig']['ts'] = "123";
        $_SESSION['userconfig']['db'] = array(
            'Server/hide_db' => true,
            'Server/only_db' => true,
        );

        $GLOBALS['server'] = 2;
        $_SESSION['relation'][2]['userconfigwork'] = null;

        $this->assertNull(
            PMA_persistOption('Server/hide_db', 'val', 'val')
        );

        $this->assertNull(
            PMA_persistOption('Server/hide_db', 'val2', 'val')
        );

        $this->assertNull(
            PMA_persistOption('Server/hide_db2', 'val', 'val')
        );
    }

    /**
     * Test for PMA_userprefsRedirect
     *
     * @return void
     */
    public function testUserprefsRedirect()
    {
        if (!defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['lang'] = '';

        $redefine = null;
        if (!defined('PMA_IS_IIS')) {
            define('PMA_IS_IIS', false);
        } else {
            $redefine = PMA_IS_IIS;
            runkit_constant_redefine('PMA_IS_IIS', false);
        }

        PMA_userprefsRedirect(
            'file.html',
            array('a' => 'b'),
            'h ash'
        );

        $this->assertContains(
            'Location: ./file.html?a=b&saved=1&server=0&' .
            'token=token#h+ash',
            $GLOBALS['header'][0]
        );

        if ($redefine !== null) {
            runkit_constant_redefine('PMA_IS_IIS', $redefine);
        } else {
            runkit_constant_remove('PMA_IS_IIS');
        }
    }

    /**
     * Test for PMA_userprefsAutoloadGetHeader
     *
     * @return void
     */
    public function testUserprefsAutoloadGetHeader()
    {
        $_SESSION['userprefs_autoload'] = false;
        $_REQUEST['prefs_autoload'] = 'hide';

        $this->assertEquals(
            '',
            PMA_userprefsAutoloadGetHeader()
        );

        $this->assertTrue(
            $_SESSION['userprefs_autoload']
        );

        $_REQUEST['prefs_autoload'] = 'nohide';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'phpunit';
        $result = PMA_userprefsAutoloadGetHeader();

        $this->assertContains(
            '<form action="prefs_manage.php" method="post" class="disableAjax">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="token" value="token"',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="json" value="" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="submit_import" value="1" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="return_url" value="phpunit?" />',
            $result
        );
    }
}
