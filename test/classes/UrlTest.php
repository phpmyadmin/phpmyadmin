<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
class UrlTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
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
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

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
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

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
        $expected = 'server=x' . $separator . 'lang=en' ;

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
        $expected = '?server=x' . htmlentities($separator) . 'lang=en' ;
        $this->assertEquals($expected, Url::getCommon());
    }

    /**
     * @dataProvider providerParseStr
     *
     * @param string      $expected  Expected result
     * @param string      $input     Data to parse
     * @param string|null $separator Separator to use to re-implode the parameters found
     *
     * @return void
     */
    public function testParseStr(string $expected, string $input, string $separator = null): void
    {
        $this->assertEquals($expected, Url::parseStr($input, $separator));
    }

    /**
     * @return array[] Unit test sets
     */
    public function providerParseStr(): array
    {
        return [
            [
                'table=garcon',
                'table=garcon',
                null,
            ],
            [
                'table=garcon',
                'table=garcon',
                '*',
            ],
            [
                'table=garçon',
                'table=gar%C3%A7on',
                null,
            ],
            [
                'table=garçon',
                'table=gar%C3%A7on',
                '*',
            ],
            [
                '?db=gh16222&table=garcon',
                '?db=gh16222&table=garcon',
                null,
            ],
            [
                '?db=gh16222*table=garcon',
                '?db=gh16222&table=garcon',
                '*',
            ],
            [
                '?db=gh16222&table=garçon',
                '?db=gh16222&table=gar%C3%A7on',
                null,
            ],
            [
                '?db=gh16222*table=garçon',
                '?db=gh16222&table=gar%C3%A7on',
                '*',
            ],
        ];
    }

    /**
     * @dataProvider providerBuildQuery
     *
     * @param string      $expected  Expected result
     * @param array       $params    Params to use to build the query
     * @param string|null $separator Separator to use to re-implode the parameters found
     *
     * @return void
     */
    public function testBuildQuery(string $expected, array $params, ?string $separator): void
    {
        $this->assertEquals($expected, Url::buildQuery($params, $separator));
    }

    /**
     * @return array[] Unit test sets
     */
    public function providerBuildQuery()
    {
        return [
            [
                'db=my_db',
                ['db' => 'my_db'],
                null,
            ],
            [
                'db=my_db',
                ['db' => 'my_db'],
                '*',
            ],
            [
                'db=my_db&table=my_table',
                [
                    'db' => 'my_db',
                    'table' => 'my_table',
                ],
                null,
            ],
            [
                'db=my_db*table=my_table',
                [
                    'db' => 'my_db',
                    'table' => 'my_table',
                ],
                '*',
            ],
        ];
    }
}
