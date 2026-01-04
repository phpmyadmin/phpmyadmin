<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;
use PhpMyAdmin\Url;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;

use function __;
use function _pgettext;
use function htmlspecialchars;
use function urlencode;

#[CoversClass(Generator::class)]
#[Medium]
class GeneratorTest extends AbstractTestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();
    }

    /**
     * Test for getDbLink
     */
    public function testGetDbLinkNull(): void
    {
        Current::$database = 'test_db';
        Current::$server = 99;
        $database = Current::$database;
        self::assertSame(
            '<a href="'
            . Url::getFromRoute(Config::getInstance()->config->DefaultTabDatabase)
            . '&db=' . $database
            . '&server=99&lang=en" '
            . 'title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink(''),
        );
    }

    /**
     * Test for getDbLink
     */
    public function testGetDbLink(): void
    {
        Current::$server = 99;
        $database = 'test_database';
        self::assertSame(
            '<a href="' . Url::getFromRoute(Config::getInstance()->config->DefaultTabDatabase)
            . '&db=' . $database
            . '&server=99&lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink($database),
        );
    }

    /**
     * Test for getDbLink
     */
    public function testGetDbLinkWithSpecialChars(): void
    {
        Current::$server = 99;
        $database = 'test&data\'base';
        self::assertSame(
            '<a href="'
            . Url::getFromRoute(Config::getInstance()->config->DefaultTabDatabase)
            . '&db='
            . htmlspecialchars(urlencode($database))
            . '&server=99&lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink($database),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconWithoutActionLinksMode(): void
    {
        Config::getInstance()->settings['ActionLinksMode'] = 'text';

        self::assertSame(
            '<span class="text-nowrap"></span>',
            Generator::getIcon('b_comment'),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconWithActionLinksMode(): void
    {
        Config::getInstance()->settings['ActionLinksMode'] = 'icons';

        self::assertSame(
            '<span class="text-nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment"></span>',
            Generator::getIcon('b_comment'),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconAlternate(): void
    {
        Config::getInstance()->settings['ActionLinksMode'] = 'icons';
        $alternateText = 'alt_str';

        self::assertSame(
            '<span class="text-nowrap"><img src="themes/dot.gif" title="'
            . $alternateText . '" alt="' . $alternateText
            . '" class="icon ic_b_comment"></span>',
            Generator::getIcon('b_comment', $alternateText),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconWithForceText(): void
    {
        Config::getInstance()->settings['ActionLinksMode'] = 'icons';
        $alternateText = 'alt_str';

        // Here we are checking for an icon embedded inside a span (i.e not a menu
        // bar icon
        self::assertSame(
            '<span class="text-nowrap"><img src="themes/dot.gif" title="'
            . $alternateText . '" alt="' . $alternateText
            . '" class="icon ic_b_comment">&nbsp;' . $alternateText . '</span>',
            Generator::getIcon('b_comment', $alternateText, true, false),
        );
    }

    /**
     * Test for showPHPDocumentation
     */
    public function testShowPHPDocumentation(): void
    {
        $target = 'docu';
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="index.php?route=/url&url=https%3A%2F%2Fwww.php.net%2Fmanual%2F' . $lang
            . '%2F' . $target . '" target="documentation">'
            . '<img src="themes/dot.gif" title="' . __('Documentation') . '" alt="'
            . __('Documentation') . '" class="icon ic_b_help"></a>';

        self::assertSame(
            $expected,
            Generator::showPHPDocumentation($target),
        );
    }

    /**
     * Test for Generator::linkOrButton
     *
     * @param mixed[] $params params
     * @param int     $limit  limit
     * @param string  $match  match
     */
    #[DataProvider('linksOrButtons')]
    public function testLinkOrButton(array $params, int $limit, string $match): void
    {
        $config = Config::getInstance();
        $restore = $config->config->LinkLengthLimit;
        $config->set('LinkLengthLimit', $limit);
        try {
            $result = Generator::linkOrButton(...$params);
            self::assertSame($match, $result);
        } finally {
            $config->set('LinkLengthLimit', $restore);
        }
    }

    /**
     * Data provider for Generator::linkOrButton test
     *
     * @return array<int, array{array<string, string>[]|string[]|null[]|bool[], int, string}>
     */
    public static function linksOrButtons(): array
    {
        return [
            [['index.php', null, 'text'], 1000, '<a href="index.php" >text</a>'],
            [
                ['index.php', ['some' => 'parameter'], 'text'],
                20,
                '<a href="index.php" data-post="some=parameter&lang=en">text</a>',
            ],
            [['index.php', null, 'text', [], 'target'], 1000, '<a href="index.php" target="target">text</a>'],
            [
                [
                    'https://mariadb.org/explain_analyzer/analyze/?client=phpMyAdmin&amp;raw_explain=%2B---%2B',
                    null,
                    'text',
                    [],
                    'target',
                ],
                10,
                // This is not the behavior we want for the analyser feature, next test will disable the limit
                '<a href="https://mariadb.org/explain_analyzer/analyze/"'
                . ' data-post="client=phpMyAdmin&amp;raw_explain=%2B---%2B" target="target">text</a>',
            ],
            [
                [
                    'https://mariadb.org/explain_analyzer/analyze/?client=phpMyAdmin&amp;raw_explain=%2B---%2B',
                    null,
                    'text',
                    [],
                    'target',
                    false,
                ],
                10,
                '<a href="https://mariadb.org/explain_analyzer/analyze/?client=phpMyAdmin&amp;raw_explain=%2B---%2B"'
                . ' target="target">text</a>',
            ],
            [
                ['index.php?route=/url&url=http://phpmyadmin.net/', null, 'text', [], '_blank'],
                1000,
                '<a href="index.php?route=/url&url=http://phpmyadmin.net/" target="_blank"'
                . ' rel="noopener noreferrer">text</a>',
            ],
            [
                ['index.php?route=/server/databases', ['some' => 'parameter'], 'text'],
                20,
                '<a href="index.php" data-post="route=/server/databases&some=parameter&lang=en">text</a>',
            ],
            [
                ['index.php?route=/server/databases', null, 'text'],
                20,
                '<a href="index.php" data-post="route=/server/databases">text</a>',
            ],
            [
                ['index.php?route=/server/databases', ['some' => 'parameter'], 'text'],
                100,
                '<a href="index.php?route=/server/databases&some=parameter&lang=en" >text</a>',
            ],
            [
                ['index.php?route=/server/databases', null, 'text'],
                100,
                '<a href="index.php?route=/server/databases" >text</a>',
            ],
            [
                [
                    'index.php',
                    null,
                    'text',
                    ['title' => '"'],
                ],
                100,
                '<a href="index.php" title="&quot;">text</a>',
            ],
        ];
    }

    public function testFormatSql(): void
    {
        self::assertSame(
            '<pre><code class="sql" dir="ltr">'
            . 'SELECT 1 &lt; 2'
            . '</code></pre>',
            Generator::formatSql('SELECT 1 < 2'),
        );

        Config::getInstance()->settings['MaxCharactersInDisplayedSQL'] = 6;

        self::assertSame(
            '<pre><code class="sql" dir="ltr">'
            . 'SELECT[...]'
            . '</code></pre>',
            Generator::formatSql('SELECT 1 < 2', true),
        );
    }

    /**
     * Test for getServerSSL
     */
    public function testGetServerSSL(): void
    {
        $sslNotUsed = '<span class="">SSL is not being used</span>'
        . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
        . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
        . ' class="icon ic_b_help"></a>';

        $sslNotUsedCaution = '<span class="text-danger">SSL is not being used</span>'
        . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
        . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
        . ' class="icon ic_b_help"></a>';

        $config = Config::getInstance();
        $config->selectedServer = ['ssl' => false, 'host' => '127.0.0.1'];
        self::assertSame(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $config->selectedServer = ['ssl' => false, 'host' => 'custom.host'];
        $config->settings['MysqlSslWarningSafeHosts'] = ['localhost', '127.0.0.1'];

        self::assertSame(
            $sslNotUsedCaution,
            Generator::getServerSSL(),
        );

        $config->selectedServer = ['ssl' => false, 'host' => 'custom.host'];
        $config->settings['MysqlSslWarningSafeHosts'] = ['localhost', '127.0.0.1', 'custom.host'];

        self::assertSame(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $config->selectedServer = ['ssl' => false, 'ssl_verify' => true, 'host' => 'custom.host'];

        self::assertSame(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $config->selectedServer = ['ssl' => true, 'ssl_verify' => false, 'host' => 'custom.host'];

        self::assertSame(
            '<span class="text-danger">SSL is used with disabled verification</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );

        $config->selectedServer = ['ssl' => true, 'ssl_verify' => true, 'host' => 'custom.host'];

        self::assertSame(
            '<span class="text-danger">SSL is used without certification authority</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );

        $config->selectedServer = [
            'ssl' => true,
            'ssl_verify' => true,
            'ssl_ca' => '/etc/ssl/ca.crt',
            'host' => 'custom.host',
        ];

        self::assertSame(
            '<span class="">SSL is used</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );
    }

    /**
     * Test for Generator::getDefaultFunctionForField
     */
    #[DataProvider('providerForTestGetDefaultFunctionForField')]
    public function testGetDefaultFunctionForField(
        string $trueType,
        bool $firstTimestamp,
        string|null $defaultValue,
        string $extra,
        bool $isNull,
        string $key,
        string $type,
        bool $insertMode,
        string $expected,
    ): void {
        $dbiStub = self::createStub(DatabaseInterface::class);
        $dbiStub->types = new Types($dbiStub);
        $dbiStub->method('getVersion')->willReturn(50700);

        DatabaseInterface::$instance = $dbiStub;

        $result = Generator::getDefaultFunctionForField(
            $trueType,
            $firstTimestamp,
            $defaultValue,
            $extra,
            $isNull,
            $key,
            $type,
            $insertMode,
        );

        self::assertSame($expected, $result);
    }

    /**
     * Data provider for Generator::getDefaultFunctionForField test
     *
     * @return array{string, bool, string|null, string, bool, string, string, bool, string}[]
     */
    public static function providerForTestGetDefaultFunctionForField(): array
    {
        return [
            [
                'GEOMETRY',
                false,
                null,
                '',
                false,
                '',
                '',
                true,
                'ST_GeomFromText',
            ],
            [
                'timestamp',
                true,
                null,
                '',
                false,
                '',
                '',
                true,
                'NOW',
            ],
            [
                'uuid',
                false,
                null,
                '',
                false,
                '',
                '',
                true,
                '',
            ],
            [
                '',
                false,
                null,
                '',
                false,
                'PRI',
                'char(36)',
                true,
                'UUID',
            ],
        ];
    }

    public function testGetMessage(): void
    {
        Config::getInstance()->settings['ShowSQL'] = true;
        Current::$displayQuery = null;
        Current::$sqlQuery = 'SELECT 1;';
        Sql::$usingBookmarkMessage = Message::notice('Bookmark message');
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$server = 2;
        Sql::$showAsPhp = null;
        SessionCache::set('profiling_supported', true);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="alert alert-primary" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Bookmark message
            </div>
            <div class="card mb-3 result_query">
            <div class="alert alert-primary border-top-0 border-start-0 border-end-0 rounded-bottom-0 mb-0" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Message <em>one</em>.
            </div>
            <div class="card-body sqlOuter"><pre><code class="sql" dir="ltr">SELECT 1;</code></pre></div>
            <div class="card-footer tools d-print-none">
            <div class="row align-items-center">
            <div class="col-auto">
            <form action="index.php?route=/sql&db=test_db&table=test_table&server=2&lang=en" method="post" class="disableAjax">
            <input type="hidden" name="db" value="test_db"><input type="hidden" name="table" value="test_table"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">
            <input type="hidden" name="sql_query" value="SELECT 1;">
            <input type="hidden" name="profiling_form" value="1">
            <div class="form-check form-switch">
            <input type="checkbox" name="profiling" id="profilingCheckbox" role="switch" class="form-check-input autosubmit">
            <label class="form-check-label" for="profilingCheckbox">Profiling</label>
            </div>
            </form></div>
            <div class="col-auto"><a href="#" class="btn btn-link inline_edit_sql">Edit inline</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/table/sql&db=test_db&table=test_table&sql_query=SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Edit</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1%3B&server=2&lang=en" class="btn btn-link">Explain SQL</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=SELECT+1%3B&show_query=1&show_as_php=1&server=2&lang=en" class="btn btn-link">Create PHP code</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/sql&db=test_db&table=test_table&sql_query=SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Refresh</a></div>
            </div></div></div>
            HTML;
        // phpcs:enable

        self::assertSame($expected, Generator::getMessage('Message [em]one[/em].'));
        SessionCache::remove('profiling_supported');
    }

    public function testGetMessage2(): void
    {
        $config = Config::getInstance();
        $config->settings['ShowSQL'] = true;
        $config->settings['SQLQuery']['Edit'] = false;
        $config->settings['SQLQuery']['Refresh'] = true;
        Current::$displayQuery = 'EXPLAIN SELECT 1;';
        Current::$sqlQuery = '';
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$server = 2;
        Sql::$showAsPhp = true;
        SessionCache::set('profiling_supported', true);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="card mb-3 result_query">
            <div class="alert alert-success border-top-0 border-start-0 border-end-0 rounded-bottom-0 mb-0" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_success"> Message <em>one</em>.
            </div>
            <div class="card-body sqlOuter"><pre><code class="php" dir="ltr">$sql = "EXPLAIN SELECT 1;";</code></pre></div>
            <div class="card-footer tools d-print-none">
            <div class="row align-items-center">
            <div class="col-auto">
            <form action="index.php?route=/sql&db=test_db&table=test_table&server=2&lang=en" method="post" class="disableAjax">
            <input type="hidden" name="db" value="test_db"><input type="hidden" name="table" value="test_table"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">
            <input type="hidden" name="sql_query" value="EXPLAIN SELECT 1;">
            </form></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=SELECT+1%3B&server=2&lang=en" class="btn btn-link">Skip Explain SQL</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Without PHP code</a></div>
            <div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Submit query</a></div>
            </div></div></div>
            HTML;
        // phpcs:enable

        self::assertSame($expected, Generator::getMessage(Message::success('Message [em]one[/em].')));
        SessionCache::remove('profiling_supported');
    }
}
