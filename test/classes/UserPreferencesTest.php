<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Message;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;
use ReflectionClass;

use function json_encode;
use function time;

/** @covers \PhpMyAdmin\UserPreferences */
class UserPreferencesTest extends AbstractNetworkTestCase
{
    /**
     * Setup various pre conditions
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $GLOBALS['text_dir'] = 'ltr';
        $_SERVER['PHP_SELF'] = '/phpmyadmin/';
    }

    /**
     * Test for pageInit
     */
    public function testPageInit(): void
    {
        $GLOBALS['cfg'] = ['Server/hide_db' => 'testval123', 'Server/port' => '213'];
        $GLOBALS['cfg']['AvailableCharsets'] = [];
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $userPreferences->pageInit(new ConfigFile());

        $this->assertEquals(
            ['Servers' => [1 => ['hide_db' => 'testval123']]],
            $_SESSION['ConfigFile' . $GLOBALS['server']],
        );
    }

    /**
     * Test for load
     */
    public function testLoad(): void
    {
        $relation = RelationParameters::fromArray([]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        unset($_SESSION['userconfig']);

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $result = $userPreferences->load();

        $this->assertCount(3, $result);

        $this->assertEquals(
            [],
            $result['config_data'],
        );

        $this->assertEqualsWithDelta(
            time(),
            $result['mtime'],
            2,
            '',
        );

        $this->assertEquals('session', $result['type']);

        // case 2
        $relation = RelationParameters::fromArray([
            'user' => 'user',
            'db' => "pma'db",
            'userconfig' => 'testconf',
            'userconfigwork' => true,
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts '
            . 'FROM `pma\'db`.`testconf` WHERE `username` = \'user\'';

        $dbi->expects($this->once())
            ->method('fetchSingleRow')
            ->with($query, DatabaseInterface::FETCH_ASSOC, Connection::TYPE_CONTROL)
            ->will(
                $this->returnValue(
                    ['ts' => '123', 'config_data' => json_encode([1, 2])],
                ),
            );
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $userPreferences = new UserPreferences($dbi);
        $result = $userPreferences->load();

        $this->assertEquals(
            ['config_data' => [1, 2], 'mtime' => 123, 'type' => 'db'],
            $result,
        );
    }

    /**
     * Test for save
     */
    public function testSave(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['server'] = 2;
        $relation = RelationParameters::fromArray([]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        unset($_SESSION['userconfig']);

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $result = $userPreferences->save([1]);

        $this->assertTrue($result);

        $this->assertCount(2, $_SESSION['userconfig']);

        $this->assertEquals(
            [1],
            $_SESSION['userconfig']['db'],
        );

        /* TODO: This breaks sometimes as there might be time difference! */
        $this->assertEqualsWithDelta(
            time(),
            $_SESSION['userconfig']['ts'],
            2,
            '',
        );

        $assert = true;

        if (isset($_SESSION['cache']['server_2']['userprefs'])) {
            $assert = false;
        }

        $this->assertTrue($assert);

        // case 2
        $relation = RelationParameters::fromArray([
            'userconfigwork' => true,
            'db' => 'pmadb',
            'userconfig' => 'testconf',
            'user' => 'user',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'UPDATE `pmadb`.`testconf` SET `timevalue` = NOW(), `config_data` = \''
            . json_encode([1]) . '\' WHERE `username` = \'user\'';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, Connection::TYPE_CONTROL)
            ->will($this->returnValue('1'));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, Connection::TYPE_CONTROL)
            ->will($this->returnValue($this->createStub(DummyResult::class)));

        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $userPreferences = new UserPreferences($dbi);
        $result = $userPreferences->save([1]);

        $this->assertTrue($result);

        // case 3

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'INSERT INTO `pmadb`.`testconf` (`username`, `timevalue`,`config_data`) '
            . 'VALUES (\'user\', NOW(), \'' . json_encode([1]) . '\')';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, Connection::TYPE_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, Connection::TYPE_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->with(Connection::TYPE_CONTROL)
            ->will($this->returnValue('err1'));
        $dbi->expects($this->any())
            ->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $userPreferences = new UserPreferences($dbi);
        $result = $userPreferences->save([1]);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals(
            'Could not save configuration<br><br>err1'
            . '<br><br>The phpMyAdmin configuration storage database could not be accessed.',
            $result->getMessage(),
        );
    }

    /**
     * Test for apply
     */
    public function testApply(): void
    {
        $GLOBALS['cfg']['UserprefsDisallow'] = ['test' => 'val', 'foo' => 'bar'];
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $result = $userPreferences->apply(
            [
                'DBG/sql' => true,
                'ErrorHandler/display' => true,
                'ErrorHandler/gather' => false,
                'Servers/foobar' => '123',
                'Server/hide_db' => true,
            ],
        );

        $this->assertEquals(
            ['Server' => ['hide_db' => 1]],
            $result,
        );
    }

    /**
     * Test for apply
     */
    public function testApplyDevel(): void
    {
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = true;

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $result = $userPreferences->apply(
            ['DBG/sql' => true],
        );

        $this->assertEquals(
            ['DBG' => ['sql' => true]],
            $result,
        );
    }

    /**
     * Test for persistOption
     */
    public function testPersistOption(): void
    {
        $relation = RelationParameters::fromArray([]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        $_SESSION['userconfig'] = [];
        $_SESSION['userconfig']['ts'] = '123';
        $_SESSION['userconfig']['db'] = ['Server/hide_db' => true, 'Server/only_db' => true];

        $GLOBALS['server'] = 2;
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([$GLOBALS['server'] => $relation]);

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $this->assertTrue(
            $userPreferences->persistOption('Server/hide_db', 'val', 'val'),
        );

        $this->assertTrue(
            $userPreferences->persistOption('Server/hide_db', 'val2', 'val'),
        );

        $this->assertTrue(
            $userPreferences->persistOption('Server/hide_db2', 'val', 'val'),
        );
    }

    /**
     * Test for redirect
     */
    public function testRedirect(): void
    {
        $GLOBALS['lang'] = '';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $this->mockResponse('Location: /phpmyadmin/file.html?a=b&saved=1&server=0#h+ash');

        $GLOBALS['config']->set('PmaAbsoluteUri', '');
        $GLOBALS['config']->set('PMA_IS_IIS', false);

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $userPreferences->redirect(
            'file.html',
            ['a' => 'b'],
            'h ash',
        );
    }

    /**
     * Test for autoloadGetHeader
     */
    public function testAutoloadGetHeader(): void
    {
        $_SESSION['userprefs_autoload'] = false;
        $_REQUEST['prefs_autoload'] = 'hide';

        $userPreferences = new UserPreferences($GLOBALS['dbi']);
        $this->assertEquals(
            '',
            $userPreferences->autoloadGetHeader(),
        );

        $this->assertTrue($_SESSION['userprefs_autoload']);

        $_REQUEST['prefs_autoload'] = 'nohide';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $result = $userPreferences->autoloadGetHeader();

        $this->assertStringContainsString(
            '<form action="' . Url::getFromRoute('/preferences/manage') . '" method="post" class="disableAjax">',
            $result,
        );

        $this->assertStringContainsString('<input type="hidden" name="token" value="token"', $result);

        $this->assertStringContainsString('<input type="hidden" name="json" value="">', $result);

        $this->assertStringContainsString('<input type="hidden" name="submit_import" value="1">', $result);

        $this->assertStringContainsString('<input type="hidden" name="return_url" value="?">', $result);
    }
}
