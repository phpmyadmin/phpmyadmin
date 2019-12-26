<?php
/**
 * Class CharsetTest
 * @package PhpMyAdmin\Tests\Charsets
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Charsets;

use PhpMyAdmin\Charsets\Charset;
use PHPUnit\Framework\TestCase;

/**
 * Class CharsetTest
 * @package PhpMyAdmin\Tests\Charsets
 */
class CharsetTest extends TestCase
{
    /**
     * @return void
     */
    public function testFromServer(): void
    {
        $serverCharset = [
            'Charset' => 'utf8',
            'Default collation' => 'utf8_general_ci',
            'Description' => 'UTF-8 Unicode',
            'Maxlen' => '3',
        ];

        $charset = Charset::fromServer($serverCharset);

        $this->assertInstanceOf(Charset::class, $charset);
        $this->assertSame('utf8', $charset->getName());
        $this->assertSame('utf8_general_ci', $charset->getDefaultCollation());
        $this->assertSame('UTF-8 Unicode', $charset->getDescription());
        $this->assertSame(3, $charset->getMaxLength());
    }
}
