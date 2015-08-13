<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the partitions
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * base Partition Class
 *
 * @package PhpMyAdmin
 */
class PMA_Partition
{
    /**
     * @var string the database
     */
    protected $db;
    /**
     * @var string the table
     */
    protected $table;
    /**
     * @var string partition name
     */
    protected $name;
    /**
     * @var int ordinal
     */
    protected $ordinal;
    /**
     * @var string partition method
     */
    protected $method;
    /**
     * @var string partition expression
     */
    protected $expression;
    /**
     * @var string partition description
     */
    protected $description;
    /**
     * @var integer no of table rows in the parition
     */
    protected $rows;
    /**
     * @var PMA_Partition[] sub partitions
     */
    protected $subPartitions = array();

    /**
     * Constructs a partition
     *
     * @param string $db         database name
     * @param string $table      table name
     * @param string $name       parition name
     * @param int    $ordinal    ordinal
     * @param string $method     partition method
     * @param string $expression partition expression
     */
    public function __construct($db, $table, $name, $ordinal, $method, $expression)
    {
        $this->db = $db;
        $this->table = $table;
        $this->name = $name;
        $this->ordinal = $ordinal;
        $this->method = $method;
        $this->expression = $expression;
    }

    /**
     * Sets the partition description
     *
     * @param string $description parition description
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Sets the number of rows in the parition
     *
     * @param integer $rows number of rows
     *
     * @return void
     */
    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    /**
     * Add a sub parition
     *
     * @param PMA_Partition $parition
     *
     * @return void
     */
    public function addSubPartition(PMA_Partition $parition)
    {
        $this->subPartitions[] = $parition;
    }

    /**
     * Returns array of partitions for a specific db/table
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @access  public
     * @return PMA_Partition[]
     */
    static public function getParititions($db, $table)
    {
        if (PMA_Partition::havePartitioning()) {
            $result = $GLOBALS['dbi']->fetchResult(
                "SELECT * FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . PMA_Util::sqlAddSlashes($db)
                . "' AND `TABLE_NAME` = '" . PMA_Util::sqlAddSlashes($table) . "'"
            );
            if ($result) {
                $partitionMap = array();
                foreach ($result as $row) {

                    if (isset($partitionMap[$row['PARTITION_NAME']])) {
                        $tempPartition = $partitionMap[$row['PARTITION_NAME']];
                    } else {
                        $tempPartition = new PMA_Partition(
                            $db,
                            $table,
                            $row['PARTITION_NAME'],
                            $row['PARTITION_ORDINAL_POSITION'],
                            $row['PARTITION_METHOD'],
                            $row['PARTITION_EXPRESSION']
                        );
                        $tempPartition->setDescription($row['PARTITION_DESCRIPTION']);
                        $partitionMap[$row['PARTITION_NAME']] = $tempPartition;
                    }

                    if (! empty($row['SUBPARTITION_NAME'])) {
                        $parentPartition = $tempPartition;
                        $partition = new PMA_Partition(
                            $db,
                            $table,
                            $row['SUBPARTITION_NAME'],
                            $row['SUBPARTITION_ORDINAL_POSITION'],
                            $row['SUBPARTITION_METHOD'],
                            $row['SUBPARTITION_EXPRESSION']
                        );
                        $parentPartition->addSubPartition($parition);
                    } else {
                        $partition = $tempPartition;
                    }

                    $partition->setRows($row['TABLE_ROWS']);
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
        if (PMA_Partition::havePartitioning()) {
            return $GLOBALS['dbi']->fetchResult(
                "SELECT `PARTITION_NAME` FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . PMA_Util::sqlAddSlashes($db)
                . "' AND `TABLE_NAME` = '" . PMA_Util::sqlAddSlashes($table) . "'"
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
        if (PMA_Partition::havePartitioning()) {
            $partition_method = $GLOBALS['dbi']->fetchResult(
                "SELECT `PARTITION_METHOD` FROM `information_schema`.`PARTITIONS`"
                . " WHERE `TABLE_SCHEMA` = '" . PMA_Util::sqlAddSlashes($db) . "'"
                . " AND `TABLE_NAME` = '" . PMA_Util::sqlAddSlashes($table) . "'"
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
                    "SHOW VARIABLES LIKE 'have_partitioning';"
                )) {
                    $have_partitioning = true;
                }
            } else {
                // see http://dev.mysql.com/doc/refman/5.6/en/partitioning.html
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
