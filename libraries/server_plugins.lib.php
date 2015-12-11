<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying server plugins
 *
 * @usedby  server_plugins.php
 *
 * @package PhpMyAdmin
 */

/**
 * Returns the common SQL used to retrieve plugin data
 *
 * @return string SQL
 */
function PMA_getServerPluginSQL()
{
    return "SELECT plugin_name, plugin_type, (plugin_status = 'ACTIVE') AS is_active,
            plugin_type_version, plugin_author, plugin_description, plugin_license
        FROM information_schema.plugins
        ORDER BY plugin_type, plugin_name";
}

/**
 * Returns details about server plugins
 *
 * @return array server plugins data
 */
function PMA_getServerPlugins()
{
    $sql = PMA_getServerPluginSQL();
    $res = $GLOBALS['dbi']->query($sql);
    $plugins = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $plugins[$row['plugin_type']][] = $row;
    }
    $GLOBALS['dbi']->freeResult($res);
    ksort($plugins);
    return $plugins;
}

/**
 * Returns the html for plugin Tab.
 *
 * @param array $plugins list
 *
 * @return string
 */
function PMA_getPluginTab($plugins)
{
    $html  = '<div id="plugins_plugins">';
    $html .= '<div id="sectionlinks">';

    foreach ($plugins as $plugin_type => $plugin_list) {
        $key = 'plugins-'
            . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
        $html .= '<a href="#' . $key . '">'
            . htmlspecialchars($plugin_type) . '</a>' . "\n";
    }

    $html .= '</div>';
    $html .= '<br />';

    foreach ($plugins as $plugin_type => $plugin_list) {
        $key = 'plugins-'
            . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
        sort($plugin_list);

        $html .= '<table class="data_full_width" id="' . $key . '">';
        $html .= '<caption class="tblHeaders">';
        $html .=  htmlspecialchars($plugin_type);
        $html .= '</caption>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . __('Plugin') . '</th>';
        $html .= '<th>' . __('Description') . '</th>';
        $html .= '<th>' . __('Version') . '</th>';
        $html .= '<th>' .  __('Author') . '</th>';
        $html .= '<th>' . __('License') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= PMA_getPluginList($plugin_list);

        $html .= '</tbody>';
        $html .= '</table>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Returns the html for plugin List.
 *
 * @param array $plugin_list list
 *
 * @return string
 */
function PMA_getPluginList($plugin_list)
{
    $html = "";
    $odd_row = false;
    foreach ($plugin_list as $plugin) {
        $odd_row = !$odd_row;
        $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '<th>';
        $html .= htmlspecialchars($plugin['plugin_name']);
        if (! $plugin['is_active']) {
            $html .= '&nbsp;<small class="attention">' . __('disabled') . '</small>';
        }
        $html .= '</th>';
        $html .= '<td>' . htmlspecialchars($plugin['plugin_description']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['plugin_type_version']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['plugin_author']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['plugin_license']) . '</td>';
        $html .= '</tr>';
    }
    return $html;
}