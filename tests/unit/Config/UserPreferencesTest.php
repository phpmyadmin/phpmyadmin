<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Clock\MockClock;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function json_encode;
use function str_replace;

#[CoversClass(UserPreferences::class)]
class UserPreferencesTest extends AbstractTestCase
{
    /**
     * Setup various pre conditions
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$server = 2;
        $_SERVER['PHP_SELF'] = '/phpmyadmin/';
    }

    /**
     * Test for pageInit
     */
    public function testPageInit(): void
    {
        $config = Config::getInstance();
        $config->settings = ['Server/hide_db' => 'testval123', 'Server/port' => '213'];

        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $userPreferences->pageInit(new ConfigFile());

        self::assertSame(
            ['Servers' => [1 => ['hide_db' => 'testval123']]],
            $_SESSION['ConfigFile' . Current::$server],
        );
    }

    /**
     * Test for load
     */
    public function testLoad(): void
    {
        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        unset($_SESSION['userconfig']);

        $config = Config::getInstance();
        $dbi1 = DatabaseInterface::getInstance();
        $clock = MockClock::from('2015-10-21T05:28:00-02:00');
        $userPreferences = new UserPreferences(
            $dbi1,
            new Relation($dbi1, $config),
            new Template($config),
            $config,
            $clock,
        );
        $result = $userPreferences->load();

        self::assertSame(
            [],
            $result['config_data'],
        );
        self::assertSame(1445412480, $result['mtime']);

        self::assertSame('session', $result['type']);

        // case 2
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER => 'user',
            RelationParameters::DATABASE => "pma'db",
            RelationParameters::USER_CONFIG => 'testconf',
            RelationParameters::USER_CONFIG_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts '
            . 'FROM `pma\'db`.`testconf` WHERE `username` = \'user\'';

        $dbi->expects(self::once())
            ->method('fetchSingleRow')
            ->with($query, DatabaseInterface::FETCH_ASSOC, ConnectionType::ControlUser)
            ->willReturn(['ts' => '123', 'config_data' => json_encode([1, 2])]);
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $result = $userPreferences->load();

        self::assertSame(
            ['config_data' => [1, 2], 'mtime' => 123, 'type' => 'db'],
            $result,
        );
    }

    /**
     * Test for save
     */
    public function testSave(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        unset($_SESSION['userconfig']);

        $dbi1 = DatabaseInterface::getInstance();
        $clock = MockClock::from('2015-10-21T05:28:00-02:00');
        $userPreferences = new UserPreferences(
            $dbi1,
            new Relation($dbi1, $config),
            new Template($config),
            $config,
            $clock,
        );
        $result = $userPreferences->save([1]);

        self::assertTrue($result);

        self::assertCount(2, $_SESSION['userconfig']);

        self::assertSame(
            [1],
            $_SESSION['userconfig']['db'],
        );
        self::assertSame(1445412480, $_SESSION['userconfig']['ts']);

        $assert = true;

        if (isset($_SESSION['cache']['server_2']['userprefs'])) {
            $assert = false;
        }

        self::assertTrue($assert);

        // case 2
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::USER_CONFIG => 'testconf',
            RelationParameters::USER => 'user',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'UPDATE `pmadb`.`testconf` SET `timevalue` = NOW(), `config_data` = \''
            . json_encode([1]) . '\' WHERE `username` = \'user\'';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with($query1, 0, ConnectionType::ControlUser)
            ->willReturn('1');

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with($query2, ConnectionType::ControlUser)
            ->willReturn(self::createStub(DummyResult::class));

        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi1, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $result = $userPreferences->save([1]);

        self::assertTrue($result);

        // case 3

        $query1 = 'SELECT `username` FROM `pmadb`.`testconf` WHERE `username` = \'user\'';

        $query2 = 'INSERT INTO `pmadb`.`testconf` (`username`, `timevalue`,`config_data`) '
            . 'VALUES (\'user\', NOW(), \'' . json_encode([1]) . '\')';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('fetchValue')
            ->with($query1, 0, ConnectionType::ControlUser)
            ->willReturn(false);

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with($query2, ConnectionType::ControlUser)
            ->willReturn(false);

        $dbi->expects(self::once())
            ->method('getError')
            ->with(ConnectionType::ControlUser)
            ->willReturn('err1');
        $dbi->expects(self::any())
            ->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi1, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $result = $userPreferences->save([1]);

        self::assertInstanceOf(Message::class, $result);
        self::assertSame(
            'Could not save configuration<br><br>err1'
            . '<br><br>The phpMyAdmin configuration storage database could not be accessed.',
            $result->getMessage(),
        );
    }

