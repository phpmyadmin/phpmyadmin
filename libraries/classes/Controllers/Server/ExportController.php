<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

final class ExportController extends AbstractController
{
    /** @var Export */
    private $export;

    /**
     * @param Response          $response A Response instance.
     * @param DatabaseInterface $dbi      A DatabaseInterface instance.
     * @param Template          $template A Template instance.
     * @param Export            $export   A Export instance.
     */
    public function __construct($response, $dbi, Template $template, Export $export)
    {
        parent::__construct($response, $dbi, $template);
        $this->export = $export;
    }

    public function index(): void
    {
        global $db, $table, $sql_query, $num_tables, $unlim_num_rows;
        global $tmp_select, $select_item, $multi_values, $export_page_title;

        Common::server();

        PageSettings::showGroup('Export');

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('export.js');

        $export_page_title = __('View dump (schema) of databases') . "\n";

        $select_item = $tmp_select ?? '';
        $multi_values = $this->export->getHtmlForSelectOptions($select_item);

        if (! isset($sql_query)) {
            $sql_query = '';
        }
        if (! isset($num_tables)) {
            $num_tables = 0;
        }
        if (! isset($unlim_num_rows)) {
            $unlim_num_rows = 0;
        }

        $this->response->addHTML($this->export->getDisplay(
            'server',
            $db,
            $table,
            $sql_query,
            $num_tables,
            $unlim_num_rows,
            $multi_values
        ));
    }
}
