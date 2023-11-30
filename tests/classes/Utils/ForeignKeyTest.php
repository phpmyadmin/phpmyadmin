<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\ForeignKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ForeignKey::class)]
class ForeignKeyTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    /**
     * foreign key supported test
     *
     * @param string $a Engine
     * @param bool   $e Expected Value
     */
    #[DataProvider('providerIsSupported')]
    public function testIsSupported(string $a, bool $e): void
    {
        $GLOBALS['server'] = 1;

        $this->assertEquals(
            $e,
            ForeignKey::isSupported($a),
        );
    }

    /**
     * data provider for foreign key supported test
     *
     * @return mixed[]
     */
    public static function providerIsSupported(): array
    {
        return [['MyISAM', false], ['innodb', true], ['pBxT', true], ['ndb', true]];
    }

    public function testIsCheckEnabled(): void
    {
        $GLOBALS['server'] = 1;

        $config = Config::getInstance();
        $config->settings['DefaultForeignKeyChecks'] = 'enable';
        $this->assertTrue(
            ForeignKey::isCheckEnabled(),
        );

        $config->settings['DefaultForeignKeyChecks'] = 'disable';
        $this->assertFalse(
            ForeignKey::isCheckEnabled(),
        );

        $config->settings['DefaultForeignKeyChecks'] = 'default';
        $this->assertTrue(
            ForeignKey::isCheckEnabled(),
        );
    }

    /** @return mixed[][] */
    public static function providerCheckInit(): array
    {
        return [['', 'OFF'], ['0', 'OFF'], ['1', 'ON']];
    }

    #[DataProvider('providerCheckInit')]
    public function testHandleDisableCheckInit(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->willReturn('ON');

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        $this->assertTrue(ForeignKey::handleDisableCheckInit());
    }

    #[DataProvider('providerCheckInit')]
    public function testHandleDisableCheckInitVarFalse(string $checksValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $_REQUEST['fk_checks'] = $checksValue;

        $dbi->expects($this->once())
            ->method('getVariable')
            ->willReturn('OFF');

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        $this->assertFalse(ForeignKey::handleDisableCheckInit());
    }

    /** @return mixed[][] */
    public static function providerCheckCleanup(): array
    {
        return [[true, 'ON'], [false, 'OFF']];
    }

    #[DataProvider('providerCheckCleanup')]
    public function testHandleDisableCheckCleanup(bool $checkValue, string $setVariableParam): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $dbi->expects($this->once())
            ->method('setVariable')
            ->with('FOREIGN_KEY_CHECKS', $setVariableParam)
            ->willReturn(true);

        ForeignKey::handleDisableCheckCleanup($checkValue);
    }
}
