<?php
/**
 * Tests for methods in URL class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Url;
use function htmlentities;

/**
 * Tests for methods in URL class
 */
class UrlTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        unset($_COOKIE['pma_lang']);
    }

    /**
     * Test for Url::getCommon for DB only
     *
     * @return void
     */
    public function testDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en';

        $expected = '?db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, Url::getCommon(['db' => 'db']));
    }

    /**
     * Test for Url::getCommon with new style
     *
     * @return void
     */
    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en';

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = [
            'db' => 'db',
            'table' => 'table',
        ];
        $this->assertEquals($expected, Url::getCommon($params));
    }

    /**
     * Test for Url::getCommon with alternate divider
     *
     * @return void
     */
    public function testWithAlternateDivider()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en';

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        $this->assertEquals(
            $expected,
            Url::getCommonRaw(
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
                '#ABC#'
            )
        );
    }

    /**
     * Test for Url::getCommon
     *
     * @return void
     */
    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = '?server=x' . htmlentities($separator) . 'lang=en';
        $this->assertEquals($expected, Url::getCommon());
    }

    /**
     * Test for Url::getFromRoute
     *
     * @return void
     */
    public function testGetFromRoute(): void
    {
        unset($GLOBALS['server']);
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '%3\$s',
            'table' => '%2\$s',
            'field' => '%1\$s',
            'change_column' => 1,
        ]);
        $this->assertEquals(
            'index.php?route=/test&amp;db=%253%5C%24s&amp;table=%252%'
            . '5C%24s&amp;field=%251%5C%24s&amp;change_column=1&amp;lang=en',
            $generatedUrl
        );
    }
}
