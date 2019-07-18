<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Charsets;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
class CharsetsTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * @return void
     */
    public function testFindCollationByName(): void
    {
        $this->assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            null
        ));

        $this->assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            ''
        ));

        $this->assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'invalid'
        ));

        $actual = Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'utf8_general_ci'
        );

        $this->assertInstanceOf(Charsets\Collation::class, $actual);

        $this->assertSame('utf8_general_ci', $actual->getName());
    }
}
