<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the table check constraints class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Message;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Relation;

/**
 * Check Constraint manipulation class
 *
 * @package PhpMyAdmin
 * @since   phpMyAdmin
 */
class CheckConstraint
{
    /**
     * Class-wide storage container for check constraints
     *
     * @var array
     */
    private static $_registry = [];

    /**
     * @var string The name of the schema
     */
    private $db_name = '';

    /**
     * @var string The name of the table
     */
    private $table_name = '';

    /**
     * @var string The name of the constraint
     */
    private $const_name = '';

    /**
     * @var string All the columns in the constraint
     */
    private $columns = '';

    /**
     * @var string logical operators in the constraint
     */
    private $logical_op = '';

    /**
     * @var string criteria operators in the constraint
     */
    private $criteria_op = '';

    /**
     * @var string Various criteria for rhs values in the constraint
     */
    private $criteria_rhs = '';

    /**
     * @var string rhs text values the constraint
     */
    private $rhs_text_val = '';

    /**
     * @var string name of tables refered in the constraint
     */
    private $tableNameSelect = '';

    /**
     * @var string name of columns refered in the constraint
     */
    private $columnNameSelect = '';

    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param array $params parameters
     */
    public function __construct(array $params = [])
    {
        $this->set($params);
        $this->relation = new Relation();
    }

    /**
     * returns an array with all check constraints from the given table
     *
     * @param string $table  table
     * @param string $schema schema
     *
     * @return Check[]  array of check constraints
     */
    public static function getFromTable($table, $schema)
    {
        CheckConstraint::_loadCCs($table, $schema);

        if (isset(CheckConstraint::$_registry[$schema][$table])) {
            return CheckConstraint::$_registry[$schema][$table];
        }

        return [];
    }

    /**
     * Returns the name of the constraint
     *
     * @return string the name of the constraint
     */
    public function getName()
    {
        return $this->const_name;
    }

    /**
     * Returns the names of all the columns in the constraint
     *
     * @return string
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Returns the logical operators in the constraint
     *
     * @return string
     */
    public function getLogical_op()
    {
        return $this->logical_op;
    }

    /**
     * Returns the criteria operators in the constraint
     *
     * @return string
     */
    public function getCriteria_op()
    {
        return $this->criteria_op;
    }

    /**
     * Returns the criteria rhs type in the constraint
     *
     * @return string
     */
    public function getCriteria_rhs()
    {
        return $this->criteria_rhs;
    }

    /**
     * Returns the rhs values in the constraint
     *
     * @return string
     */
    public function getText()
    {
        return $this->rhs_text_val;
    }

    /**
     * Returns the rhs table names in the constraint
     *
     * @return string
     */
    public function getTableNameSelect()
    {
        return $this->tableNameSelect;
    }

    /**
     * Returns the rhs column names in the constraint
     *
     * @return string
     */
    public function getColumnNameSelect()
    {
        return $this->columnNameSelect;
    }

    /**
     * Returns the table name of the constraint
     *
     * @return string
     */
    public function getTbl()
    {
        return $this->table_name;
    }

    /**
     * Returns the database name of the constraint
     *
     * @return string
     */
    public function getDb()
    {
        return $this->db_name;
    }

    /**
     * Load data for table
     *
     * @param string $table  table
     * @param string $schema schema
     *
     * @return boolean whether loading was successful
     */
    private static function _loadCCs($table, $schema)
    {
        if (isset(CheckConstraint::$_registry[$schema][$table])) {
            return true;
        }

        $_raw_ccs = self::getFromDb($table, $schema);
        foreach ($_raw_ccs as $_each_cc) {
            $constraintName = $_each_cc['const_name'];
            if (! isset(CheckConstraint::$_registry[$schema][$table][$constraintName])) {
                $constraint = new CheckConstraint($_each_cc);
                CheckConstraint::$_registry[$schema][$table][$constraintName] = $constraint;
            } else {
                $constraint = CheckConstraint::$_registry[$schema][$table][$constraintName];
            }

            // $constraint->addColumns($_each_cc);
        }

        return true;
    }

