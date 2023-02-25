<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;
use function is_array;
use function mb_strtolower;
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
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->checkParameters(['db']);

        $cfg = $this->config->settings;

        /* Check if database name is empty */
        if ($GLOBALS['db'] === '') {
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
        if (! $this->dbi->selectDb($GLOBALS['db'])) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($GLOBALS['db'])),
                '',
                false,
                'index.php',
            );
        }

        if ($this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'])) {
            // table exists already
            Generator::mysqlDie(
                sprintf(__('Table %s already exists!'), htmlspecialchars($GLOBALS['table'])),
                '',
                false,
                Url::getFromRoute('/database/structure', ['db' => $GLOBALS['db']]),
            );
        }

        $createAddField = new CreateAddField($this->dbi);

        $numFields = $createAddField->getNumberOfFieldsFromRequest();

        /**
         * The form used to define the structure of the table has been submitted
         */
        if (isset($_POST['do_save_data'])) {
            if ($this->dbi->getLowerCaseNames() === 1) {
                $GLOBALS['db'] = mb_strtolower($GLOBALS['db']);
                $GLOBALS['table'] = mb_strtolower($GLOBALS['table']);
            }

            $GLOBALS['sql_query'] = $createAddField->getTableCreationQuery($GLOBALS['db'], $GLOBALS['table']);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                Core::previewSQL($GLOBALS['sql_query']);

                return;
            }

            // Executes the query
            $result = $this->dbi->tryQuery($GLOBALS['sql_query']);

            if ($result) {
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
                            $GLOBALS['db'],
                            $GLOBALS['table'],
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

        $this->checkParameters(['server', 'db']);

        $templateData = $this->columnsDefinition->displayForm('/table/create', $numFields);

        $this->render('columns_definitions/column_definitions_form', $templateData);
    }
}
