<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the partitions
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * base Partition Class
 *
 * @package PhpMyAdmin
 */
class Partition extends SubPartition
{
    /**
     * @var string partition description
     */
    protected $description;
    /**
     * @var SubPartition[] sub partitions
     */
    protected $subPartitions = array();

    /**
     * Loads data from the fetched row from information_schema.PARTITIONS
     *
     * @param array $row fetched row
     *
     * @return void
     */
    protected function loadData($row)
    {
        $this->name = $row['PARTITION_NAME'];
        $this->ordinal = $row['PARTITION_ORDINAL_POSITION'];
        $this->method = $row['PARTITION_METHOD'];
        $this->expression = $row['PARTITION_EXPRESSION'];
        $this->description = $row['PARTITION_DESCRIPTION'];
        // no sub partitions, load all data to this object
        if (empty($row['SUBPARTITION_NAME'])) {
            $this->loadCommonData($row);
        }
    }

    /**
     * Returns the partiotion description
     *
     * @return string partition description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Add a sub partition
     *
     * @param SubPartition $partition Sub partition
     *
     * @return void
     */
    public function addSubPartition(SubPartition $partition)
    {
        $this->subPartitions[] = $partition;
    }

    /**
     * Whether there are sub partitions
     *
     * @return boolean
     */
    public function hasSubPartitions()
    {
        return ! empty($this->subPartitions);
    }

    /**
     * Returns the number of data rows
     *
     * @return integer number of rows
     */
    public function getRows()
    {
        if (empty($this->subPartitions)) {
            return $this->rows;
        } else {
            $rows = 0;
            foreach ($this->subPartitions as $subPartition) {
                $rows += $subPartition->rows;
            }
            return $rows;
        }
    }

    /**
     * Returns the total data length
     *
     * @return integer data length
     */
    public function getDataLength()
    {
        if (empty($this->subPartitions)) {
            return $this->dataLength;
        } else {
            $dataLength = 0;
            foreach ($this->subPartitions as $subPartition) {
                $dataLength += $subPartition->dataLength;
            }
            return $dataLength;
        }
    }

    /**
     * Returns the tatal index length
     *
     * @return integer index length
     */
    public function getIndexLength()
    {
        if (empty($this->subPartitions)) {
            return $this->indexLength;
        } else {
            $indexLength = 0;
            foreach ($this->subPartitions as $subPartition) {
                $indexLength += $subPartition->indexLength;
            }
            return $indexLength;
        }
    }

    /**
     * Returns the list of sub partitions
     *
     * @return SubPartition[]
     */
    public function getSubPartitions()
    {
        return $this->subPartitions;
    }

    /**
     * Returns array of partitions for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @access  public
     * @return Partition[]
     */
    static public function getPartitions($db, $table)
    {
        if (Partition::havePartitioning()) {
            $result = $GLOBALS['dbi']->fetchResult(
                "SELECT * FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . Util::sqlAddSlashes($db)
                . "' AND `TABLE_NAME` = '" . Util::sqlAddSlashes($table) . "'"
            );
            if ($result) {
                $partitionMap = array();
                foreach ($result as $row) {
                    if (isset($partitionMap[$row['PARTITION_NAME']])) {
                        $partition = $partitionMap[$row['PARTITION_NAME']];
                    } else {
                        $partition = new Partition($row);
                        $partitionMap[$row['PARTITION_NAME']] = $partition;
                    }

                    if (! empty($row['SUBPARTITION_NAME'])) {
                        $parentPartition = $partition;
                        $partition = new SubPartition($row);
                        $parentPartition->addSubPartition($partition);
                    }
                }
                return array_values($partitionMap);
            }
            return array();
        } else {
            return array();
        }
    }

    /**
     * returns array of partition names for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @access  public
     * @return array   of partition names
     */
    static public function getPartitionNames($db, $table)
    {
        if (Partition::havePartitioning()) {
            return $GLOBALS['dbi']->fetchResult(
                "SELECT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . Util::sqlAddSlashes($db)
                . "' AND `TABLE_NAME` = '" . Util::sqlAddSlashes($table) . "'"
            );
        } else {
            return array();
        }
    }

    /**
     * returns the partition method used by the table.
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return string partition method
     */
    static public function getPartitionMethod($db, $table)
    {
        if (Partition::havePartitioning()) {
            $partition_method = $GLOBALS['dbi']->fetchResult(
                "SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . Util::sqlAddSlashes($db) . "'"
                . " AND `TABLE_NAME` = '" . Util::sqlAddSlashes($table) . "'"
            );
            if (! empty($partition_method)) {
                return $partition_method[0];
            }
        }
        return null;
    }

    /**
     * checks if MySQL server supports partitioning
     *
     * @static
     * @staticvar boolean $have_partitioning
     * @staticvar boolean $already_checked
     * @access  public
     * @return boolean
     */
    static public function havePartitioning()
    {
        static $have_partitioning = false;
        static $already_checked = false;

        if (! $already_checked) {
            if (PMA_MYSQL_INT_VERSION < 50600) {
                if ($GLOBALS['dbi']->fetchValue(
                    "SELECT @@have_partitioning;"
                )) {
                    $have_partitioning = true;
                }
            } else {
                // see https://dev.mysql.com/doc/refman/5.6/en/partitioning.html
                $plugins = $GLOBALS['dbi']->fetchResult("SHOW PLUGINS");
                foreach ($plugins as $value) {
                    if ($value['Name'] == 'partition') {
                        $have_partitioning = true;
                        break;
                    }
                }
            }
            $already_checked = true;
        }
        return $have_partitioning;
    }
}
