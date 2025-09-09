<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Validator::class)]
final class ValidatorTest extends AbstractTestCase
{
    public function testGetValidators(): void
    {
        (new ReflectionProperty(Validator::class, 'validators'))->setValue(null, null);
        Config::$instance = new Config();
        $validators = Validator::getValidators(new ConfigFile([]));
        $expected = [
            'Console/Height' => 'validateNonNegativeNumber',
            'CharTextareaCols' => 'validatePositiveNumber',
            'CharTextareaRows' => 'validatePositiveNumber',
            'ExecTimeLimit' => 'validateNonNegativeNumber',
            'Export/sql_max_query_size' => 'validatePositiveNumber',
            'FirstLevelNavigationItems' => 'validatePositiveNumber',
            'ForeignKeyMaxLimit' => 'validatePositiveNumber',
            'Import/csv_enclosed' => [['validateByRegex', '/^.?$/']],
            'Import/csv_escaped' => [['validateByRegex', '/^.$/']],
            'Import/csv_terminated' => [['validateByRegex', '/^.$/']],
            'Import/ldi_enclosed' => [['validateByRegex', '/^.?$/']],
            'Import/ldi_escaped' => [['validateByRegex', '/^.$/']],
            'Import/ldi_terminated' => [['validateByRegex', '/^.$/']],
            'Import/skip_queries' => 'validateNonNegativeNumber',
            'InsertRows' => 'validatePositiveNumber',
            'NumRecentTables' => 'validateNonNegativeNumber',
            'NumFavoriteTables' => 'validateNonNegativeNumber',
            'LimitChars' => 'validatePositiveNumber',
            'LoginCookieValidity' => 'validatePositiveNumber',
            'LoginCookieStore' => 'validateNonNegativeNumber',
            'MaxDbList' => ['validatePositiveNumber', ['validateUpperBound', 100]],
            'MaxNavigationItems' => 'validatePositiveNumber',
            'MaxCharactersInDisplayedSQL' => 'validatePositiveNumber',
            'MaxRows' => 'validatePositiveNumber',
            'MaxSizeForInputField' => 'validatePositiveNumber',
            'MinSizeForInputField' => 'validateNonNegativeNumber',
            'MaxTableList' => ['validatePositiveNumber', ['validateUpperBound', 250]],
            'MaxRoutineList' => ['validatePositiveNumber', ['validateUpperBound', 250]],
            'MemoryLimit' => [['validateByRegex', '/^(-1|(\d+(?:[kmg])?))$/i']],
            'NavigationTreeDisplayItemFilterMinimum' => 'validatePositiveNumber',
            'NavigationTreeTableLevel' => 'validatePositiveNumber',
            'NavigationWidth' => 'validateNonNegativeNumber',
            'QueryHistoryMax' => ['validatePositiveNumber', ['validateUpperBound', 25]],
            'RepeatCells' => 'validateNonNegativeNumber',
            'Server' => 'validateServer',
            'Server_pmadb' => 'validatePMAStorage',
            'Servers/1/port' => 'validatePortNumber',
            'Servers/1/hide_db' => 'validateRegex',
            'TextareaCols' => 'validatePositiveNumber',
            'TextareaRows' => 'validatePositiveNumber',
            'TrustedProxies' => 'validateTrustedProxies',
        ];
        self::assertSame($expected, $validators);
    }
}
