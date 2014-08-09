<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains abstract class to hold relation preferences/statistics
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Relations preferences/statistics
 *
 * This class fetches the table master and foreign fields positions
 * and helps in generating the Table references and then connects
 * master table's master field to foreign table's foreign key.
 *
 * @package PhpMyAdmin
 * @abstract
 */
abstract class RealtionStats
{

}
?>