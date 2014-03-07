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
                . " WHERE `TABLE_SCHEMA` = '" . $db
                . "' AND `TABLE_NAME` = '" . $table . "'"
            );
        } else {
            return array();
        }
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
            if (PMA_MYSQL_INT_VERSION >= 50100) {
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
        }
        return $have_partitioning;
    }
}
?>
