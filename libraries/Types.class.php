<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL data types definition
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Generic class holding type definitions.
 */
class PMA_Types
{
    public function getUnaryOperators() {
        return array(
           'IS NULL',
           'IS NOT NULL',
           "= ''",
           "!= ''",
        );
    }

    public function isUnaryOperator($op) {
        return in_array($op, $this->getUnaryOperators());
    }
}
