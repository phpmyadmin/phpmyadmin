<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Charsets;

use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Charsets\Charset
 */
class CharsetTest extends AbstractTestCase
{
    public function testFromServer(): void
    {
        $serverCharset = [
            'Charset' => 'utf8',
            'Default collation' => 'utf8_general_ci',
            'Description' => 'UTF-8 Unicode',
            'Maxlen' => '3',
        ];

        $charset = Charset::fromServer($serverCharset);

        self::assertInstanceOf(Charset::class, $charset);
        self::assertSame('utf8', $charset->getName());
        self::assertSame('utf8_general_ci', $charset->getDefaultCollation());
        self::assertSame('UTF-8 Unicode', $charset->getDescription());
        self::assertSame(3, $charset->getMaxLength());
    }
}
