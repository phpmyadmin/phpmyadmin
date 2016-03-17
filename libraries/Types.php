<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL data types definition
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Generic class holding type definitions.
 *
 * @package PhpMyAdmin
 */
class Types
{
    /**
     * Returns list of unary operators.
     *
     * @return string[]
     */
    public function getUnaryOperators()
    {
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
    public function isUnaryOperator($op)
    {
        return in_array($op, $this->getUnaryOperators());
    }

    /**
     * Returns list of operators checking for NULL.
     *
     * @return string[]
     */
    public function getNullOperators()
    {
        return array(
            'IS NULL',
            'IS NOT NULL',
        );
    }

    /**
     * ENUM search operators
     *
     * @return string[]
     */
    public function getEnumOperators()
    {
        return array(
            '=',
            '!=',
        );
    }

    /**
     * TEXT search operators
     *
     * @return string[]
     */
    public function getTextOperators()
    {
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
     * @return string[]
     */
    public function getNumberOperators()
    {
        return array(
            '=',
            '>',
            '>=',
            '<',
            '<=',
            '!=',
            'LIKE',
            'LIKE %...%',
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
     * @return string[]
     */
    public function getTypeOperators($type, $null)
    {
        $ret = array();
        $class = $this->getTypeClass($type);

        if (strncasecmp($type, 'enum', 4) == 0) {
            $ret = array_merge($ret, $this->getEnumOperators());
        } elseif ($class == 'CHAR') {
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
     * @param string  $type             Type of field
     * @param boolean $null             Whether field can be NULL
     * @param string  $selectedOperator Option to be selected
     *
     * @return string Generated Html
     */
    public function getTypeOperatorsHtml($type, $null, $selectedOperator = null)
    {
        $html = '';

        foreach ($this->getTypeOperators($type, $null) as $fc) {
            if (isset($selectedOperator) && $selectedOperator == $fc) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $html .= '<option value="' . htmlspecialchars($fc)  . '"'
                . $selected . '>'
                . htmlspecialchars($fc)  . '</option>';
        }

        return $html;
    }

    /**
     * Returns the data type description.
     *
     * @param string $type The data type to get a description.
     *
     * @return string
     *
     */
    public function getTypeDescription($type)
    {
        return '';
    }

    /**
     * Returns class of a type, used for functions available for type
     * or default values.
     *
     * @param string $type The data type to get a class.
     *
     * @return string
     *
     */
    public function getTypeClass($type)
    {
        return '';
    }

    /**
     * Returns array of functions available for a class.
     *
     * @param string $class The class to get function list.
     *
     * @return string[]
     *
     */
    public function getFunctionsClass($class)
    {
        return array();
    }

    /**
     * Returns array of functions available for a type.
     *
     * @param string $type The data type to get function list.
     *
     * @return string[]
     *
     */
    public function getFunctions($type)
    {
        $class = $this->getTypeClass($type);
        return $this->getFunctionsClass($class);
    }

    /**
     * Returns array of all functions available.
     *
     * @return string[]
     *
     */
    public function getAllFunctions()
    {
        $ret = array_merge(
            $this->getFunctionsClass('CHAR'),
            $this->getFunctionsClass('NUMBER'),
            $this->getFunctionsClass('DATE'),
            $this->getFunctionsClass('UUID')
        );
        sort($ret);
        return $ret;
    }

    /**
     * Returns array of all attributes available.
     *
     * @return string[]
     *
     */
    public function getAttributes()
    {
        return array();
    }

    /**
     * Returns array of all column types available.
     *
     * @return string[]
     *
     */
    public function getColumns()
    {
        // most used types
        return array(
            'INT',
            'VARCHAR',
            'TEXT',
            'DATE',
        );
    }

    /**
     * Returns an array of integer types
     *
     * @return string[] integer types
     */
    public function getIntegerTypes()
    {
        return array();
    }

    /**
     * Returns the min and max values of a given integer type
     *
     * @param string  $type   integer type
     * @param boolean $signed whether signed
     *
     * @return string[] min and max values
     */
    public function getIntegerRange($type, $signed = true)
    {
        return array('', '');
    }
}