    /**
     * Sets contraint details
     *
     * @param array $params constraint details
     *
     * @return void
     */
    public function set(array $params)
    {
        if (isset($params['const_name'])) {
            $this->const_name = $params['const_name'];
        }
        if (isset($params['columns'])) {
            if(!is_array($params['columns'])) {
                $this->columns = json_decode($params['columns']);
            } else {
                $this->columns = $params['columns'];
            }
        }
        if (isset($params['db_name'])) {
            $this->db_name = $params['db_name'];
        }
        if (isset($params['table_name'])) {
            $this->table_name = $params['table_name'];
        }
        if(isset($params['logical_op'])) {
            if(!is_array($params['logical_op'])) {
                $this->logical_op = json_decode($params['logical_op']);
            } else {
                $this->logical_op = $params['logical_op'];
            }
        }
        if(isset($params['criteria_op'])) {
            if(!is_array($params['criteria_op'])) {
                $this->criteria_op = json_decode($params['criteria_op']);
            } else {
                $this->criteria_op = $params['criteria_op'];
            }
        }
        if(isset($params['criteria_rhs'])) {
            if(!is_array($params['criteria_rhs'])) {
                $this->criteria_rhs = json_decode($params['criteria_rhs']);
            } else {
                $this->criteria_rhs = $params['criteria_rhs'];
            }
        }
        if(isset($params['rhs_text_val'])) {
            if(!is_array($params['rhs_text_val'])) {
                $this->rhs_text_val = json_decode($params['rhs_text_val']);
            } else {
                $this->rhs_text_val = $params['rhs_text_val'];
            }
        }
        if(isset($params['tableNameSelect'])) {
            if(!is_array($params['tableNameSelect'])) {
               $this->tableNameSelect = json_decode($params['tableNameSelect']);
            } else {
                $this->tableNameSelect = $params['tableNameSelect'];
            }
        }
        if(isset($params['columnNameSelect'])) {
            if(!is_array($params['columnNameSelect'])) {
                $this->columnNameSelect = json_decode($params['columnNameSelect']);
            } else {
                $this->columnNameSelect = $params['columnNameSelect'];
            }
        }
    }

    /*
     * HTML for check constraint statement
     *
     * @param $constraint Constraint object
     *
     * @return HTML for check constraint statement
     */
    public static function generateConstraintStatement($constraint)
    {
        $constraintName = $constraint->getName();
        $columnNames = $constraint->getColumns();
        $logical_op = $constraint->getLogical_op();
        $criteria_op = $constraint->getCriteria_op();
        $criteria_rhs = $constraint->getCriteria_rhs();
        $tableNameSelect = $constraint->getTableNameSelect();
        $columnNameSelect = $constraint->getColumnNameSelect();
        $rhs_text_val = $constraint->getText();
        $definition = 'CONSTRAINT ' . Util::backquote($constraintName) . ' CHECK (';
        for($i=0; $i<count($columnNames); ++$i) {
            if($i>0) {
                $definition .= ' ' . $logical_op[$i-1] . ' ';
            }
            $columnNames[$i] = trim($columnNames[$i]);
            $definition .= Util::backquote($columnNames[$i]);
            $definition .= ' ' . $criteria_op[$i];
            if($criteria_rhs[$i] === 'text') {
                $definition .= ' \'' . $rhs_text_val[$i] . '\'';
            } else if($criteria_rhs[$i] === 'anotherColumn') {
                $definition .= ' ' . Util::backquote($tableNameSelect[$i]) . '.' . Util::backquote($columnNameSelect[$i]);
            }
        }
        $definition .= ')';
        return $definition;
    }

