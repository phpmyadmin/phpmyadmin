<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function json_encode;
use function time;

/**
 * @covers \PhpMyAdmin\UserPreferences
 */
class UserPreferencesTest extends AbstractNetworkTestCase
{
    /** @var UserPreferences */
    private $userPreferences;

    /**
     * Setup various pre conditions
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = '/phpmyadmin/';

        $this->userPreferences = new UserPreferences();
    }

    /**
     * Test for pageInit
     */
    public function testPageInit(): void
    {
        $GLOBALS['cfg'] = [
            'Server/hide_db' => 'testval123',
            'Server/port' => '213',
        ];
        $GLOBALS['cfg']['AvailableCharsets'] = [];
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;

        $this->userPreferences->pageInit(new ConfigFile());

        self::assertSame([
            'Servers' => [
                1 => ['hide_db' => 'testval123'],
            ],
        ], $_SESSION['ConfigFile' . $GLOBALS['server']]);
    }

    /**
     * Test for load
     */
    public function testLoad(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        unset($_SESSION['userconfig']);

        $result = $this->userPreferences->load();

        self::assertCount(3, $result);

        self::assertSame([], $result['config_data']);

        self::assertEqualsWithDelta(time(), $result['mtime'], 2, '');

        self::assertSame('session', $result['type']);

        // case 2
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'user' => 'user',
            'db' => "pma'db",
            'userconfig' => 'testconf',
            'userconfigwork' => true,
        ])->toArray();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts '
            . 'FROM `pma\'db`.`testconf` WHERE `username` = \'user\'';

        $dbi->expects($this->once())
            ->method('fetchSingleRow')
            ->with($query, DatabaseInterface::FETCH_ASSOC, DatabaseInterface::CONNECT_CONTROL)
            ->will(
                $this->returnValue(
                    [
                        'ts' => '123',
                        'config_data' => json_encode([1, 2]),
                    ]
                )
            );
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->userPreferences->load();

        self::assertSame([
            'config_data' => [
                1,
                2,
            ],
            'mtime' => 123,
            'type' => 'db',
        ], $result);
    }

    /**
     * Test for save
     */
    public function testSave(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['server'] = 2;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        unset($_SESSION['userconfig']);

        $result = $this->userPreferences->save([1]);

        self::assertTrue($result);

        self::assertCount(2, $_SESSION['userconfig']);

        self::assertSame([1], $_SESSION['userconfig']['db']);

        /* TODO: This breaks sometimes as there might be time difference! */
        self::assertEqualsWithDelta(time(), $_SESSION['userconfig']['ts'], 2, '');

        $assert = true;

        if (isset($_SESSION['cache']['server_2']['userprefs'])) {
            $assert = false;
        }

        self::assertTrue($assert);

        // case 2
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'userconfigwork' => true,
            'db' => 'pmadb',
            'userconfig' => 'testconf',
            'user' => 'user',
        ])->toArray();

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'UPDATE `pmadb`.`testconf` SET `timevalue` = NOW(), `config_data` = \''
            . json_encode([1]) . '\' WHERE `username` = \'user\'';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(true));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->userPreferences->save([1]);

        self::assertTrue($result);

        // case 3

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'INSERT INTO `pmadb`.`testconf` (`username`, `timevalue`,`config_data`) '
            . 'VALUES (\'user\', NOW(), \'' . json_encode([1]) . '\')';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query1, 0, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($query2, DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->with(DatabaseInterface::CONNECT_CONTROL)
            ->will($this->returnValue('err1'));
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $result = $this->userPreferences->save([1]);

        self::assertInstanceOf(Message::class, $result);
        self::assertSame('Could not save configuration<br><br>err1'
        . '<br><br>The phpMyAdmin configuration storage database could not be accessed.', $result->getMessage());
    }

    /**
     * Test for apply
     */
    public function testApply(): void
    {
        $GLOBALS['cfg']['UserprefsDisallow'] = [
            'test' => 'val',
            'foo' => 'bar',
        ];
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = null;
        $result = $this->userPreferences->apply(
            [
                'DBG/sql' => true,
                'ErrorHandler/display' => true,
                'ErrorHandler/gather' => false,
                'Servers/foobar' => '123',
                'Server/hide_db' => true,
            ]
        );

        self::assertEquals([
            'Server' => ['hide_db' => 1],
        ], $result);
    }

    /**
     * Test for apply
     */
    public function testApplyDevel(): void
    {
        $GLOBALS['cfg']['UserprefsDeveloperTab'] = true;
        $result = $this->userPreferences->apply(
            ['DBG/sql' => true]
        );

        self::assertSame([
            'DBG' => ['sql' => true],
        ], $result);
    }

    /**
     * Test for persistOption
     */
    public function testPersistOption(): void
    {
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        $_SESSION['userconfig'] = [];
        $_SESSION['userconfig']['ts'] = '123';
        $_SESSION['userconfig']['db'] = [
            'Server/hide_db' => true,
            'Server/only_db' => true,
        ];

        $GLOBALS['server'] = 2;
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        self::assertTrue($this->userPreferences->persistOption('Server/hide_db', 'val', 'val'));

        self::assertTrue($this->userPreferences->persistOption('Server/hide_db', 'val2', 'val'));

        self::assertTrue($this->userPreferences->persistOption('Server/hide_db2', 'val', 'val'));
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

        $this->userPreferences->redirect(
            'file.html',
            ['a' => 'b'],
            'h ash'
        );
    }

    /**
     * Test for autoloadGetHeader
     */
    public function testAutoloadGetHeader(): void
    {
        $_SESSION['userprefs_autoload'] = false;
        $_REQUEST['prefs_autoload'] = 'hide';

        self::assertSame('', $this->userPreferences->autoloadGetHeader());

        self::assertTrue($_SESSION['userprefs_autoload']);

        $_REQUEST['prefs_autoload'] = 'nohide';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $result = $this->userPreferences->autoloadGetHeader();

        self::assertStringContainsString(
            '<form action="' . Url::getFromRoute('/preferences/manage') . '" method="post" class="disableAjax">',
            $result
        );

        self::assertStringContainsString('<input type="hidden" name="token" value="token"', $result);

        self::assertStringContainsString('<input type="hidden" name="json" value="">', $result);

        self::assertStringContainsString('<input type="hidden" name="submit_import" value="1">', $result);

        self::assertStringContainsString('<input type="hidden" name="return_url" value="index.php?">', $result);
    }
}
