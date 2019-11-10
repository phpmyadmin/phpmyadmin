<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table export
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $containerBuilder, $db, $table, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

PageSettings::showGroup('Export');

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('export.js');

// Get the relation settings
/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$cfgRelation = $relation->getRelationsParam();

/** @var Export $displayExport */
$displayExport = $containerBuilder->get('display_export');

// handling export template actions
if (isset($_POST['templateAction']) && $cfgRelation['exporttemplateswork']) {
    $displayExport->handleTemplateActions($cfgRelation);
    exit;
}

/**
 * Gets tables information and displays top links
 */
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_export.php&amp;back=tbl_export.php';

// Dump of a table

$export_page_title = __('View dump (schema) of table');

// When we have some query, we need to remove LIMIT from that and possibly
// generate WHERE clause (if we are asked to export specific rows)

if (! empty($sql_query)) {
    $parser = new PhpMyAdmin\SqlParser\Parser($sql_query);

    if (! empty($parser->statements[0])
        && ($parser->statements[0] instanceof PhpMyAdmin\SqlParser\Statements\SelectStatement)
    ) {
        // Checking if the WHERE clause has to be replaced.
        if (! empty($where_clause) && is_array($where_clause)) {
            $replaces[] = [
                'WHERE',
                'WHERE (' . implode(') OR (', $where_clause) . ')',
            ];
        }

        // Preparing to remove the LIMIT clause.
        $replaces[] = [
            'LIMIT',
            '',
        ];

        // Replacing the clauses.
        $sql_query = PhpMyAdmin\SqlParser\Utils\Query::replaceClauses(
            $parser->statements[0],
            $parser->list,
            $replaces
        );
    }

    echo PhpMyAdmin\Util::getMessage(PhpMyAdmin\Message::success());
}

if (! isset($sql_query)) {
    $sql_query = '';
}
if (! isset($num_tables)) {
    $num_tables = 0;
}
if (! isset($unlim_num_rows)) {
    $unlim_num_rows = 0;
}
if (! isset($multi_values)) {
    $multi_values = '';
}
$response = Response::getInstance();
$response->addHTML(
    $displayExport->getDisplay(
        'table',
        $db,
        $table,
        $sql_query,
        $num_tables,
        $unlim_num_rows,
        $multi_values
    )
);
