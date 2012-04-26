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
    /**
     * Returns list of unary operators.
     *
     * @return array
     */
    public function getUnaryOperators() {
        return array(
           'IS NULL',
           'IS NOT NULL',
           "= ''",
           "!= ''",
        );
    }

    /**
     * Check whether operator is unary.
     *
     * @param string $op operator name
     *
     * @return boolean
     */
    public function isUnaryOperator($op) {
        return in_array($op, $this->getUnaryOperators());
    }

    /**
     * Returns list of operators checking for NULL.
     *
     * @return array
     */
    public function getNullOperators() {
        return array(
           'IS NULL',
           'IS NOT NULL',
        );
    }
}
