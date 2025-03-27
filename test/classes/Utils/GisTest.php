<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Utils\Gis;

use function hex2bin;

/**
 * @covers \PhpMyAdmin\Utils\Gis
 */
class GisTest extends AbstractTestCase
{
    /**
     * @param string $expectedQuery The query to expect
     * @param array  $returnData    The data to return for fetchRow
     * @param bool   $SRIDOption    Use the SRID option or not
     * @param int    $mysqlVersion  The mysql version to return for getVersion
     *
     * @dataProvider providerConvertToWellKnownText
     */
    public function testConvertToWellKnownText(
        string $expectedQuery,
        array $returnData,
        string $expectedResult,
        bool $SRIDOption,
        int $mysqlVersion
    ): void {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('getVersion')
            ->will($this->returnValue($mysqlVersion));

        $dbi->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('tryQuery')
            ->with($expectedQuery)
            ->will($this->returnValue($resultStub));// Omit the real object

        $resultStub->expects($SRIDOption ? $this->once() : $this->exactly(2))
            ->method('fetchRow')
            ->will($this->returnValue($returnData));

        $GLOBALS['dbi'] = $dbi;

        if (! $SRIDOption) {
            // Also test default signature
            self::assertSame($expectedResult, Gis::convertToWellKnownText(
                (string) hex2bin('000000000101000000000000000000F03F000000000000F03F')
            ));
        }

        self::assertSame($expectedResult, Gis::convertToWellKnownText(
            (string) hex2bin('000000000101000000000000000000F03F000000000000F03F'),
            $SRIDOption
        ));
    }

    public static function providerConvertToWellKnownText(): array
    {
        return [
            [
                'SELECT ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)'],
                'POINT(1 1)',
                false,
                50300,
            ],
            [
                'SELECT ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)', '0'],
                '\'POINT(1 1)\',0',
                true,
                50300,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)'],
                'POINT(1 1)',
                false,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)', '0'],
                '\'POINT(1 1)\',0',
                true,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\', \'axis-order=long-lat\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)', '0'],
                '\'POINT(1 1)\',0',
                true,
                80001,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\'),'
                . ' ST_SRID(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)', '0'],
                '\'POINT(1 1)\',0',
                true,
                50700,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\', \'axis-order=long-lat\')',
                ['POINT(1 1)', '0'],
                'POINT(1 1)',
                false,
                80001,
            ],
            [
                'SELECT ST_ASTEXT(x\'000000000101000000000000000000f03f000000000000f03f\')',
                ['POINT(1 1)', '0'],
                'POINT(1 1)',
                false,
                50700,
            ],
        ];
    }

    public function testCreateDataOldMysql(): void
    {
        self::assertEquals('abc', Gis::createData('abc', 50500));
        self::assertEquals('GeomFromText(\'POINT()\',10)', Gis::createData('\'POINT()\',10', 50500));
    }

    public function testCreateDataNewMysql(): void
    {
        self::assertEquals('abc', Gis::createData('abc', 50600));
        self::assertEquals('ST_GeomFromText(\'POINT()\',10)', Gis::createData('\'POINT()\',10', 50600));
    }

    public function testGetFunctions(): void
    {
        $funcs = Gis::getFunctions();
        self::assertArrayHasKey('Dimension', $funcs);
        self::assertArrayHasKey('GeometryType', $funcs);
        self::assertArrayHasKey('MBRDisjoint', $funcs);
    }
}
