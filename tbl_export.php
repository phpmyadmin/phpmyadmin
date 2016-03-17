<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table export
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\PageSettings;
use PMA\libraries\Response;

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/display_export.lib.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

PageSettings::showGroup('Export');

$response = PMA\libraries\Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

// Get the relation settings
$cfgRelation = PMA_getRelationsParam();

// handling export template actions
if (isset($_REQUEST['templateAction']) && $cfgRelation['exporttemplateswork']) {
    PMA_handleExportTemplateActions($cfgRelation);
    exit;
}

/**
 * Gets tables information and displays top links
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_export.php&amp;back=tbl_export.php';
require_once 'libraries/tbl_info.inc.php';

// Dump of a table

$export_page_title = __('View dump (schema) of table');

// When we have some query, we need to remove LIMIT from that and possibly
// generate WHERE clause (if we are asked to export specific rows)

if (! empty($sql_query)) {
    $parser = new SqlParser\Parser($sql_query);

    if ((!empty($parser->statements[0]))
        && ($parser->statements[0] instanceof SqlParser\Statements\SelectStatement)
    ) {

        // Finding aliases and removing them, but we keep track of them to be
        // able to replace them in select expression too.
        $aliases = array();
        foreach ($parser->statements[0]->from as $from) {
            if ((!empty($from->table)) && (!empty($from->alias))) {
                $aliases[$from->alias] = $from->table;
                // We remove the alias of the table because they are going to
                // be replaced anyway.
                $from->alias = null;
                $from->expr = null; // Force rebuild.
            }
        }

        // Rebuilding the SELECT and FROM clauses.
        $replaces = array(
            array(
                'FROM', 'FROM ' . SqlParser\Components\ExpressionArray::build(
                    $parser->statements[0]->from
                ),
            ),
        );

        // Checking if the WHERE clause has to be replaced.
        if ((!empty($where_clause)) && (is_array($where_clause))) {
            $replaces[] = array(
                'WHERE', 'WHERE (' . implode(') OR (', $where_clause) . ')'
            );
        }

        // Preparing to remove the LIMIT clause.
        $replaces[] = array('LIMIT', '');

        // Replacing the clauses.
        $sql_query = SqlParser\Utils\Query::replaceClauses(
            $parser->statements[0],
            $parser->list,
            $replaces
        );

        // Removing the aliases by finding the alias followed by a dot.
        $tokens = SqlParser\Lexer::getTokens($sql_query);
        foreach ($aliases as $alias => $table) {
            $tokens = SqlParser\Utils\Tokens::replaceTokens(
                $tokens,
                array(
                    array(
                        'value_str' => $alias,
                    ),
                    array(
                        'type' => SqlParser\Token::TYPE_OPERATOR,
                        'value_str' => '.',
                    )
                ),
                array(
                    new SqlParser\Token($table),
                    new SqlParser\Token('.',SqlParser\Token::TYPE_OPERATOR)
                )
            );
        }
        $sql_query = SqlParser\TokensList::build($tokens);
    }

    echo PMA\libraries\Util::getMessage(PMA\libraries\Message::success());
}

require_once 'libraries/display_export.lib.php';

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
    PMA_getExportDisplay(
        'table', $db, $table, $sql_query, $num_tables,
        $unlim_num_rows, $multi_values
    )
);
