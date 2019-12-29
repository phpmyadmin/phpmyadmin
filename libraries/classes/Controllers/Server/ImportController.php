<?php
/**
 * @package PhpMyAdmin\Controllers\Server
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Display\Import;

/**
 * @package PhpMyAdmin\Controllers\Server
 */
final class ImportController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        global $db, $max_upload_size, $table;

        PageSettings::showGroup('Import');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('import.js');

        require ROOT_PATH . 'libraries/server_common.inc.php';

        $this->response->addHTML(Import::get(
            'server',
            $db,
            $table,
            $max_upload_size
        ));
    }
}
