<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Operations;

use function array_merge;

/**
 * @covers \PhpMyAdmin\Operations
 */
class OperationsTest extends AbstractTestCase
{
    /** @var Operations */
    private $object;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 1;

        $this->object = new Operations($this->dbi, new Relation($this->dbi));
    }

    /**
     * @dataProvider providerGetPartitionMaintenanceChoices
     */
    public function testGetPartitionMaintenanceChoices(string $tableName, array $extraChoice): void
    {
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = $tableName;

        $choices = [
            'ANALYZE' => 'Analyze',
            'CHECK' => 'Check',
            'OPTIMIZE' => 'Optimize',
            'REBUILD' => 'Rebuild',
            'REPAIR' => 'Repair',
            'TRUNCATE' => 'Truncate',
        ];
        $expected = array_merge($choices, $extraChoice);

        $actual = $this->object->getPartitionMaintenanceChoices();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-return array<string, array{0: string, 1: array<string, string>}>
     */
    public function providerGetPartitionMaintenanceChoices(): array
    {
        return [
            'no partition method' => ['no_partition_method', ['COALESCE' => 'Coalesce']],
            'RANGE partition method' => ['range_partition_method', ['DROP' => 'Drop']],
            'RANGE COLUMNS partition method' => ['range_columns_partition_method', ['DROP' => 'Drop']],
            'LIST partition method' => ['list_partition_method', ['DROP' => 'Drop']],
            'LIST COLUMNS partition method' => ['list_columns_partition_method', ['DROP' => 'Drop']],
        ];
    }
}
