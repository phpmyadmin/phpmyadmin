<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the database index columns class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

/**
 * Index column wrapper
 *
 * @package PhpMyAdmin
 */
class IndexColumn
{
    /**
     * @var string The column name
     */
    private $_name = '';

    /**
     * @var integer The column sequence number in the index, starting with 1.
     */
    private $_seq_in_index = 1;

    /**
     * @var string How the column is sorted in the index. “A” (Ascending) or
     * NULL (Not sorted)
     */
    private $_collation = null;

    /**
     * The number of indexed characters if the column is only partly indexed,
     * NULL if the entire column is indexed.
     *
     * @var integer
     */
    private $_sub_part = null;

    /**
     * Contains YES if the column may contain NULL.
     * If not, the column contains NO.
     *
     * @var string
     */
    private $_null = '';

    /**
     * An estimate of the number of unique values in the index. This is updated
     * by running ANALYZE TABLE or myisamchk -a. Cardinality is counted based on
     * statistics stored as integers, so the value is not necessarily exact even
     * for small tables. The higher the cardinality, the greater the chance that
     * MySQL uses the index when doing joins.
     *
     * @var integer
     */
    private $_cardinality = null;

    /**
     * Constructor
     *
     * @param array $params an array containing the parameters of the index column
     */
    public function __construct(array $params = array())
    {
        $this->set($params);
    }

    /**
     * Sets parameters of the index column
     *
     * @param array $params an array containing the parameters of the index column
     *
     * @return void
     */
    public function set(array $params)
    {
        if (isset($params['Column_name'])) {
            $this->_name = $params['Column_name'];
        }
        if (isset($params['Seq_in_index'])) {
            $this->_seq_in_index = $params['Seq_in_index'];
        }
        if (isset($params['Collation'])) {
            $this->_collation = $params['Collation'];
        }
        if (isset($params['Cardinality'])) {
            $this->_cardinality = $params['Cardinality'];
        }
        if (isset($params['Sub_part'])) {
            $this->_sub_part = $params['Sub_part'];
        }
        if (isset($params['Null'])) {
            $this->_null = $params['Null'];
        }
    }

    /**
     * Returns the column name
     *
     * @return string column name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the column collation
     *
     * @return string column collation
     */
    public function getCollation()
    {
        return $this->_collation;
    }

    /**
     * Returns the cardinality of the column
     *
     * @return int cardinality of the column
     */
    public function getCardinality()
    {
        return $this->_cardinality;
    }

    /**
     * Returns whether the column is nullable
     *
     * @param boolean $as_text whether to returned the string representation
     *
     * @return mixed nullability of the column. True/false or Yes/No depending
     *               on the value of the $as_text parameter
     */
    public function getNull($as_text = false)
    {
        if ($as_text) {
            if (!$this->_null || $this->_null == 'NO') {
                return __('No');
            }

            return __('Yes');
        }

        return $this->_null;
    }

    /**
     * Returns the sequence number of the column in the index
     *
     * @return int sequence number of the column in the index
     */
    public function getSeqInIndex()
    {
        return $this->_seq_in_index;
    }

    /**
     * Returns the number of indexed characters if the column is only
     * partly indexed
     *
     * @return int the number of indexed characters
     */
    public function getSubPart()
    {
        return $this->_sub_part;
    }

    /**
     * Gets the properties in an array for comparison purposes
     *
     * @return array an array containing the properties of the index column
     */
    public function getCompareData()
    {
        return array(
            'Column_name'   => $this->_name,
            'Seq_in_index'  => $this->_seq_in_index,
            'Collation'     => $this->_collation,
            'Sub_part'      => $this->_sub_part,
            'Null'          => $this->_null,
        );
    }
}
