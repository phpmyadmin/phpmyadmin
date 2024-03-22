<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

use function __;
use function htmlspecialchars;
use function is_array;
use function mb_strtolower;
use function min;
use function sprintf;
use function strlen;

/**
 * Displays table create form and handles it.
 */
class CreateController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Transformations $transformations,
        private Config $config,
        private DatabaseInterface $dbi,
        private ColumnsDefinition $columnsDefinition,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $this->checkParameters(['db'])) {
            return;
        }

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $cfg = $this->config->settings;

        /* Check if database name is empty */
        if (Current::$database === '') {
            Generator::mysqlDie(
                __('The database name is empty!'),
                '',
                false,
                'index.php',
            );
        }

        /**
         * Selects the database to work with
         */
        if (! $this->dbi->selectDb(Current::$database)) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars(Current::$database)),
                '',
                false,
                'index.php',
            );
        }

        if ($this->dbi->getColumns(Current::$database, Current::$table) !== []) {
            // table exists already
            Generator::mysqlDie(
                sprintf(__('Table %s already exists!'), htmlspecialchars(Current::$table)),
                '',
                false,
                Url::getFromRoute('/database/structure', ['db' => Current::$database]),
            );
        }

        $createAddField = new CreateAddField($this->dbi);

        $numFields = $this->getNumberOfFieldsFromRequest($request);

        /**
         * The form used to define the structure of the table has been submitted
         */
        if (isset($_POST['do_save_data'])) {
            if ($this->dbi->getLowerCaseNames() === 1) {
                Current::$database = mb_strtolower(Current::$database);
                Current::$table = mb_strtolower(Current::$table);
            }

            $GLOBALS['sql_query'] = $createAddField->getTableCreationQuery(Current::$database, Current::$table);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($GLOBALS['sql_query']);

                return;
            }

            // Executes the query
            $result = $this->dbi->tryQuery($GLOBALS['sql_query']);

            if ($result !== false) {
                // Update comment table for mime types [MIME]
                if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $cfg['BrowseMIME']) {
                    foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                        if (
                            ! isset($_POST['field_name'][$fieldindex])
                            || strlen($_POST['field_name'][$fieldindex]) <= 0
                        ) {
                            continue;
                        }

                        $this->transformations->setMime(
                            Current::$database,
                            Current::$table,
                            $_POST['field_name'][$fieldindex],
                            $mimetype,
                            $_POST['field_transformation'][$fieldindex],
                            $_POST['field_transformation_options'][$fieldindex],
                            $_POST['field_input_transformation'][$fieldindex],
                            $_POST['field_input_transformation_options'][$fieldindex],
                        );
                    }
                }
            } else {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $this->dbi->getError());
            }

            return;
        }

        // Do not display the table in the header since it hasn't been created yet
        $this->response->getHeader()->getMenu()->setTable('');

        $this->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        if (! $this->checkParameters(['server', 'db'])) {
            return;
        }

        $templateData = $this->columnsDefinition->displayForm($userPrivileges, '/table/create', $numFields);

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }

    /**
     * Function to get the number of fields for the table creation form
     */
    private function getNumberOfFieldsFromRequest(ServerRequest $request): int
    {
        $origNumFields = $request->getParsedBodyParam('orig_num_fields');
        $numFields = $request->getParsedBodyParam('num_fields');

        if ($request->hasBodyParam('submit_num_fields')) { // adding new fields
            $numberOfFields = (int) $origNumFields + (int) $request->getParsedBodyParam('added_fields');
        } elseif ($origNumFields !== null) { // retaining existing fields
            $numberOfFields = (int) $origNumFields;
        } elseif ($numFields !== null && (int) $numFields > 0) { // new table with specified number of fields
            $numberOfFields = (int) $numFields;
        } else { // new table with unspecified number of fields
            $numberOfFields = 4;
        }

        // Limit to 4096 fields (MySQL maximal value)
        return min($numberOfFields, 4096);
    }
}