    /**
     * Test for save and keep 2FA settings
     */
    public function testSaveAndKeep2FA(): void
    {
        $initialConfig = [
            'CharEditing' => 'textarea',
            '2fa' => ['backend' => 'application', 'settings' => ['secret' => 'thisisasecret']],
            'RowActionLinks' => 'both',
            'TableNavigationLinksMode' => 'both',
        ];
        $dummyDbi = $this->createDbiDummy();

        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('pma-db');
        $dummyDbi->addResult(
            'SHOW TABLES FROM `pma-db`;',
            [['pma__userconfig']],
            ['Tables_in_pma-db'],
        );

        $dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [['NULL']],
        );

        $dbi = $this->createDatabaseInterface($dummyDbi);
        $config = new Config();
        $config->selectedServer['pmadb'] = 'pma-db';
        $config->selectedServer['userconfig'] = 'pma__userconfig';
        $relation = new Relation($dbi, $config);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $relationParameters = $relation->getRelationParameters();
        self::assertNotNull($relationParameters->userPreferencesFeature);

        $userPreferences = new UserPreferences($dbi, $relation, new Template($config), $config, new Clock());

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [],
        );

        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `username` FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [],
        );

        $dummyDbi->addResult(
            <<<'SQL'
            INSERT INTO `pma-db`.`pma__userconfig` (`username`, `timevalue`,`config_data`) VALUES ('root', NOW(), '[1]')
            SQL,
            [],
        );

        $result = $userPreferences->save([1]);

        self::assertTrue($result);

        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `username` FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [],
        );

        $encodedConfig = json_encode($initialConfig);
        $encodedEscapedConfig = str_replace('"', '\"', $encodedConfig);

        $dummyDbi->addResult(
            <<<SQL
            INSERT INTO `pma-db`.`pma__userconfig` (`username`, `timevalue`,`config_data`) VALUES ('root', NOW(), '$encodedEscapedConfig')
            SQL,
            [],
        );

        // Test 2fa preservation on partial save

        // Initial save with 2fa
        $userPreferences->save($initialConfig);

        // Not using the session storage
        self::assertFalse(isset($_SESSION['userconfig']));

        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [[$encodedConfig, 1767029179]],
            ['config_data', 'ts'],
        );

        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `username` FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [['root']],
            ['username'],
        );

        // 2FA is combined with the previous config
        $dummyDbi->addResult(
            <<<'SQL'
            UPDATE `pma-db`.`pma__userconfig` SET `timevalue` = NOW(), `config_data` = '{\"CharEditing\":\"textarea\",\"TableNavigationLinksMode\":\"text\",\"Console\\/Mode\":\"collapse\",\"2fa\":{\"backend\":\"application\",\"settings\":{\"secret\":\"thisisasecret\"}}}' WHERE `username` = 'root'
            SQL,
            [['root']],
            ['username'],
        );

        // Partial save without 2fa
        $partialConfig = [
            'CharEditing' => 'textarea',
            'TableNavigationLinksMode' => 'text',
            'Console/Mode' => 'collapse',
        ];
        $userPreferences->save($partialConfig);

        $expected = [
            'CharEditing' => 'textarea',
            'TableNavigationLinksMode' => 'text',
            'Console/Mode' => 'collapse',
            '2fa' => ['backend' => 'application', 'settings' => ['secret' => 'thisisasecret']],
        ];
        $encodedConfig = json_encode($expected);

        $dummyDbi->addResult(
            <<<'SQL'
            SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts FROM `pma-db`.`pma__userconfig` WHERE `username` = 'root'
            SQL,
            [[$encodedConfig, 1767029179]],
            ['config_data', 'ts'],
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Check that 2fa is still present
        $resultConfig = $userPreferences->load()['config_data'];
        self::assertSame($expected, $resultConfig);

        $dummyDbi->assertAllSelectsConsumed();
    }

    public function testSaveAndKeep2FAWithSession(): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $relation = new Relation($dbi, $config);
        $clock = MockClock::from('2015-10-21T05:28:00-02:00');
        $userPreferences = new UserPreferences($dbi, $relation, new Template($config), $config, $clock);

        unset($_SESSION['userconfig']);
        $initialConfig = [
            'CharEditing' => 'textarea',
            '2fa' => ['backend' => 'application', 'settings' => ['secret' => 'thisisasecret']],
            'RowActionLinks' => 'both',
            'TableNavigationLinksMode' => 'both',
        ];
        $userPreferences->save($initialConfig);

        /** @phpstan-ignore offsetAccess.notFound */
        self::assertSame(['db' => $initialConfig, 'ts' => 1445412480], $_SESSION['userconfig']);

        $partialConfig = [
            'CharEditing' => 'textarea',
            'TableNavigationLinksMode' => 'text',
            'Console/Mode' => 'collapse',
        ];
        $userPreferences->save($partialConfig);

        $expected = [
            'db' => [
                'CharEditing' => 'textarea',
                'TableNavigationLinksMode' => 'text',
                'Console/Mode' => 'collapse',
                '2fa' => ['backend' => 'application', 'settings' => ['secret' => 'thisisasecret']],
            ],
            'ts' => 1445412480,
        ];
        /** @psalm-suppress DocblockTypeContradiction */
        self::assertSame($expected, $_SESSION['userconfig']);
    }

    /**
     * Test for apply
     */
    public function testApply(): void
    {
        $config = Config::getInstance();
        $config->settings['UserprefsDisallow'] = ['test' => 'val', 'foo' => 'bar'];

        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $result = $userPreferences->apply(
            [
                'DBG/sql' => true,
                'ErrorHandler/display' => true,
                'ErrorHandler/gather' => false,
                'Servers/foobar' => '123',
                'Server/hide_db' => true,
            ],
        );

        self::assertEquals(
            ['Server' => ['hide_db' => 1]],
            $result,
        );
    }

    /**
     * Test for apply
     */
    public function testApplyDevel(): void
    {
        $config = Config::getInstance();
        $config->set('UserprefsDeveloperTab', true);

        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $result = $userPreferences->apply(
            ['DBG/sql' => true],
        );

        self::assertSame(
            ['DBG' => ['sql' => true]],
            $result,
        );
    }

    /**
     * Test for persistOption
     */
    public function testPersistOption(): void
    {
        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $_SESSION['userconfig'] = [];
        $_SESSION['userconfig']['ts'] = '123';
        $_SESSION['userconfig']['db'] = ['Server/hide_db' => true, 'Server/only_db' => true];

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $config = Config::getInstance();
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        self::assertTrue(
            $userPreferences->persistOption('Server/hide_db', 'val', 'val'),
        );

        self::assertTrue(
            $userPreferences->persistOption('Server/hide_db', 'val2', 'val'),
        );

        self::assertTrue(
            $userPreferences->persistOption('Server/hide_db2', 'val', 'val'),
        );
    }

    #[BackupStaticProperties(true)]
    public function testRedirect(): void
    {
        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, $responseStub);

        Current::$lang = '';
        Current::$database = 'db';
        Current::$table = 'table';

        $config = Config::getInstance();
        $config->set('PmaAbsoluteUri', '');

        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        $response = $userPreferences->redirect(
            'file.html',
            ['a' => 'b'],
            'h ash',
        );

        self::assertSame(['/phpmyadmin/file.html?a=b&saved=1&server=2#h+ash'], $response->getHeader('Location'));
        self::assertSame(302, $response->getStatusCode());
    }

    /**
     * Test for autoloadGetHeader
     */
    public function testAutoloadGetHeader(): void
    {
        $_SESSION['userprefs_autoload'] = false;
        $_REQUEST['prefs_autoload'] = 'hide';

        $config = Config::getInstance();
        $dbi = DatabaseInterface::getInstance();
        $userPreferences = new UserPreferences(
            $dbi,
            new Relation($dbi, $config),
            new Template($config),
            $config,
            new Clock(),
        );
        self::assertSame(
            '',
            $userPreferences->autoloadGetHeader(),
        );

        self::assertTrue($_SESSION['userprefs_autoload']);

        $_REQUEST['prefs_autoload'] = 'nohide';
        $result = $userPreferences->autoloadGetHeader();

        self::assertStringContainsString(
            '<form action="' . Url::getFromRoute('/preferences/manage') . '" method="post" class="disableAjax">',
            $result,
        );

        self::assertStringContainsString('<input type="hidden" name="token" value="token"', $result);

        self::assertStringContainsString('<input type="hidden" name="json" value="">', $result);

        self::assertStringContainsString('<input type="hidden" name="submit_import" value="1">', $result);

        self::assertStringContainsString('<input type="hidden" name="return_url" value="?">', $result);
    }
}