    /**
     * Get HTML to display constraints
     *
     * @return string $html_output
     */
    public static function getHtmlForDisplayCCs()
    {
        $html_output = '<div id="cc_div" class="width100 ajax" >';
        $html_output .= self::getHtmlForCCs(
            $GLOBALS['table'],
            $GLOBALS['db']
        );
        $html_output .= '<fieldset class="tblFooters print_ignore" style="text-align: '
            . 'left;"><form action="tbl_constraints.php" method="post">';
        $html_output .= Url::getHiddenInputs(
            $GLOBALS['db'],
            $GLOBALS['table']
        );
        $html_output .= sprintf(__('Add a new Check Constraint'));
        $html_output .= '<input type="hidden" name="create_constraint" value="1" />'
            . '<input class="add_cc ajax"'
            . ' type="submit" value="' . __('Go') . '" />';

        $html_output .= '</form>'
            . '</fieldset>'
            . '</div>';

        return $html_output;
    }

    /**
     * Show Check Constraint data
     *
     * @param string  $table      The table name
     * @param string  $schema     The schema name
     * @param boolean $print_mode Whether the output is for the print mode
     *
     * @return string HTML for showing check constraint
     *
     * @access  public
     */
    public static function getHtmlForCCs($table, $schema, $print_mode = false)
    {
        $constraints = CheckConstraint::getFromTable($table, $schema);
        $no_constraints_class = count($constraints) > 0 ? ' hide' : '';
        $no_constraints  = "<div class='no_constraints_defined$no_constraints_class'>";
        $no_constraints .= Message::notice(__('No constraint defined!'))->getDisplay();
        $no_constraints .= '</div>';

        if (! $print_mode) {
            $r  = '<fieldset class="constraint_info">';
            $r .= '<legend id="constraint_header">' . __('Constraints');
            // Todo : Link proper documentation

            $r .= '</legend>';
            $r .= $no_constraints;
            if (count($constraints) < 1) {
                $r .= '</fieldset>';
                return $r;
            }
        } else {
            $r  = '<h3>' . __('Constraints') . '</h3>';
            $r .= $no_constraints;
            if (count($constraints) < 1) {
                return $r;
            }
        }
        $r .= '<div class="responsivetable jsresponsive">';
        $r .= '<table id="table_constraint">';
        $r .= '<thead>';
        $r .= '<tr>';
        $r .= '<th colspan="2" class="print_ignore">' . __('Action') . '</th>';
        $r .= '<th>' . __('Name') . '</th>';
        $r .= '<th>' . __('Description') . '</th>';
        $r .= '</tr>';
        $r .= '</thead>';
        $r .= '<tbody class="row_span">';
        foreach ($constraints as $constraint) {
            $r .= '<tr class="noclick" >';

            if (! $print_mode) {
                $this_params = $GLOBALS['url_params'];
                $this_params['constraint'] = $constraint->getName();
                $this_params['edit_constraint'] = 1;
                $this_params['ajax_request'] = 1;
                $r .= '<td class="edit_constraint print_ignore ajax">'
                   . ' <a class="';
                $r .= 'ajax edit_constraint_anchor';
                $r .= '" href="tbl_constraints.php" data-post="' . Url::getCommon($this_params)
                   . '">' . Util::getIcon('b_edit', __('Edit')) . '</a>'
                   . '</td>' . "\n";
                $this_params = $GLOBALS['url_params'];
                $this_params['drop_constraint'] = 1;
                $this_params['constraint'] = $constraint->getName();
                $this_params['sql_query'] = 'ALTER TABLE '
                    . Util::backquote($table) . ' DROP constraint '
                    . Util::backquote($constraint->getName()) . ';';
                $this_params['message_to_show'] = sprintf(
                    __('constraint %s has been dropped.'),
                    htmlspecialchars($constraint->getName())
                );
                $js_msg = Sanitize::jsFormat($this_params['sql_query']);

                $r .= '<td class="print_ignore">';
                $r .= '<input type="hidden" class="drop_constraint_msg"'
                    . ' value="' . $js_msg . '" />';
                $r .= Util::linkOrButton(
                    'tbl_constraints.php' . Url::getCommon($this_params),
                    Util::getIcon('b_drop', __('Drop')),
                    ['class' => 'drop_constraint_anchor ajax']
                );
                $r .= '</td>' . "\n";
            }
            $r .= '<td>' . $constraint->getName() . '</td>';
            $r .= '<td>'
                . self::generateConstraintStatement($constraint)
                . '</td>';
            $r .= '</tr>';
        } // end while
        $r .= '</tbody>';
        $r .= '</table>';
        $r .= '</div>';
        if (! $print_mode) {
            $r .= '</fieldset>';
        }

        return $r;
    }

