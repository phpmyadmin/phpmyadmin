<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Display\Import;
use PhpMyAdmin\Util;

final class ImportController extends AbstractController
{
    public function index(): void
    {
        global $db, $max_upload_size, $table, $tables, $num_tables, $total_num_tables, $is_show_stats;
        global $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos, $sub_part;

        PageSettings::showGroup('Import');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('import.js');

        /**
         * Gets tables information and displays top links
         */
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

        $this->response->addHTML(
            Import::get(
                'database',
                $db,
                $table,
                $max_upload_size
            )
        );
    }
}
