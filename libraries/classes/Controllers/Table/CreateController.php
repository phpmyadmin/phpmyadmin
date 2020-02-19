<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Displays table create form and handles it.
 *
 * @package PhpMyAdmin\Controllers\Table
 */
class CreateController extends AbstractController
{
    /** @var Transformations */
    private $transformations;

    /** @var Config */
    private $config;

    /**
     * @param Response          $response        A Response instance.
     * @param DatabaseInterface $dbi             A DatabaseInterface instance.
     * @param Template          $template        A Template instance.
     * @param string            $db              Database name.
     * @param string            $table           Table name.
     * @param Transformations   $transformations A Transformations instance.
     * @param Config            $config          A Config instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Transformations $transformations,
        Config $config
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->transformations = $transformations;
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function index(): void
    {
        global $num_fields, $action, $sql_query, $result, $db, $table;

        Util::checkParameters(['db']);

        $cfg = $this->config->settings;

        /* Check if database name is empty */
        if (strlen($db) === 0) {
            Generator::mysqlDie(
                __('The database name is empty!'),
                '',
                false,
                'index.php'
            );
        }

        /**
         * Selects the database to work with
         */
        if (! $this->dbi->selectDb($db)) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
                '',
                false,
                'index.php'
            );
        }

        if ($this->dbi->getColumns($db, $table)) {
            // table exists already
            Generator::mysqlDie(
                sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
                '',
                false,
                Url::getFromRoute('/database/structure', ['db' => $db])
            );
        }

        $createAddField = new CreateAddField($this->dbi);

        // for libraries/tbl_columns_definition_form.inc.php
        // check number of fields to be created
        $num_fields = $createAddField->getNumberOfFieldsFromRequest();

        $action = Url::getFromRoute('/table/create');

        /**
         * The form used to define the structure of the table has been submitted
         */
        if (isset($_POST['do_save_data'])) {
            // lower_case_table_names=1 `DB` becomes `db`
            if ($this->dbi->getLowerCaseNames() === '1') {
                $db = mb_strtolower(
                    $db
                );
                $table = mb_strtolower(
                    $table
                );
            }
            $sql_query = $createAddField->getTableCreationQuery($db, $table);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($sql_query);
            }
            // Executes the query
            $result = $this->dbi->tryQuery($sql_query);

            if ($result) {
                // Update comment table for mime types [MIME]
                if (isset($_POST['field_mimetype'])
                    && is_array($_POST['field_mimetype'])
                    && $cfg['BrowseMIME']
                ) {
                    foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                        if (isset($_POST['field_name'][$fieldindex])
                            && strlen($_POST['field_name'][$fieldindex]) > 0
                        ) {
                            $this->transformations->setMime(
                                $db,
                                $table,
                                $_POST['field_name'][$fieldindex],
                                $mimetype,
                                $_POST['field_transformation'][$fieldindex],
                                $_POST['field_transformation_options'][$fieldindex],
                                $_POST['field_input_transformation'][$fieldindex],
                                $_POST['field_input_transformation_options'][$fieldindex]
                            );
                        }
                    }
                }
            } else {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $this->dbi->getError());
            }
            return;
        }

        // This global variable needs to be reset for the header class to function properly
        $table = '';

        /**
         * Displays the form used to define the structure of the table
         */
        require ROOT_PATH . 'libraries/tbl_columns_definition_form.inc.php';
    }
}
