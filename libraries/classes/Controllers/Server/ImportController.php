<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Display\Import;

final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table;

        $pageSettings = new PageSettings('Import');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->addScriptFiles(['import.js']);

        Common::server();

        $this->response->addHTML(Import::get(
            'server',
            $db,
            $table,
            $max_upload_size
        ));
    }
}
