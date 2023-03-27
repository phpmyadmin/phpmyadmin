<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function count;
use function is_array;
use function is_numeric;
use function json_decode;
use function min;

/**
 * Displays index edit/creation form and handles it.
 */
class IndexesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Indexes $indexes,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        if (! isset($_POST['create_edit_table'])) {
            $this->checkParameters(['db', 'table']);

            $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

            DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);
        }

        if (isset($_POST['index'])) {
            if (is_array($_POST['index'])) {
                // coming already from form
                $index = new Index($_POST['index']);
            } else {
                $index = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->getIndex($_POST['index']);
            }
        } else {
            $index = new Index();
        }

        if (isset($_POST['do_save_data'])) {
            $this->indexes->doSaveData($index, false, $GLOBALS['db'], $GLOBALS['table']);

            return;
        }

        $this->displayForm($index);
    }

    /**
     * Display the form to edit/create an index
     *
     * @param Index $index An Index instance.
     */
    private function displayForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $addFields = 0;
        if (isset($_POST['index']) && is_array($_POST['index'])) {
            // coming already from form
            if (isset($_POST['index']['columns']['names'])) {
                $addFields = count($_POST['index']['columns']['names'])
                    - $index->getColumnCount();
            }

            if (isset($_POST['add_fields'])) {
                $addFields += $_POST['added_fields'];
            }
        } elseif (isset($_POST['create_index'])) {
            /**
             * In most cases, an index may consist of up to 16 columns, so add an initial limit.
             * More columns could be added later if necessary.
             *
             * @see https://dev.mysql.com/doc/refman/5.6/en/multiple-column-indexes.html "up to 16 columns"
             * @see https://mariadb.com/kb/en/innodb-limitations/#limitations-on-schema "maximum of 16 columns"
             * @see https://mariadb.com/kb/en/myisam-overview/#myisam-features "Maximum of 32 columns per index"
             */
            $addFields = 1;
            if (is_numeric($_POST['added_fields']) && $_POST['added_fields'] >= 2) {
                $addFields = min((int) $_POST['added_fields'], 16);
            }
        }

        // Get fields and stores their name/type
        if (isset($_POST['create_edit_table'])) {
            $fields = json_decode($_POST['columns'], true);
            $indexParams = ['Non_unique' => $_POST['index']['Index_choice'] !== 'UNIQUE'];
            $index->set($indexParams);
            $addFields = count($fields);
        } else {
            $fields = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table'])
                ->getNameAndTypeOfTheColumns();
        }

        $formParams = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];

        if (isset($_POST['create_index'])) {
            $formParams['create_index'] = 1;
        } elseif (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->render('table/index_form', [
            'fields' => $fields,
            'index' => $index,
            'form_params' => $formParams,
            'add_fields' => $addFields,
            'create_edit_table' => isset($_POST['create_edit_table']),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            'is_from_nav' => isset($_POST['is_from_nav']),
        ]);
    }
}