    /**
     * Returns CHECK Constraint(s) defined on the table
     * @param string $table    table
     * @param string $db database
     * @param string $constName Name of the constraint to be fetched from db, if absent, all constraints are fetched
     *
     * @return array
     */
    public static function getFromDb($table, $db, $constName='')
    {
        // Read from phpMyAdmin database
        $tmp = new CheckConstraint();
        $sql_query
            = " SELECT * FROM " . $tmp->_getPmaTable() .
            " WHERE `db_name` = '" . $db . "' AND `table_name` = '" . $table . "'";
        if($constName !== '') {
            $sql_query .= " AND `const_name` = '" . $constName . "'";
        }
        $sql_query .= ";";
        $result = $GLOBALS['dbi']->fetchResult($sql_query, null, null, DatabaseInterface::CONNECT_CONTROL);
        if (! is_array($result) || count($result) < 1) {
            return [];
        }
        return $result;
    }

    /**
     * Prepare request data for insertion.
     *
     * @return void
     */
    public static function prepareData()
    {
        $_REQUEST['const']['const_name'] = trim($_REQUEST['const']['const_name']) === '' ? 'CHECK_CONSTRAINT1' : trim($_REQUEST['const']['const_name']);
        // Discarding the first element as it is from the template.
        array_shift($_REQUEST['const']['columns']);
        array_shift($_REQUEST['const']['logical_op']);
        array_shift($_REQUEST['const']['criteria_op']);
        array_shift($_REQUEST['const']['criteria_rhs']);
        array_shift($_REQUEST['const']['tableNameSelect']);
        array_shift($_REQUEST['const']['columnNameSelect']);
        array_shift($_REQUEST['const']['rhs_text_val']);
    }

    /**
     * Encode Data to be saved in the db
     * @param string $str String to be encoded
     *
     * @return string
     */
    public function encodeString($str)
    {
        return $GLOBALS['dbi']->escapeString(json_encode($str));
    }

