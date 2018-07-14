<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\TableConstraintsController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\TableController;
use PhpMyAdmin\CheckConstraint;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Class TableConstraintsController
 *
 * @package PhpMyAdmin\Controllers
 */
class TableConstraintsController extends TableController
{
    /**
     * @var Constraint $constraint
     */
    protected $constraint;

    /**
     * Constructor
     *
     * @param Response                      $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     * @param string                        $db       Database name
     * @param string                        $table    Table name
     * @param Constraint                         $constraint    Constraint object
     */
    public function __construct(
        $response,
        $dbi,
        $db,
        $table,
        $constraint
    ) {
        parent::__construct($response, $dbi, $db, $table);

        $this->constraint = $constraint;
    }

    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        if (isset($_REQUEST['do_save_data'])) {
            $this->doSaveDataAction();
            return;
        } // end builds the new constraint

        $this->displayFormAction();
    }

    /**
     * Display the form to edit/create a constraint
     *
     * @return void
     */
    public function displayFormAction()
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $tables = $this->dbi->getTables($this->db);
        $tables_hashed = [];
        foreach ($tables as $table) {
            $tables_hashed[$table]['hash'] = md5($table);
            $tables_hashed[$table]['columns'] = array_keys(
                $this->dbi->getColumns($this->db, $table)
            );
        }
        $this->response->getHeader()->getScripts()->addFiles([
            'vendor/jquery/jquery.md5.js',
            'check_constraint.js'
        ]);
        if(isset($_REQUEST['edit_constraint'])) {
            $this->response->addHTML(
                $this->template->render('table/constraint_form', [
                    'db' => $this->db,
                    'table' => $this->table,
                    'constraint' => $this->constraint[0],
                    'tables' => $tables_hashed,
                    'default_no_of_columns' => count(json_decode($this->constraint[0]['columns']))-1,
                    'edit_constraint' => 1
                ])
            );
        } else if(isset($_REQUEST['create_constraint'])) {
            $this->response->addHTML(
                $this->template->render('table/constraint_form', [
                    'db' => $this->db,
                    'table' => $this->table,
                    'tables' => $tables_hashed,
                    'default_no_of_columns' => 1,
                    'create_constraint' => 1
                ])
            );
        } else if(isset($_REQUEST['drop_constraint'])) {
            $sql_query = " ALTER TABLE " . Util::backquote($this->table) . " DROP CONSTRAINT " . Util::backquote($_REQUEST['constraint']);
            $success = $this->dbi->tryQuery($sql_query);
            $message = '';
            if (! $success) {
                $message = Message::error($this->dbi->getError());
                $this->response->setRequestStatus(false);
            } else {
                CheckConstraint::removeFromDb($_REQUEST['constraint'], $this->table, $this->db);
                $message = Message::success(
                    __('Table %1$s has been altered successfully.')
                );
            }
            $message->addParam($this->table);
            $this->response->addJSON(
                'message',
                $message
            );
        }
    }

    /**
     * Process the data from the edit/create constraint form,
     * run the query to build the new constraint
     * and moves back to "tbl_structure.php"
     *
     * @return void
     */
    public function doSaveDataAction()
    {
        $error = false;
        $this->dbi->selectDb($GLOBALS['db']);
        $sql_query = '';
        $param = $_REQUEST['const'];
        $const = new CheckConstraint($param);
        $create = isset($_REQUEST['create_constraint_submit']) ? 1 : 0;
        $sql_query = $const->getSqlQueryForCreateOrEdit($create);

        // If there is a request for SQL previewing.
        if (isset($_REQUEST['preview_sql'])) {
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sql_query])
            );
        } else if (!$error) {
            $success = $this->dbi->tryQuery($sql_query);
            $message = '';
            if (! $success) {
                $message = Message::error($this->dbi->getError());
                $this->response->setRequestStatus(false);
            } else {
                if($create) {
                    $const->saveToDb();
                } else {
                    $const->changeInDb();
                }
                $message = Message::success(
                    __('Table %1$s has been altered successfully.')
                );
            }
            $message->addParam($this->table);
            $this->response->addJSON(
                'message',
                $message
            );
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);
        }
    }
}
