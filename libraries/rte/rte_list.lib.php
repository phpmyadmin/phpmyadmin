<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for generating lists of Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Creates a list of items containing the relevant
 * information and some action links.
 *
 * @param string $type  One of ['routine'|'trigger'|'event']
 * @param array  $items An array of items
 *
 * @return string HTML code of the list of items
 */
function PMA_RTE_getList($type, $items)
{
    global $table;

    /**
     * Conditional classes switch the list on or off
     */
    $class1 = 'hide';
    $class2 = '';
    if (! $items) {
        $class1 = '';
        $class2 = ' hide';
    }
    /**
     * Generate output
     */
    $retval  = "<!-- LIST OF " . PMA_RTE_getWord('docu') . " START -->\n";
    $retval .= '<form id="rteListForm" class="ajax" action="';
    switch ($type) {
    case 'routine':
        $retval .= 'db_routines.php';
        break;
    case 'trigger':
        if (! empty($table)) {
            $retval .= 'tbl_triggers.php';
        } else {
            $retval .= 'db_triggers.php';
        }
        break;
    case 'event':
        $retval .= 'db_events.php';
        break;
    default:
        break;
    }
    $retval .= '">';
    $retval .= PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
    $retval .= "<fieldset>\n";
    $retval .= "    <legend>\n";
    $retval .= "        " . PMA_RTE_getWord('title') . "\n";
    $retval .= "        " . PMA_Util::showMySQLDocu(PMA_RTE_getWord('docu')) . "\n";
    $retval .= "    </legend>\n";
    $retval .= "    <div class='$class1' id='nothing2display'>\n";
    $retval .= "      " . PMA_RTE_getWord('nothing') . "\n";
    $retval .= "    </div>\n";
    $retval .= "    <table class='data$class2'>\n";
    $retval .= "        <!-- TABLE HEADERS -->\n";
    $retval .= "        <tr>\n";
    // th cells with a colspan need corresponding td cells, according to W3C
    switch ($type) {
    case 'routine':
        $retval .= "            <th></th>\n";
        $retval .= "            <th>" . __('Name') . "</th>\n";
        $retval .= "            <th colspan='4'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Type') . "</th>\n";
        $retval .= "            <th>" . __('Returns') . "</th>\n";
        $retval .= "        </tr>\n";
        $retval .= "        <tr style='display: none'>\n"; // see comment above
        for ($i = 0; $i < 7; $i++) {
            $retval .= "            <td></td>\n";
        }
        break;
    case 'trigger':
        $retval .= "            <th></th>\n";
        $retval .= "            <th>" . __('Name') . "</th>\n";
        if (empty($table)) {
            $retval .= "            <th>" . __('Table') . "</th>\n";
        }
        $retval .= "            <th colspan='3'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Time') . "</th>\n";
        $retval .= "            <th>" . __('Event') . "</th>\n";
        $retval .= "        </tr>\n";
        $retval .= "        <tr style='display: none'>\n"; // see comment above
        for ($i = 0; $i < (empty($table) ? 7 : 6); $i++) {
            $retval .= "            <td></td>\n";
        }
        break;
    case 'event':
        $retval .= "            <th></th>\n";
        $retval .= "            <th>" . __('Name') . "</th>\n";
        $retval .= "            <th>" . __('Status') . "</th>\n";
        $retval .= "            <th colspan='3'>" . __('Action') . "</th>\n";
        $retval .= "            <th>" . __('Type') . "</th>\n";
        $retval .= "        </tr>\n";
        $retval .= "        <tr style='display: none'>\n"; // see comment above
        for ($i = 0; $i < 6; $i++) {
            $retval .= "            <td></td>\n";
        }
        break;
    default:
        break;
    }
    $retval .= "        </tr>\n";
    $retval .= "        <!-- TABLE DATA -->\n";
    $count = 0;
    foreach ($items as $item) {
        $rowclass = ($count % 2 == 0) ? 'odd' : 'even';
        if ($GLOBALS['is_ajax_request'] && empty($_REQUEST['ajax_page_request'])) {
            $rowclass .= ' ajaxInsert hide';
        }
        // Get each row from the correct function
        switch ($type) {
        case 'routine':
            $retval .= PMA_RTN_getRowForList($item, $rowclass);
            break;
        case 'trigger':
            $retval .= PMA_TRI_getRowForList($item, $rowclass);
            break;
        case 'event':
            $retval .= PMA_EVN_getRowForList($item, $rowclass);
            break;
        default:
            break;
        }
        $count++;
    }
    $retval .= "    </table>\n";

    if (count($items)) {
        $retval .= '<div class="withSelected">';
        $retval .= PMA_Util::getWithSelected(
            $GLOBALS['pmaThemeImage'], $GLOBALS['text_dir'], 'rteListForm'
        );
        $retval .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_export',
            __('Export'), 'b_export.png', 'export'
        );
        $retval .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_drop',
            __('Drop'), 'b_drop.png', 'drop'
        );
        $retval .= '</div>';
    }

    $retval .= "</fieldset>\n";
    $retval .= "</form>\n";
    $retval .= "<!-- LIST OF " . PMA_RTE_getWord('docu') . " END -->\n";

    return $retval;
} // end PMA_RTE_getList()

