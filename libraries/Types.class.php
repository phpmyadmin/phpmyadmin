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

    /**
     * ENUM search operators
     *
     * @return array
     */
    public function getEnumOperators() {
        return array(
           '=',
           '!=',
        );
    }

    /**
     * TEXT search operators
     *
     * @return array
     */
    public function getTextOperators() {
        return array(
           'LIKE',
           'LIKE %...%',
           'NOT LIKE',
           '=',
           '!=',
           'REGEXP',
           'REGEXP ^...$',
           'NOT REGEXP',
           "= ''",
           "!= ''",
           'IN (...)',
           'NOT IN (...)',
           'BETWEEN',
           'NOT BETWEEN',
        );
    }

    /**
     * Number search operators
     *
     * @return array
     */
    public function getNumberOperators() {
        return array(
           '=',
           '>',
           '>=',
           '<',
           '<=',
           '!=',
           'LIKE',
           'NOT LIKE',
           'IN (...)',
           'NOT IN (...)',
           'BETWEEN',
           'NOT BETWEEN',
        );
    }

    /**
     * Returns operators for given type
     *
     * @param string  $type Type of field
     * @param boolean $null Whether field can be NULL
     *
     * @return array
     */
    public function getTypeOperators($type, $null) {
        $ret = array();

        if (strncasecmp($type, 'enum', 4) == 0) {
            $ret = array_merge($ret, $this->getEnumOperators());
        } elseif (preg_match('@char|blob|text|set@i', $type)) {
            $ret = array_merge($ret, $this->getTextOperators());
        } else {
            $ret = array_merge($ret, $this->getNumberOperators());
        }

        if ($null) {
            $ret = array_merge($ret, $this->getNullOperators());
        }

        return $ret;
    }

    /**
     * Returns operators for given type as html options
     *
     * @param string  $type Type of field
     * @param boolean $null Whether field can be NULL
     *
     * @return array
     */
    public function getTypeOperatorsHtml($type, $null) {
        $html = '';

        foreach ($this->getTypeOperators($type, $null) as $fc) {
            $html .= "\n" . '                        '
                . '<option value="' . htmlspecialchars($fc) . '">'
                . htmlspecialchars($fc) . '</option>';
        }

        return $html;
    }
}
