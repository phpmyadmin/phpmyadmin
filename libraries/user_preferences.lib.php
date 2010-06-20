<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package phpMyAdmin
 */

/**
 * Saves user preferences
 *
 * @uses PMA_getRelationsParam()
 * @return true|PMA_Message
 */
function PMA_save_userprefs()
{
    $cfgRelation = PMA_getRelationsParam();
    $cf = ConfigFile::getInstance();

    $config_data = serialize($cf->getConfigArray());

    $query_table = PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['userconfig']);
    $query = '
        SELECT `username`
        FROM ' . $query_table . '
          WHERE `username` = \'' . PMA_sqlAddslashes($cfgRelation['user']) . '\'';

    $has_config = PMA_DBI_fetch_value($query, 0, 0, $GLOBALS['controllink']);
    if ($has_config) {
        $query = '
            UPDATE ' . $query_table . '
            SET `config_data` = \'' . PMA_sqlAddslashes($config_data) . '\'
            WHERE `username` = \'' . PMA_sqlAddslashes($cfgRelation['user']) . '\'';
    } else {
        $query = '
            INSERT INTO ' . $query_table . ' (`username`, `config_data`)
            VALUES (\'' . PMA_sqlAddslashes($cfgRelation['user']) . '\',
                \'' . PMA_sqlAddslashes($config_data) . '\')';
    }
    if (!PMA_DBI_try_query($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not save configuration'));
        $message->addMessage('<br /><br />');
        $message->addMessage(PMA_Message::rawError(PMA_DBI_getError($GLOBALS['controllink'])));
        return $message;
    }
    return true;
}
?>