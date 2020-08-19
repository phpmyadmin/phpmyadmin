<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Util;

final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table, $tables, $num_tables, $total_num_tables, $is_show_stats;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $sub_part, $SESSION_KEY;

        $pageSettings = new PageSettings('Import');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['import.js']);

        Common::database();

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,
            $is_show_stats,
            $db_is_system_schema,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part ?? '');

        [$SESSION_KEY, $uploadId] = ImportAjax::uploadProgressSetup();

        $importList = Plugins::getImport('database');

        if (empty($importList)) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!'
            ))->getDisplay());

            return;
        }

        $import = Import::get(
            'database',
            $db,
            $table,
            $max_upload_size,
            $SESSION_KEY,
            $uploadId,
            $importList
        );

        $this->render('display/import/import', $import);
    }
}
