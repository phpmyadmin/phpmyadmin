<?php
/**
 * Tests for MySQL Charsets
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Charsets;

/**
 * Tests for MySQL Charsets
 */
class CharsetsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

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
