<?php
/**
 * Library for extracting information about the partitions
 */

declare(strict_types=1);

namespace PhpMyAdmin\Partitioning;

use PhpMyAdmin\DatabaseInterface;

use function array_values;

class Partition extends SubPartition
{
    protected string|null $description = null;
    /** @var SubPartition[] */
    protected array $subPartitions = [];

    /**
     * Loads data from the fetched row from information_schema.PARTITIONS
     *
     * @param mixed[] $row fetched row
     */
    public function __construct(array $row)
    {
        $this->name = $row['PARTITION_NAME'];
        $this->ordinal = $row['PARTITION_ORDINAL_POSITION'] !== null ? (int) $row['PARTITION_ORDINAL_POSITION'] : null;
        $this->method = $row['PARTITION_METHOD'];
        $this->expression = $row['PARTITION_EXPRESSION'];
        $this->description = $row['PARTITION_DESCRIPTION'];
        // no sub partitions, load all data to this object
        if (! empty($row['SUBPARTITION_NAME'])) {
            return;
        }

        $this->loadCommonData($row);
    }

    /**
     * Returns the partition description
     */
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Add a sub partition
     */
    public function addSubPartition(SubPartition $subPartition): void
    {
        $this->subPartitions[] = $subPartition;
    }

    /**
     * Whether there are sub partitions
     */
    public function hasSubPartitions(): bool
    {
        return $this->subPartitions !== [];
    }

    /**
     * Returns the number of data rows
     *
     * @return int number of rows
     */
    public function getRows(): int
    {
        if ($this->subPartitions === []) {
            return $this->rows;
        }

        $rows = 0;
        foreach ($this->subPartitions as $subPartition) {
            $rows += $subPartition->rows;
        }

        return $rows;
    }

    /**
     * Returns the total data length
     *
     * @return int data length
     */
    public function getDataLength(): int
    {
        if ($this->subPartitions === []) {
            return $this->dataLength;
        }

        $dataLength = 0;
        foreach ($this->subPartitions as $subPartition) {
            $dataLength += $subPartition->dataLength;
        }

        return $dataLength;
    }

    /**
     * Returns the total index length
     *
     * @return int index length
     */
    public function getIndexLength(): int
    {
        if ($this->subPartitions === []) {
            return $this->indexLength;
        }

        $indexLength = 0;
        foreach ($this->subPartitions as $subPartition) {
            $indexLength += $subPartition->indexLength;
        }

        return $indexLength;
    }

    /**
     * Returns the list of sub partitions
     *
     * @return SubPartition[]
     */
    public function getSubPartitions(): array
    {
        return $this->subPartitions;
    }

    /**
     * Returns array of partitions for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return Partition[]
     */
    public static function getPartitions(string $db, string $table): array
    {
        if (self::havePartitioning()) {
            $dbi = DatabaseInterface::getInstance();
            $result = $dbi->fetchResult(
                'SELECT * FROM `information_schema`.`PARTITIONS`'
                . ' WHERE `TABLE_SCHEMA` = ' . $dbi->quoteString($db)
                . ' AND `TABLE_NAME` = ' . $dbi->quoteString($table),
            );
            if ($result !== []) {
                $partitionMap = [];
                /** @var array $row */
                foreach ($result as $row) {
                    if (isset($partitionMap[$row['PARTITION_NAME']])) {
                        $partition = $partitionMap[$row['PARTITION_NAME']];
                    } else {
                        $partition = new Partition($row);
                        $partitionMap[$row['PARTITION_NAME']] = $partition;
                    }

                    if (empty($row['SUBPARTITION_NAME'])) {
                        continue;
                    }

                    $partition->addSubPartition(new SubPartition($row));
                }

                return array_values($partitionMap);
            }
        }

        return [];
    }

    /**
     * returns array of partition names for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return mixed[]   of partition names
     */
    public static function getPartitionNames(string $db, string $table): array
    {
        if (self::havePartitioning()) {
            $dbi = DatabaseInterface::getInstance();

            return $dbi->fetchResult(
                'SELECT DISTINCT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS`'
                . ' WHERE `TABLE_SCHEMA` = ' . $dbi->quoteString($db)
                . ' AND `TABLE_NAME` = ' . $dbi->quoteString($table),
            );
        }

        return [];
    }

    /**
     * returns the partition method used by the table.
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string|null partition method
     */
    public static function getPartitionMethod(string $db, string $table): string|null
    {
        if (self::havePartitioning()) {
            $dbi = DatabaseInterface::getInstance();
            $partitionMethod = $dbi->fetchResult(
                'SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS`'
                . ' WHERE `TABLE_SCHEMA` = ' . $dbi->quoteString($db)
                . ' AND `TABLE_NAME` = ' . $dbi->quoteString($table)
                . ' LIMIT 1',
            );
            if ($partitionMethod !== []) {
                return $partitionMethod[0];
            }
        }

        return null;
    }

    /**
     * checks if MySQL server supports partitioning
     *
     * @staticvar bool $have_partitioning
     * @staticvar bool $already_checked
     */
    public static function havePartitioning(): bool
    {
        static $havePartitioning = false;
        static $alreadyChecked = false;

        if (! $alreadyChecked) {
            $dbi = DatabaseInterface::getInstance();
            if ($dbi->getVersion() < 50600) {
                if ($dbi->fetchValue('SELECT @@have_partitioning;')) {
                    $havePartitioning = true;
                }
            } elseif ($dbi->getVersion() >= 80000) {
                $havePartitioning = true;
            } else {
                // see https://dev.mysql.com/doc/refman/5.6/en/partitioning.html
                $plugins = $dbi->fetchResult('SHOW PLUGINS');
                foreach ($plugins as $value) {
                    if ($value['Name'] === 'partition') {
                        $havePartitioning = true;
                        break;
                    }
                }
            }

            $alreadyChecked = true;
        }

        return $havePartitioning;
    }
}
