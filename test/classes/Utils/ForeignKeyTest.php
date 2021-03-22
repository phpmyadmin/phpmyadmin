<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\ForeignKey;

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
    public function testIsSupported(string $a, bool $e): void
    {
        $GLOBALS['server'] = 1;

        $this->assertEquals(
            $e,
            ForeignKey::isSupported($a)
        );
    }

    /**
     * data provider for foreign key supported test
     *
     * @return array
     */
    public function providerIsSupported(): array
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
        $this->assertTrue(
            ForeignKey::isCheckEnabled()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        $this->assertFalse(
            ForeignKey::isCheckEnabled()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        $this->assertTrue(
            ForeignKey::isCheckEnabled()
        );
    }

    /**
     * @return array[]
     */
    public function providerCheckInit(): array
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
    public function testHandleDisableCheckInit(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->will($this->returnValue('ON'));

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        $this->assertTrue(ForeignKey::handleDisableCheckInit());
    }

    /**
     * @dataProvider providerCheckInit
     */
    public function testHandleDisableCheckInitVarFalse(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->will($this->returnValue('OFF'));

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        $this->assertFalse(ForeignKey::handleDisableCheckInit());
    }

    /**
     * @return array[]
     */
    public function providerCheckCleanup(): array
    {
        return [
            [true, 'ON'],
            [false, 'OFF'],
        ];
    }

    /**
     * @dataProvider providerCheckCleanup
     */
    public function testHandleDisableCheckCleanup(bool $checkValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->will($this->returnValue(true));

        ForeignKey::handleDisableCheckCleanup($checkValue);
    }
}
