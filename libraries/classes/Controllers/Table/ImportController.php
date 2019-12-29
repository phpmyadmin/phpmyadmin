<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Url;

/**
 * @package PhpMyAdmin\Controllers\Table
 */
final class ImportController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        global $db, $max_upload_size, $table, $url_query, $url_params;

        PageSettings::showGroup('Import');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('import.js');

        /**
         * Gets tables information and displays top links
         */
        require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

        $url_params['goto'] = Url::getFromRoute('/table/import');
        $url_params['back'] = Url::getFromRoute('/table/import');
        $url_query .= Url::getCommon($url_params, '&');

        $this->response->addHTML(Import::get(
            'table',
            $db,
            $table,
            $max_upload_size
        ));
    }
}