/**
 * Creates the contents for a row in the list of routines
 *
 * @param array  $routine  An array of routine data
 * @param string $rowclass Empty or one of ['even'|'odd']
 *
 * @return string HTML code of a row for the list of routines
 */
function PMA_RTN_getRowForList($routine, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf(
        'DROP %s IF EXISTS %s',
        $routine['type'],
        PMA_Util::backquote($routine['name'])
    );
    $type_link = "item_type={$routine['type']}";

    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= '                <input type="checkbox"'
        . ' class="checkall" name="item_name[]"'
        . ' value="' . htmlspecialchars($routine['name']) . '" />';
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>"
        . htmlspecialchars($sql_drop) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    "
        . htmlspecialchars($routine['name']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    // Since editing a procedure involved dropping and recreating, check also for
    // CREATE ROUTINE privilege to avoid lost procedures.
    if (PMA_Util::currentUserHasPrivilege('CREATE ROUTINE', $db)) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_routines.php'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name='
                                         . urlencode($routine['name'])
                                         . '&amp;' . $type_link
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";

    // There is a problem with PMA_Util::currentUserHasPrivilege():
    // it does not detect all kinds of privileges, for example
    // a direct privilege on a specific routine. So, at this point,
    // we show the Execute link, hoping that the user has the correct rights.
    // Also, information_schema might be hiding the ROUTINE_DEFINITION
    // but a routine with no input parameters can be nonetheless executed.

    // Check if the routine has any input parameters. If it does,
    // we will show a dialog to get values for these parameters,
    // otherwise we can execute it directly.

    $parser = new SqlParser\Parser(
        $GLOBALS['dbi']->getDefinition(
            $db,
            $routine['type'],
            $routine['name']
        )
    );

    /**
     * @var CreateStatement $stmt
     */
    $stmt = $parser->statements[0];

    $params = SqlParser\Utils\Routine::getParameters($stmt);
    if ($routine !== false) {
        if (PMA_Util::currentUserHasPrivilege('EXECUTE', $db)) {
            $execute_action = 'execute_routine';
            for ($i = 0; $i < $params['num']; $i++) {
                if ($routine['type'] == 'PROCEDURE'
                    && $params['dir'][$i] == 'OUT'
                ) {
                    continue;
                }
                $execute_action = 'execute_dialog';
                break;
            }
            $retval .= '                <a ' . $ajax_class['exec']
                                             . ' href="db_routines.php'
                                             . $url_query
                                             . '&amp;' . $execute_action . '=1'
                                             . '&amp;item_name='
                                             . urlencode($routine['name'])
                                             . '&amp;' . $type_link
                                             . '">' . $titles['Execute'] . "</a>\n";
        } else {
            $retval .= "                {$titles['NoExecute']}\n";
        }
    }

    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                <a ' . $ajax_class['export']
                                     . ' href="db_routines.php'
                                     . $url_query
                                     . '&amp;export_item=1'
                                     . '&amp;item_name='
                                     . urlencode($routine['name'])
                                     . '&amp;' . $type_link
                                     . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php'
                                         . $url_query
                                         . '&amp;sql_query=' . urlencode($sql_drop)
                                         . '&amp;goto=db_routines.php'
                                         . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$routine['type']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td dir=\"ltr\">\n";
    $retval .= "                "
        . htmlspecialchars($routine['returns']) . "\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_RTN_getRowForList()

/**
 * Creates the contents for a row in the list of triggers
 *
 * @param array  $trigger  An array of routine data
 * @param string $rowclass Empty or one of ['even'|'odd']
 *
 * @return string HTML code of a cell for the list of triggers
 */
function PMA_TRI_getRowForList($trigger, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $table, $titles;

    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= '                <input type="checkbox"'
        . ' class="checkall" name="item_name[]"'
        . ' value="' . htmlspecialchars($trigger['name']) . '" />';
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>"
        . htmlspecialchars($trigger['drop']) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    " . htmlspecialchars($trigger['name']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    if (empty($table)) {
        $retval .= "            <td>\n";
        $retval .= "<a href='db_triggers.php{$url_query}"
            . "&amp;table=" . urlencode($trigger['table']) . "'>"
            . urlencode($trigger['table']) . "</a>";
        $retval .= "            </td>\n";
    }
    $retval .= "            <td>\n";
    if (PMA_Util::currentUserHasPrivilege('TRIGGER', $db, $table)) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_triggers.php'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name='
                                         . urlencode($trigger['name'])
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                    <a ' . $ajax_class['export']
                                         . ' href="db_triggers.php'
                                         . $url_query
                                         . '&amp;export_item=1'
                                         . '&amp;item_name='
                                         . urlencode($trigger['name'])
                                         . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_Util::currentUserHasPrivilege('TRIGGER', $db)) {
        $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php'
                                         . $url_query
                                         . '&amp;sql_query='
                                         . urlencode($trigger['drop'])
                                         . '&amp;goto=db_triggers.php'
                                         . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoDrop']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$trigger['action_timing']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$trigger['event_manipulation']}\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_TRI_getRowForList()

/**
 * Creates the contents for a row in the list of events
 *
 * @param array  $event    An array of routine data
 * @param string $rowclass Empty or one of ['even'|'odd']
 *
 * @return string HTML code of a cell for the list of events
 */
function PMA_EVN_getRowForList($event, $rowclass = '')
{
    global $ajax_class, $url_query, $db, $titles;

    $sql_drop = sprintf(
        'DROP EVENT IF EXISTS %s',
        PMA_Util::backquote($event['name'])
    );

    $retval  = "        <tr class='$rowclass'>\n";
    $retval .= "            <td>\n";
    $retval .= '                <input type="checkbox"'
        . ' class="checkall" name="item_name[]"'
        . ' value="' . htmlspecialchars($event['name']) . '" />';
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                <span class='drop_sql hide'>"
        . htmlspecialchars($sql_drop) . "</span>\n";
    $retval .= "                <strong>\n";
    $retval .= "                    "
        . htmlspecialchars($event['name']) . "\n";
    $retval .= "                </strong>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$event['status']}\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_Util::currentUserHasPrivilege('EVENT', $db)) {
        $retval .= '                <a ' . $ajax_class['edit']
                                         . ' href="db_events.php'
                                         . $url_query
                                         . '&amp;edit_item=1'
                                         . '&amp;item_name='
                                         . urlencode($event['name'])
                                         . '">' . $titles['Edit'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoEdit']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= '                <a ' . $ajax_class['export']
                                     . ' href="db_events.php'
                                     . $url_query
                                     . '&amp;export_item=1'
                                     . '&amp;item_name='
                                     . urlencode($event['name'])
                                     . '">' . $titles['Export'] . "</a>\n";
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    if (PMA_Util::currentUserHasPrivilege('EVENT', $db)) {
        $retval .= '                <a ' . $ajax_class['drop']
                                         . ' href="sql.php'
                                         . $url_query
                                         . '&amp;sql_query=' . urlencode($sql_drop)
                                         . '&amp;goto=db_events.php'
                                         . urlencode("?db={$db}")
                                         . '" >' . $titles['Drop'] . "</a>\n";
    } else {
        $retval .= "                {$titles['NoDrop']}\n";
    }
    $retval .= "            </td>\n";
    $retval .= "            <td>\n";
    $retval .= "                 {$event['type']}\n";
    $retval .= "            </td>\n";
    $retval .= "        </tr>\n";

    return $retval;
} // end PMA_EVN_getRowForList()

