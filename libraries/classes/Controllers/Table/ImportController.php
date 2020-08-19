<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Url;

final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table, $url_query, $url_params, $SESSION_KEY;

        $pageSettings = new PageSettings('Import');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['import.js']);

        Common::table();

        $url_params['goto'] = Url::getFromRoute('/table/import');
        $url_params['back'] = Url::getFromRoute('/table/import');
        $url_query .= Url::getCommon($url_params, '&');

        [$SESSION_KEY, $uploadId] = ImportAjax::uploadProgressSetup();

        $importList = Plugins::getImport('table');

        if (empty($importList)) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!'
            ))->getDisplay());

            return;
        }

        $import = Import::get(
            'table',
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
