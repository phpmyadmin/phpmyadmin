<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Types;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;

use function __;
use function _pgettext;
use function call_user_func_array;
use function htmlspecialchars;
use function urlencode;

/** @covers \PhpMyAdmin\Html\Generator */
class GeneratorTest extends AbstractTestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();
    }

    /**
     * Test for getDbLink
     *
     * @group medium
     */
    public function testGetDbLinkNull(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['server'] = 99;
        $database = $GLOBALS['db'];
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database',
            )
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
        $GLOBALS['server'] = 99;
        $database = 'test_database';
        $this->assertEquals(
            '<a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database',
            )
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
        $GLOBALS['server'] = 99;
        $database = 'test&data\'base';
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database',
            )
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
        $GLOBALS['cfg']['ActionLinksMode'] = 'text';

        $this->assertEquals(
            '<span class="text-nowrap"></span>',
            Generator::getIcon('b_comment'),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconWithActionLinksMode(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $this->assertEquals(
            '<span class="text-nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment"></span>',
            Generator::getIcon('b_comment'),
        );
    }

    /**
     * Test for Util::getIcon
     */
    public function testGetIconAlternate(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternateText = 'alt_str';

        $this->assertEquals(
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
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternateText = 'alt_str';

        // Here we are checking for an icon embedded inside a span (i.e not a menu
        // bar icon
        $this->assertEquals(
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
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;

        $target = 'docu';
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="index.php?route=/url&url=https%3A%2F%2Fwww.php.net%2Fmanual%2F' . $lang
            . '%2F' . $target . '" target="documentation">'
            . '<img src="themes/dot.gif" title="' . __('Documentation') . '" alt="'
            . __('Documentation') . '" class="icon ic_b_help"></a>';

        $this->assertEquals(
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
     *
     * @dataProvider linksOrButtons
     */
    public function testLinkOrButton(array $params, int $limit, string $match): void
    {
        $restore = $GLOBALS['cfg']['LinkLengthLimit'] ?? 1000;
        $GLOBALS['cfg']['LinkLengthLimit'] = $limit;
        try {
            $result = call_user_func_array(
                [Generator::class, 'linkOrButton'],
                $params,
            );
            $this->assertEquals($match, $result);
        } finally {
            $GLOBALS['cfg']['LinkLengthLimit'] = $restore;
        }
    }

    /**
     * Data provider for Generator::linkOrButton test
     *
     * @return mixed[]
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
        ];
    }

    public function testFormatSql(): void
    {
        $this->assertEquals(
            '<code class="sql" dir="ltr"><pre>' . "\n"
            . 'SELECT 1 &lt; 2' . "\n"
            . '</pre></code>',
            Generator::formatSql('SELECT 1 < 2'),
        );

        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 6;

        $this->assertEquals(
            '<code class="sql" dir="ltr"><pre>' . "\n"
            . 'SELECT[...]' . "\n"
            . '</pre></code>',
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

        $GLOBALS['cfg']['Server'] = ['ssl' => false, 'host' => '127.0.0.1'];
        $this->assertEquals(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = ['ssl' => false, 'host' => 'custom.host'];
        $GLOBALS['cfg']['MysqlSslWarningSafeHosts'] = ['localhost', '127.0.0.1'];

        $this->assertEquals(
            $sslNotUsedCaution,
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = ['ssl' => false, 'host' => 'custom.host'];
        $GLOBALS['cfg']['MysqlSslWarningSafeHosts'] = ['localhost', '127.0.0.1', 'custom.host'];

        $this->assertEquals(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = ['ssl' => false, 'ssl_verify' => true, 'host' => 'custom.host'];

        $this->assertEquals(
            $sslNotUsed,
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = ['ssl' => true, 'ssl_verify' => false, 'host' => 'custom.host'];

        $this->assertEquals(
            '<span class="text-danger">SSL is used with disabled verification</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = ['ssl' => true, 'ssl_verify' => true, 'host' => 'custom.host'];

        $this->assertEquals(
            '<span class="text-danger">SSL is used without certification authority</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );

        $GLOBALS['cfg']['Server'] = [
            'ssl' => true,
            'ssl_verify' => true,
            'ssl_ca' => '/etc/ssl/ca.crt',
            'host' => 'custom.host',
        ];

        $this->assertEquals(
            '<span class="">SSL is used</span>'
            . ' <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23ssl"'
            . ' target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation"'
            . ' class="icon ic_b_help"></a>',
            Generator::getServerSSL(),
        );
    }

    /**
     * Test for Generator::getDefaultFunctionForField
     *
     * @param array  $field      field settings
     * @param bool   $insertMode true if insert mode
     * @param string $expected   expected result
     * @psalm-param array<string, string|bool|null> $field
     *
     * @dataProvider providerForTestGetDefaultFunctionForField
     */
    public function testGetDefaultFunctionForField(
        array $field,
        bool $insertMode,
        string $expected,
    ): void {
        $dbiStub = $this->createStub(DatabaseInterface::class);
        $dbiStub->types = new Types($dbiStub);
        $dbiStub->method('getVersion')->willReturn(50700);

        $GLOBALS['dbi'] = $dbiStub;

        $result = Generator::getDefaultFunctionForField($field, $insertMode);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for Generator::getDefaultFunctionForField test
     *
     * @return mixed[]
     * @psalm-return array<int, array{array<string, string|bool|null>, bool, string}>
     */
    public static function providerForTestGetDefaultFunctionForField(): array
    {
        return [
            [
                [
                    'True_Type' => 'GEOMETRY',
                    'first_timestamp' => false,
                    'Extra' => null,
                    'Key' => '',
                    'Type' => '',
                    'Null' => 'NO',
                ],
                true,
                'ST_GeomFromText',
            ],
            [
                [
                    'True_Type' => 'timestamp',
                    'first_timestamp' => true,
                    'Extra' => null,
                    'Key' => '',
                    'Type' => '',
                    'Null' => 'NO',
                ],
                true,
                'NOW',
            ],
            [['True_Type' => 'uuid', 'first_timestamp' => false, 'Key' => '', 'Type' => ''], true, ''],
            [['True_Type' => '', 'first_timestamp' => false, 'Key' => 'PRI', 'Type' => 'char(36)'], true, 'UUID'],
        ];
    }

    public function testGetMessage(): void
    {
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['display_query'] = null;
        $GLOBALS['unparsed_sql'] = null;
        $GLOBALS['sql_query'] = 'SELECT 1;';
        $usingBookmarkMessage = Message::notice('Bookmark message');
        $GLOBALS['using_bookmark_message'] = $usingBookmarkMessage;
        $GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['server'] = 2;
        $GLOBALS['special_message'] = 'Message [em]two[/em].';
        SessionCache::set('profiling_supported', true);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Bookmark message
</div>
<div class="alert alert-primary" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Message <em>one</em>. Message <em>two</em>.
</div>
<div class="card result_query">
<div class="card-body sqlOuter"><code class="sql" dir="ltr"><pre>
SELECT 1;
</pre></code></div>
<div class="card-footer tools d-print-none">
<div class="row align-items-center">
<div class="col-auto">
<form action="index.php?route=/sql&server=2&lang=en" method="post">
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

        $this->assertSame($expected, Generator::getMessage('Message [em]one[/em].'));
        $this->assertArrayNotHasKey('using_bookmark_message', $GLOBALS);
        $this->assertArrayNotHasKey('special_message', $GLOBALS);
        SessionCache::remove('profiling_supported');
    }

    public function testGetMessage2(): void
    {
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['SQLQuery']['Edit'] = false;
        $GLOBALS['cfg']['SQLQuery']['Refresh'] = true;
        $GLOBALS['display_query'] = 'EXPLAIN SELECT 1;';
        $GLOBALS['unparsed_sql'] = null;
        $GLOBALS['sql_query'] = null;
        $GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['server'] = 2;
        $GLOBALS['show_as_php'] = true;
        $GLOBALS['special_message'] = 'Message [em]two[/em].';
        SessionCache::set('profiling_supported', true);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="alert alert-success" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_success"> Message <em>one</em>. Message <em>two</em>.
</div>
<div class="card result_query">
<div class="card-body sqlOuter"><code class="php" dir="ltr"><pre>
$sql = "EXPLAIN SELECT 1;";
</pre></code></div>
<div class="card-footer tools d-print-none">
<div class="row align-items-center">
<div class="col-auto">
<form action="index.php?route=/sql&server=2&lang=en" method="post">
<input type="hidden" name="db" value="test_db"><input type="hidden" name="table" value="test_table"><input type="hidden" name="server" value="2"><input type="hidden" name="lang" value="en"><input type="hidden" name="token" value="token">
<input type="hidden" name="sql_query" value="EXPLAIN SELECT 1;">
</form></div>
<div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=SELECT+1%3B&server=2&lang=en" class="btn btn-link">Skip Explain SQL</a></div>
<div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Without PHP code</a></div>
<div class="col-auto"><a href="index.php" data-post="route=/import&db=test_db&table=test_table&sql_query=EXPLAIN+SELECT+1%3B&show_query=1&server=2&lang=en" class="btn btn-link">Submit query</a></div>
</div></div></div>
HTML;
        // phpcs:enable

        $this->assertSame($expected, Generator::getMessage(Message::success('Message [em]one[/em].')));
        $this->assertArrayNotHasKey('special_message', $GLOBALS);
        SessionCache::remove('profiling_supported');
    }
}
