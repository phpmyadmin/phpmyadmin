<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the database index class
 *
 * @package PhpMyAdmin
 */

/**
 * @since phpMyAdmin 3.0.0
 *
 * @package PhpMyAdmin
 */
class PMA_Index
{
    /**
     * Class-wide storage container for indexes (caching, singleton)
     *
     * @var array
     */
    protected static $_registry = array();

    /**
     * @var string The name of the schema
     */
    protected $_schema = '';

    /**
     * @var string The name of the table
     */
    protected $_table = '';

    /**
     * @var string The name of the index
     */
    protected $_name = '';

    /**
     * Columns in index
     *
     * @var array
     */
    protected $_columns = array();

    /**
     * The index method used (BTREE, SPATIAL, FULLTEXT, HASH, RTREE).
     *
     * @var string
     */
    protected $_type = '';

    /**
     * The index choice (PRIMARY, UNIQUE, INDEX, SPATIAL, FULLTEXT)
     *
     * @var string
     */
    protected $_choice = '';

    /**
     * Various remarks.
     *
     * @var string
     */
    protected $_remarks = '';

    /**
     * Any comment provided for the index with a COMMENT attribute when the
     * index was created.
     *
     * @var string
     */
    protected $_comment = '';

    /**
     * @var integer 0 if the index cannot contain duplicates, 1 if it can.
     */
    protected $_non_unique = 0;

