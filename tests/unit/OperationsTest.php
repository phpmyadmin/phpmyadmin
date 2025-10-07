<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Table\TableMover;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_merge;

#[CoversClass(Operations::class)]
class OperationsTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Operations $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $relation = new Relation($this->dbi);
        $this->object = new Operations($this->dbi, $relation, new TableMover($this->dbi, $relation));
    }

    /** @param array<string, string> $extraChoice */
    #[DataProvider('providerGetPartitionMaintenanceChoices')]
    public function testGetPartitionMaintenanceChoices(string $tableName, array $extraChoice): void
    {
        Current::$database = 'database';
        Current::$table = $tableName;

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
        self::assertSame($expected, $actual);
    }

    /** @return array<string, array{0: string, 1: array<string, string>}> */
    public static function providerGetPartitionMaintenanceChoices(): array
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
