<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under PhpMyAdmin\UserPreferences class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\UserPreferences;

/**
 * tests for methods under PhpMyAdmin\UserPreferences class
 *
 * @package PhpMyAdmin-test
 */
class UserPreferencesTest extends PmaTestCase
{
    /**
     * @var UserPreferences
     */
    private $userPreferences;

    /**
     * Setup various pre conditions
     *
     * @return void
     */
    protected function setUp()
    {
        global $cfg;
        include 'libraries/config.default.php';
        $GLOBALS['server'] = 0;
        $GLOBALS['PMA_PHP_SELF'] = '/phpmyadmin/';

        $this->userPreferences = new UserPreferences();
    }

    /**
     * Test for pageInit
     *
     * @return void
     */
    public function testPageInit()
    {
        $GLOBALS['cfg'] = array(
            'Server/hide_db' => 'testval123',
            'Server/port' => '213'
        );
        $GLOBALS['cfg']['AvailableCharsets'] = array();
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;

        $this->userPreferences->pageInit(new ConfigFile());

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
     * Test for load
     *
     * @return void
     */
    public function testLoad()
    {
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;

        $_SESSION['relation'][$GLOBALS['server']]['userconfigwork'] = null;
        unset($_SESSION['userconfig']);

        $result = $this->userPreferences->load();

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

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts '
            . 'FROM `pma\'db`.`testconf` WHERE `username` = \'user\'';

        $dbi->expects($this->once())
            ->method('fetchSingleRow')
            ->with($query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL)
            ->will(
                $this->returnValue(
                    array(
                        'ts' => '123',
                        'config_data' => json_encode(array(1, 2))
                    )
                )
            );
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->userPreferences->load();

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
     * Test for save
     *
     *  @return void
     */
    public function testSave()
    {
        $GLOBALS['server'] = 2;
        $_SESSION['relation'][2]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][2]['userconfigwork'] = null;
        unset($_SESSION['userconfig']);

        $result = $this->userPreferences->save(array(1));

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

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` '
            . 'WHERE `username` = \'user\'';

        $query2 = 'UPDATE `pmadb`.`testconf` SET `timevalue` = NOW(), `config_data` = \''
            . json_encode(array(1)) . '\' WHERE `username` = \'user\'';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, 0, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(
            $this->userPreferences->save(array(1))
        );

        // case 3

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` '
            . 'WHERE `username` = \'user\'';

        $query2 = 'INSERT INTO `pmadb`.`testconf` (`username`, `timevalue`,`config_data`) '
            . 'VALUES (\'user\', NOW(), \'' . json_encode(array(1)) . '\')';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, 0, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->with(DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue("err1"));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->userPreferences->save(array(1));

        $this->assertEquals(
            'Could not save configuration<br /><br />err1',
            $result->getMessage()
        );
    }

    /**
     * Test for apply
     *
     * @return void
     */
    public function testApply()
    {
        $GLOBALS['cfg']['UserprefsDisallow'] = array(
            'test' => 'val',
            'foo' => 'bar'
        );
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;
        $result = $this->userPreferences->apply(
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
     * Test for apply
     *
     * @return void
     */
    public function testApplyDevel()
    {
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = true;
        $result = $this->userPreferences->apply(
            array(
                'DBG/sql' => true,
            )
        );

        $this->assertEquals(
            array(
                'DBG' => array('sql' => true),
            ),
            $result
        );
    }

    /**
     * Test for persistOption
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

        $this->assertTrue(
            $this->userPreferences->persistOption('Server/hide_db', 'val', 'val')
        );

        $this->assertTrue(
            $this->userPreferences->persistOption('Server/hide_db', 'val2', 'val')
        );

        $this->assertTrue(
            $this->userPreferences->persistOption('Server/hide_db2', 'val', 'val')
        );
    }

    /**
     * Test for redirect
     *
     * @return void
     */
    public function testRedirect()
    {
        $GLOBALS['lang'] = '';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $this->mockResponse('Location: /phpmyadmin/file.html?a=b&saved=1&server=0#h+ash');

        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['PMA_Config']->set('PMA_IS_IIS', false);

        $this->userPreferences->redirect(
            'file.html',
            array('a' => 'b'),
            'h ash'
        );
    }

    /**
     * Test for autoloadGetHeader
     *
     * @return void
     */
    public function testAutoloadGetHeader()
    {
        $_SESSION['userprefs_autoload'] = false;
        $_REQUEST['prefs_autoload'] = 'hide';

        $this->assertEquals(
            '',
            $this->userPreferences->autoloadGetHeader()
        );

        $this->assertTrue(
            $_SESSION['userprefs_autoload']
        );

        $_REQUEST['prefs_autoload'] = 'nohide';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'phpunit';
        $result = $this->userPreferences->autoloadGetHeader();

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
