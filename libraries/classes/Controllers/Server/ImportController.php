<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;

final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table, $SESSION_KEY;

        $pageSettings = new PageSettings('Import');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['import.js']);

        Common::server();

        [$SESSION_KEY, $uploadId] = ImportAjax::uploadProgressSetup();

        $importList = Plugins::getImport('server');

        if (empty($importList)) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!'
            ))->getDisplay());

            return;
        }

        $import = Import::get(
            'server',
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