    /**
     * Save CHECK contraints to phpMyAdmin database.
     * @param boolean $create_table True if called from tbl_create.php
     *
     * @return void
     */
    public function saveToDb($create_table = false)
    {
        $constraintName = $this->getName();
        $columnNames = $this->encodeString($this->getColumns());
        $logical_op = $this->encodeString($this->getLogical_op());
        $criteria_op = $this->encodeString($this->getCriteria_op());
        $criteria_rhs = $this->encodeString($this->getCriteria_rhs());
        $tableNameSelect = $this->encodeString($this->getTableNameSelect());
        $columnNameSelect = $this->encodeString($this->getColumnNameSelect());
        $rhs_text_val = $this->encodeString($this->getText());
        $table = $this->getTbl();
        $db = $this->getDb();

        $sql_query
            = " INSERT INTO " . $this->_getPmaTable() .
                " VALUES('" . $constraintName . "', '" . $table . "', '" . $db . "', '" . $columnNames . "', '" . $logical_op . "', '" . $criteria_op ."', '" . $criteria_rhs . "', '" . $rhs_text_val . "', '" . $tableNameSelect . "', '" .
                $columnNameSelect . "');";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);
        if (! $success) {
            $error_msg = $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL);
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $error_msg);
        } else if(! $create_table){
            $sql_query
                = " ALTER TABLE " . Util::backquote($table) .
                " ADD " . CheckConstraint::generateConstraintStatement($this);
            $success = $GLOBALS['dbi']->tryQuery($sql_query);
            if (! $success) {
                $sql_query
                = " DELETE FROM " . $this->_getPmaTable() .
                " WHERE `db_name` = '" . $db . "' AND `table_name` = '" . $table . "' AND" .
                " `const_name` = '" . $constraintName . "';";

                $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);
                $error_msg = __("Unable to add constraint. ") . $GLOBALS['dbi']->getError();
                $response = Response::getInstance();
                $response->setRequestStatus(false);
                $response->addJSON('message', $error_msg);
            }
        }
    }

    /**
     * Change CHECK contraints in phpMyAdmin database.
     *
     * @return
     */
    public function changeInDb()
    {
        $constraintName = $this->getName();
        $columnNames = $this->encodeString($this->getColumns());
        $logical_op = $this->encodeString($this->getLogical_op());
        $criteria_op = $this->encodeString($this->getCriteria_op());
        $criteria_rhs = $this->encodeString($this->getCriteria_rhs());
        $tableNameSelect = $this->encodeString($this->getTableNameSelect());
        $columnNameSelect = $this->encodeString($this->getColumnNameSelect());
        $rhs_text_val = $this->encodeString($this->getText());
        $table = $this->getTbl();
        $db = $this->getDb();

        $sql_query
            = " INSERT INTO " . $this->_getPmaTable() .
                " VALUES('" . $constraintName . "', '" . $table . "', '" . $db . "', '" . $columnNames . "', '" . $logical_op . "', '" . $criteria_op ."', '" . $criteria_rhs . "', '" . $rhs_text_val . "', '" . $tableNameSelect . "', '" .
                $columnNameSelect . "');";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);
        if (! $success) {
            $error_msg = $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL);
            $response = Response::getInstance();
            $response->setRequestStatus(false);
            $response->addJSON('message', $error_msg);
        } else {
            $sql_query
                = " DELETE FROM " . $this->_getPmaTable() .
                " WHERE `db_name` = '" . $db . "' AND `table_name` = '" . $table . "' AND" .
                " `const_name` = '" . $_REQUEST['old_const'] . "';";

            $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

            $sql_query
                = " ALTER TABLE " . Util::backquote($table) . " DROP CONSTRAINT " . Util::backquote($_REQUEST['old_const']) .
                ", ADD " . CheckConstraint::generateConstraintStatement($this);
            $GLOBALS['dbi']->tryQuery($sql_query);
        }
    }

    /**
     * Removes Constraint from phpmyadmin database
     * @param string $constName
     * @param string $table
     * @param string $database
     *
     * @return void
     */
    public static function removeFromDb($constName, $table, $db)
    {
        $tmp = new CheckConstraint();
        $sql_query
            = " DELETE FROM " . $tmp->_getPmaTable() .
                " WHERE `db_name` = '" . $db . "' AND `table_name` = '" . $table . "' AND" .
                " `const_name` = '" . $constName . "'";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $error_msg = __('Could not drop Constraint!');
            $message = Message::error($error_msg);
            $message->addMessage(
                Message::rawError(
                    $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL)
                ),
                '<br /><br />'
            );
            return $message;
        } else {
            $sql_query = " ALTER TABLE " . Util::backquote($table) . " DROP CONSTRAINT " . Util::backquote($constName);
            $GLOBALS['dbi']->tryQuery($sql_query);
        }
    }

    /**
     * Return the name of the configuration storage table
     *
     * @return string pma table name
     */
    private function _getPmaTable()
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! empty($cfgRelation['db'])
            && ! empty($cfgRelation['check_constraints'])
        ) {
            return Util::backquote($cfgRelation['db']) . "."
                . Util::backquote($cfgRelation['check_constraints']);
        }
        return null;
    }
}
