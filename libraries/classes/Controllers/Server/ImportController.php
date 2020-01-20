<?php
/**
 * @package PhpMyAdmin\Controllers\Server
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Display\Import;

/**
 * @package PhpMyAdmin\Controllers\Server
 */
final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table;

        PageSettings::showGroup('Import');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('import.js');

        Common::server();

        $this->response->addHTML(Import::get(
            'server',
            $db,
            $table,
            $max_upload_size
        ));
    }
}
