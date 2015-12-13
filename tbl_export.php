<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table export
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/page_settings.class.php';
require_once 'libraries/display_export.lib.php';

PMA_PageSettings::showGroup('Export');

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

// Get the relation settings
$cfgRelation = PMA_getRelationsParam();

// handling export template actions
if (isset($_REQUEST['templateAction']) && $cfgRelation['exporttemplateswork']) {

    if (isset($_REQUEST['templateId'])) {
        $templateId = $_REQUEST['templateId'];
        $id = PMA_Util::sqlAddSlashes($templateId);
    }

    $templateTable = PMA_Util::backquote($cfgRelation['db']) . '.'
       . PMA_Util::backquote($cfgRelation['export_templates']);
    $user = PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']);

    switch ($_REQUEST['templateAction']) {
    case 'create':
        $query = "INSERT INTO " . $templateTable . "("
            . " `username`, `export_type`,"
            . " `template_name`, `template_data`"
            . ") VALUES ("
            . "'" . $user . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['exportType']) . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateName']) . "', "
            . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateData']) . "');";
        break;
    case 'load':
        $query = "SELECT `template_data` FROM " . $templateTable
             . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    case 'update':
        $query = "UPDATE " . $templateTable . " SET `template_data` = "
          . "'" . PMA_Util::sqlAddSlashes($_REQUEST['templateData']) . "'"
          . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    case 'delete':
        $query = "DELETE FROM " . $templateTable
           . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    default:
        break;
    }

    $result = PMA_queryAsControlUser($query, false);

    $response = PMA_Response::getInstance();
    if (! $result) {
        $error = $GLOBALS['dbi']->getError($GLOBALS['controllink']);
        $response->isSuccess(false);
        $response->addJSON('message', $error);
        exit;
    }

    $response->isSuccess(true);
    if ('create' == $_REQUEST['templateAction']) {
        $response->addJSON(
            'data',
            PMA_getOptionsForExportTemplates($_REQUEST['exportType'])
        );
    } elseif ('load' == $_REQUEST['templateAction']) {
        $data = null;
        while ($row = $GLOBALS['dbi']->fetchAssoc(
            $result, $GLOBALS['controllink']
        )) {
            $data = $row['template_data'];
        }
        $response->addJSON('data', $data);
    }
    $GLOBALS['dbi']->freeResult($result);
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

    echo PMA_Util::getMessage(PMA_Message::success());
}

$export_type = 'table';
require_once 'libraries/display_export.inc.php';
