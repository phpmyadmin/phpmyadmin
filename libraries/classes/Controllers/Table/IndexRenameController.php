<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function is_array;

final class IndexRenameController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Indexes */
    private $indexes;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi,
        Indexes $indexes
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
        $this->indexes = $indexes;
    }

    public function __invoke(): void
    {
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

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
            $this->indexes->doSaveData($index, true, $GLOBALS['db'], $GLOBALS['table']);

            return;
        }

        $this->displayRenameForm($index);
    }

    /**
     * Display the rename form to rename an index
     *
     * @param Index $index An Index instance.
     */
    private function displayRenameForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);

        $formParams = [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
        ];

        if (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->addScriptFiles(['indexes.js']);

        $this->render('table/index_rename_form', [
            'index' => $index,
            'form_params' => $formParams,
        ]);
    }
}
