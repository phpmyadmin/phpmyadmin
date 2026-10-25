<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\ForeignKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @covers \PhpMyAdmin\Utils\ForeignKey
 */
#[CoversClass(ForeignKey::class)]
class ForeignKeyTest extends AbstractTestCase
{
    /**
     * foreign key supported test
     *
     * @param string $a Engine
     * @param bool   $e Expected Value
     *
     * @dataProvider providerIsSupported
     */
    #[DataProvider('providerIsSupported')]
    public function testIsSupported(string $a, bool $e): void
    {
        $GLOBALS['server'] = 1;

        self::assertSame($e, ForeignKey::isSupported($a));
    }

    /**
     * data provider for foreign key supported test
     *
     * @return array
     */
    public static function providerIsSupported(): array
    {
        return [
            ['MyISAM', false],
            ['innodb', true],
            ['pBxT', true],
            ['ndb', true],
        ];
    }

    public function testIsCheckEnabled(): void
    {
        $GLOBALS['server'] = 1;

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'enable';
        self::assertTrue(ForeignKey::isCheckEnabled());

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        self::assertFalse(ForeignKey::isCheckEnabled());

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        self::assertTrue(ForeignKey::isCheckEnabled());
    }

    /**
     * @return array[]
     */
    public static function providerCheckInit(): array
    {
        return [
            ['', 'OFF'],
            ['0', 'OFF'],
            ['1', 'ON'],
        ];
    }

    /**
     * @dataProvider providerCheckInit
     */
    #[DataProvider('providerCheckInit')]
    public function testHandleDisableCheckInit(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->willReturn('ON');

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        self::assertTrue(ForeignKey::handleDisableCheckInit());
    }

    /**
     * @dataProvider providerCheckInit
     */
    #[DataProvider('providerCheckInit')]
    public function testHandleDisableCheckInitVarFalse(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->willReturn('OFF');

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        self::assertFalse(ForeignKey::handleDisableCheckInit());
    }

    /**
     * @return array[]
     */
    public static function providerCheckCleanup(): array
    {
        return [
            [true, 'ON'],
            [false, 'OFF'],
        ];
    }

    /**
     * @dataProvider providerCheckCleanup
     */
    #[DataProvider('providerCheckCleanup')]
    public function testHandleDisableCheckCleanup(bool $checkValue, string $setVariableParam): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $GLOBALS['dbi'] = $dbi;

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        ForeignKey::handleDisableCheckCleanup($checkValue);
    }
}