    /**
     * Indicates how the key is packed. NULL if it is not.
     *
     * @var string
     */
    protected $_packed = null;

    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->set($params);
    }

    static public function singleton($schema, $table, $index_name = '')
    {
        PMA_Index::_loadIndexes($table, $schema);
        if (! isset(PMA_Index::$_registry[$schema][$table][$index_name])) {
            $index = new PMA_Index;
            if (strlen($index_name)) {
                $index->setName($index_name);
                PMA_Index::$_registry[$schema][$table][$index->getName()] = $index;
            }
            return $index;
        } else {
            return PMA_Index::$_registry[$schema][$table][$index_name];
        }
    }

    /**
     * returns an array with all indexes from the given table
     *
     * @param string $table
     * @param string $schema
     * @return  array
     */
    static public function getFromTable($table, $schema)
    {
        PMA_Index::_loadIndexes($table, $schema);

        if (isset(PMA_Index::$_registry[$schema][$table])) {
            return PMA_Index::$_registry[$schema][$table];
        } else {
            return array();
        }
    }

    /**
     * return primary if set, false otherwise
     *
     * @param string $table
     * @param string $schema
     * @return  mixed primary index or false if no one exists
     */
    static public function getPrimary($table, $schema)
    {
        PMA_Index::_loadIndexes($table, $schema);

        if (isset(PMA_Index::$_registry[$schema][$table]['PRIMARY'])) {
            return PMA_Index::$_registry[$schema][$table]['PRIMARY'];
        } else {
            return false;
        }
    }

    /**
     * Load index data for table
     *
     * @param string $table
     * @param string $schema
     * @return  boolean
     */
    static protected function _loadIndexes($table, $schema)
    {
        if (isset(PMA_Index::$_registry[$schema][$table])) {
            return true;
        }

        $_raw_indexes = PMA_DBI_get_table_indexes($schema, $table);
        foreach ($_raw_indexes as $_each_index) {
            $_each_index['Schema'] = $schema;
            if (! isset(PMA_Index::$_registry[$schema][$table][$_each_index['Key_name']])) {
                $key = new PMA_Index($_each_index);
                PMA_Index::$_registry[$schema][$table][$_each_index['Key_name']] = $key;
            } else {
                $key = PMA_Index::$_registry[$schema][$table][$_each_index['Key_name']];
            }

            $key->addColumn($_each_index);
        }

        return true;
    }

    /**
     * Add column to index
     *
     * @param array $params column params
     */
    public function addColumn($params)
    {
        if (strlen($params['Column_name'])) {
            $this->_columns[$params['Column_name']] = new PMA_Index_Column($params);
        }
    }

    public function addColumns($columns)
    {
        $_columns = array();

        if (isset($columns['names'])) {
            // coming from form
            // $columns[names][]
            // $columns[sub_parts][]
            foreach ($columns['names'] as $key => $name) {
                $sub_part = isset($columns['sub_parts'][$key]) ? $columns['sub_parts'][$key] : '';
                $_columns[] = array(
                    'Column_name'   => $name,
                    'Sub_part'      => $sub_part,
                );
            }
        } else {
            // coming from SHOW INDEXES
            // $columns[][name]
            // $columns[][sub_part]
            // ...
            $_columns = $columns;
        }

        foreach ($_columns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns true if $column indexed in this index
     *
     * @param string $column
     * @return  boolean
     */
    public function hasColumn($column)
    {
        return isset($this->_columns[$column]);
    }

    public function set($params)
    {
        if (isset($params['columns'])) {
            $this->addColumns($params['columns']);
        }
        if (isset($params['Schema'])) {
            $this->_schema = $params['Schema'];
        }
        if (isset($params['Table'])) {
            $this->_table = $params['Table'];
        }
        if (isset($params['Key_name'])) {
            $this->_name = $params['Key_name'];
        }
        if (isset($params['Index_type'])) {
            $this->_type = $params['Index_type'];
        }
        if (isset($params['Comment'])) {
            $this->_remarks = $params['Comment'];
        }
        if (isset($params['Index_comment'])) {
            $this->_comment = $params['Index_comment'];
        }
        if (isset($params['Non_unique'])) {
            $this->_non_unique = $params['Non_unique'];
        }
        if (isset($params['Packed'])) {
            $this->_packed = $params['Packed'];
        }
        if ('PRIMARY' == $this->_name) {
            $this->_choice = 'PRIMARY';
        } elseif ('FULLTEXT' == $this->_type) {
            $this->_choice = 'FULLTEXT';
        } elseif ('SPATIAL' == $this->_type) {
            $this->_choice = 'SPATIAL';
        } elseif ('0' == $this->_non_unique) {
            $this->_choice = 'UNIQUE';
        } else {
            $this->_choice = 'INDEX';
        }
    }

    public function getColumnCount()
    {
        return count($this->_columns);
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function getRemarks()
    {
        return $this->_remarks;
    }

    public function getComments()
    {
        $comments = $this->getRemarks();
        if (strlen($comments)) {
            $comments .= "\n";
        }
        $comments .= $this->getComment();

        return $comments;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getChoice()
    {
        return $this->_choice;
    }

    /**
     * Return a list of all index choices
     *
     * @return  array index choices
     */
    static public function getIndexChoices()
    {
        return array(
            'PRIMARY',
            'INDEX',
            'UNIQUE',
            'SPATIAL',
            'FULLTEXT',
        );
    }

    public function generateIndexSelector()
    {
        $html_options = '';

        foreach (PMA_Index::getIndexChoices() as $each_index_choice) {
            if ($each_index_choice === 'PRIMARY'
             && $this->_choice !== 'PRIMARY'
             && PMA_Index::getPrimary($this->_table, $this->_schema)) {
                // skip PRIMARY if there is already one in the table
                continue;
            }
            $html_options .= '<option value="' . $each_index_choice . '"'
                 . (($this->_choice == $each_index_choice) ? ' selected="selected"' : '')
                 . '>'. $each_index_choice . '</option>' . "\n";
        }

        return $html_options;
    }

    public function getPacked()
    {
        return $this->_packed;
    }

    public function isPacked($as_text = false)
    {
        if ($as_text) {
            $r = array(
                '0' => __('No'),
                '1' => __('Yes'),
            );
        } else {
            $r = array(
                '0' => false,
                '1' => true,
            );
        }

        if (null === $this->_packed) {
            return $r[0];
        }

        return $this->_packed;
    }

    public function getNonUnique()
    {
        return $this->_non_unique;
    }

    public function isUnique($as_text = false)
    {
        if ($as_text) {
            $r = array(
                '0' => __('Yes'),
                '1' => __('No'),
            );
        } else {
            $r = array(
                '0' => true,
                '1' => false,
            );
        }

        return $r[$this->_non_unique];
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = (string) $name;
    }

    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * Show index data
     *
     * @param string      $table          The tablename
     * @param array       $indexes_info   Referenced info array
     * @param array       $indexes_data   Referenced data array
     * @param boolean     $print_mode
     * @access  public
     * @return  array       Index collection array
     */
    static public function getView($table, $schema, $print_mode = false)
    {
        $indexes = PMA_Index::getFromTable($table, $schema);

        $no_indexes_class = count($indexes) > 0 ? ' hide' : '';
        $no_indexes  = "<div class='no_indexes_defined$no_indexes_class'>";
        $no_indexes .= PMA_Message::notice(__('No index defined!'))->getDisplay();
        $no_indexes .= '</div>';

        $r  = '<fieldset>';
        $r .= '<legend id="index_header">' . __('Indexes');
        $r .= PMA_showMySQLDocu('optimization', 'optimizing-database-structure');
        $r .= '</legend>';
        $r .= $no_indexes;
        if (count($indexes) < 1) {
            $r .= '</fieldset>';
            return $r;
        }
        if (! $print_mode) {
            $r .= PMA_Index::findDuplicates($table, $schema);
        }
        $r .= '<table id="table_index">';
        $r .= '<thead>';
        $r .= '<tr>';
        if (! $print_mode) {
            $r .= '<th colspan="2">' . __('Action') . '</th>';
        }
        $r .= '<th>' . __('Keyname') . '</th>';
        $r .= '<th>' . __('Type') . '</th>';
        $r .= '<th>' . __('Unique') . '</th>';
        $r .= '<th>' . __('Packed') . '</th>';
        $r .= '<th>' . __('Column') . '</th>';
        $r .= '<th>' . __('Cardinality') . '</th>';
        $r .= '<th>' . __('Collation') . '</th>';
        $r .= '<th>' . __('Null') . '</th>';
        $r .= '<th>' . __('Comment') . '</th>';
        $r .= '</tr>';
        $r .= '</thead>';
        $r .= '<tbody>';

        $odd_row = true;
        foreach ($indexes as $index) {
            $row_span = ' rowspan="' . $index->getColumnCount() . '" ';

            $r .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';

            if (! $print_mode) {
                $this_params = $GLOBALS['url_params'];
                $this_params['index'] = $index->getName();
                $r .= '<td class="edit_index ';
                if ($GLOBALS['cfg']['AjaxEnable']) {
                    $r .= 'ajax" ';
                }
                $r .= '" ' . $row_span . '>'
                   . '    <a href="tbl_indexes.php' . PMA_generate_common_url($this_params)
                   . '">' . PMA_getIcon('b_edit.png', __('Edit')) . '</a>'
                   . '</td>' . "\n";

                $this_params = $GLOBALS['url_params'];
                if ($index->getName() == 'PRIMARY') {
                    $this_params['sql_query'] = 'ALTER TABLE ' . PMA_backquote($table) . ' DROP PRIMARY KEY';
                    $this_params['message_to_show'] = __('The primary key has been dropped');
                    $js_msg      = PMA_jsFormat('ALTER TABLE ' . $table . ' DROP PRIMARY KEY');
                } else {
                    $this_params['sql_query'] = 'ALTER TABLE ' . PMA_backquote($table) . ' DROP INDEX ' . PMA_backquote($index->getName());
                    $this_params['message_to_show'] = sprintf(__('Index %s has been dropped'), $index->getName());
                    $js_msg      = PMA_jsFormat('ALTER TABLE ' . $table . ' DROP INDEX ' . $index->getName());
                }

                $r .= '<td ' . $row_span . '>';
                $r .= '<input type="hidden" class="drop_primary_key_index_msg" value="' . $js_msg . '" />';
                $r .= '    <a ';
                if ($GLOBALS['cfg']['AjaxEnable']) {
                    $r .= 'class="drop_primary_key_index_anchor" ';
                }
                $r .= ' href="sql.php' . PMA_generate_common_url($this_params)
                   . '" >'
                   . PMA_getIcon('b_drop.png', __('Drop'))  . '</a>'
                   . '</td>' . "\n";
            }

            $r .= '<th ' . $row_span . '>' . htmlspecialchars($index->getName()) . '</th>';
            $r .= '<td ' . $row_span . '>' . htmlspecialchars($index->getType()) . '</td>';
            $r .= '<td ' . $row_span . '>' . $index->isUnique(true) . '</td>';
            $r .= '<td ' . $row_span . '>' . $index->isPacked(true) . '</td>';

            foreach ($index->getColumns() as $column) {
                if ($column->getSeqInIndex() > 1) {
                    $r .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
                }
                $r .= '<td>' . htmlspecialchars($column->getName());
                if ($column->getSubPart()) {
                    $r .= ' (' . $column->getSubPart() . ')';
                }
                $r .= '</td>';
                $r .= '<td>' . htmlspecialchars($column->getCardinality()) . '</td>';
                $r .= '<td>' . htmlspecialchars($column->getCollation()) . '</td>';
                $r .= '<td>' . htmlspecialchars($column->getNull(true)) . '</td>';

                if ($column->getSeqInIndex() == 1) {
                    $r .= '<td ' . $row_span . '>'
                        . htmlspecialchars($index->getComments()) . '</td>';
                }
                $r .= '</tr>';
            } // end foreach $index['Sequences']

            $odd_row = ! $odd_row;
        } // end while
        $r .= '</tbody>';
        $r .= '</table>';
        $r .= '</fieldset>';

        return $r;
    }

    public function getCompareData()
    {
        $data = array(
            // 'Non_unique'    => $this->_non_unique,
            'Packed'        => $this->_packed,
            'Index_type'    => $this->_type,
        );

        foreach ($this->_columns as $column) {
            $data['columns'][] = $column->getCompareData();
        }

        return $data;
    }

    /**
     * Function to check over array of indexes and look for common problems
     *
     * @access  public
     * @param string      name of table
     * @return  string      Output HTML
     */
    static public function findDuplicates($table, $schema)
    {
        $indexes = PMA_Index::getFromTable($table, $schema);

        $output  = '';

        // count($indexes) < 2:
        //   there is no need to check if there less than two indexes
        if (count($indexes) < 2) {
            return $output;
        }

        // remove last index from stack and ...
        while ($while_index = array_pop($indexes)) {
            // ... compare with every remaining index in stack
            foreach ($indexes as $each_index) {
                if ($each_index->getCompareData() !== $while_index->getCompareData()) {
                    continue;
                }

                // did not find any difference
                // so it makes no sense to have this two equal indexes

                $message = PMA_Message::notice(__('The indexes %1$s and %2$s seem to be equal and one of them could possibly be removed.'));
                $message->addParam($each_index->getName());
                $message->addParam($while_index->getName());
                $output .= $message->getDisplay();

                // there is no need to check any further indexes if we have already
                // found that this one has a duplicate
                continue 2;
            }
        }
        return $output;
    }
}

/**
 * @package PhpMyAdmin
 */
class PMA_Index_Column
{
    /**
     * @var string The column name
     */
    protected $_name = '';

    /**
     * @var integer The column sequence number in the index, starting with 1.
     */
    protected $_seq_in_index = 1;

    /**
     * @var string How the column is sorted in the index. “A” (Ascending) or NULL (Not sorted)
     */
    protected $_collation = null;

    /**
     * The number of indexed characters if the column is only partly indexed,
     * NULL if the entire column is indexed.
     *
     * @var integer
     */
    protected $_sub_part = null;

    /**
     * Contains YES if the column may contain NULL.
     * If not, the column contains NO.
     *
     * @var string
     */
    protected $_null = '';

    /**
     * An estimate of the number of unique values in the index. This is updated
     * by running ANALYZE TABLE or myisamchk -a. Cardinality is counted based on
     * statistics stored as integers, so the value is not necessarily exact even
     * for small tables. The higher the cardinality, the greater the chance that
     * MySQL uses the index when doing joins.
     *
     * @var integer
     */
    protected $_cardinality = null;

    public function __construct($params = array())
    {
        $this->set($params);
    }

    public function set($params)
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

    public function getName()
    {
        return $this->_name;
    }

    public function getCollation()
    {
        return $this->_collation;
    }

    public function getCardinality()
    {
        return $this->_cardinality;
    }

    public function getNull($as_text = false)
    {
        return $as_text
            ? (!$this->_null || $this->_null == 'NO' ? __('No') : __('Yes'))
            : $this->_null;
    }

    public function getSeqInIndex()
    {
        return $this->_seq_in_index;
    }

    public function getSubPart()
    {
        return $this->_sub_part;
    }

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
?>
