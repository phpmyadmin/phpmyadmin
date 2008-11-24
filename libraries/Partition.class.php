<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the partitions
 *
 * @version $Id$
 * @package phpMyAdmin
 */


/**
 * base Partition Class
 * @package phpMyAdmin
 */
class PMA_Partition
{
    /**
     * returns array of partition names for a specific db/table
     *
     * @access  public
     * @uses    PMA_DBI_fetch_result()
     * @return  array   of partition names
     */
    static public function getPartitionNames($db, $table)
    {
        if (PMA_Partition::havePartitioning()) {
            return PMA_DBI_fetch_result("select `PARTITION_NAME` from `information_schema`.`PARTITIONS` where `TABLE_SCHEMA` = '" . $db . "' and `TABLE_NAME` = '" . $table . "'");
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
     * @uses    PMA_DBI_fetch_result()
     * @return  boolean
     */
    static public function havePartitioning()
    {
        static $have_partitioning = false;
        static $already_checked = false;

        if (! $already_checked) {
            $have_partitioning = PMA_MYSQL_INT_VERSION >= 50100 && PMA_DBI_fetch_value("SHOW VARIABLES LIKE 'have_partitioning';");
            $already_checked = true;
        }
        return $have_partitioning;
    }

}

?>
