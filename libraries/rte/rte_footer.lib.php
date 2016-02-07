<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for generating the footer for Routines, Triggers and Events.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Creates a fieldset for adding a new item, if the user has the privileges.
 *
 * @param string $docu String used to create a link to the MySQL docs
 * @param string $priv Privilege to check for adding a new item
 * @param string $name MySQL name of the item
 *
 * @return string An HTML snippet with the link to add a new item
 */
function PMA_RTE_getFooterLinks($docu, $priv, $name)
{
    global $db, $table, $url_query, $ajax_class;

    $icon = mb_strtolower($name) . '_add.png';
    $retval  = "";
    $retval .= "<!-- ADD " . $name . " FORM START -->\n";
    $retval .= "<fieldset class='left'>\n";
    $retval .= "<legend>" . _pgettext('Create new procedure', 'New') . "</legend>\n";
    $retval .= "        <div class='wrap'>\n";
    if (PMA\libraries\Util::currentUserHasPrivilege($priv, $db, $table)) {
        $retval .= "            <a {$ajax_class['add']} ";
        $retval .= "href='db_" . mb_strtolower($name) . "s.php";
        $retval .= "$url_query&amp;add_item=1' ";
        $retval .= "onclick='$.datepicker.initialized = false;'>";
        $icon = 'b_' . $icon;
        $retval .= PMA\libraries\Util::getIcon($icon);
        $retval .= PMA_RTE_getWord('add') . "</a>\n";
    } else {
        $icon = 'bd_' . $icon;
        $retval .= PMA\libraries\Util::getIcon($icon);
        $retval .= PMA_RTE_getWord('add') . "\n";
    }
    $retval .= "            " . PMA\libraries\Util::showMySQLDocu($docu) . "\n";
    $retval .= "        </div>\n";
    $retval .= "</fieldset>\n";
    $retval .= "<!-- ADD " . $name . " FORM END -->\n\n";

    return $retval;
} // end PMA_RTE_getFooterLinks()

/**
 * Creates a fieldset for adding a new routine, if the user has the privileges.
 *
 * @return string    HTML code with containing the footer fieldset
 */
function PMA_RTN_getFooterLinks()
{
    return PMA_RTE_getFooterLinks('CREATE_PROCEDURE', 'CREATE ROUTINE', 'ROUTINE');
}// end PMA_RTN_getFooterLinks()

/**
 * Creates a fieldset for adding a new trigger, if the user has the privileges.
 *
 * @return string    HTML code with containing the footer fieldset
 */
function PMA_TRI_getFooterLinks()
{
    return PMA_RTE_getFooterLinks('CREATE_TRIGGER', 'TRIGGER', 'TRIGGER');
} // end PMA_TRI_getFooterLinks()

/**
 * Creates a fieldset for adding a new event, if the user has the privileges.
 *
 * @return string    HTML code with containing the footer fieldset
 */
function PMA_EVN_getFooterLinks()
{
    global $db, $url_query;

    /**
     * For events, we show the usual 'Add event' form and also
     * a form for toggling the state of the event scheduler
     */
    // Init options for the event scheduler toggle functionality
    $es_state = $GLOBALS['dbi']->fetchValue(
        "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
        0,
        1
    );
    $es_state = mb_strtolower($es_state);
    $options = array(
                    0 => array(
                        'label' => __('OFF'),
                        'value' => "SET GLOBAL event_scheduler=\"OFF\"",
                        'selected' => ($es_state != 'on')
                    ),
                    1 => array(
                        'label' => __('ON'),
                        'value' => "SET GLOBAL event_scheduler=\"ON\"",
                        'selected' => ($es_state == 'on')
                    )
               );
    // Generate output
    $retval  = "<!-- FOOTER LINKS START -->\n";
    $retval .= "<div class='doubleFieldset'>\n";
    // show the usual footer
    $retval .= PMA_RTE_getFooterLinks('CREATE_EVENT', 'EVENT', 'EVENT');
    $retval .= "    <fieldset class='right'>\n";
    $retval .= "        <legend>\n";
    $retval .= "            " . __('Event scheduler status') . "\n";
    $retval .= "        </legend>\n";
    $retval .= "        <div class='wrap'>\n";
    // show the toggle button
    $retval .= PMA\libraries\Util::toggleButton(
        "sql.php$url_query&amp;goto=db_events.php" . urlencode("?db=$db"),
        'sql_query',
        $options,
        'PMA_slidingMessage(data.sql_query);'
    );
    $retval .= "        </div>\n";
    $retval .= "    </fieldset>\n";
    $retval .= "    <div style='clear: both;'></div>\n";
    $retval .= "</div>";
    $retval .= "<!-- FOOTER LINKS END -->\n";

    return $retval;
} // end PMA_EVN_getFooterLinks()

