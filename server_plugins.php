<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * no need for variables importing
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

/**
 * requirements
 */
require_once './libraries/common.inc.php';

/**
 * JS includes
 */
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/jquery.cookie.js';
$GLOBALS['js_include'][] = 'jquery/jquery.tablesorter.js';
$GLOBALS['js_include'][] = 'server_plugins.js';

/**
 * Does the common work
 */
require './libraries/server_common.inc.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic']
        ? '<img class="icon" src="' . $pmaThemeImage . 'b_engine.png"'
            .' width="16" height="16" alt="" />' : '')
   . "\n" . __('Plugins') . "\n"
   . '</h2>' . "\n";

/**
 * Prepare plugin list
 */
$sql = "SELECT p.plugin_name, p.plugin_type, p.is_active, m.module_name, m.module_library,
        m.module_version, m.module_author, m.module_description, m.module_license
    FROM data_dictionary.plugins p
        JOIN data_dictionary.modules m USING (module_name)
    ORDER BY m.module_name, p.plugin_type, p.plugin_name";
$res = PMA_DBI_query($sql);
$plugins = array();
$modules = array();
while ($row = PMA_DBI_fetch_assoc($res)) {
    $plugins[$row['plugin_type']][] = $row;
    $modules[$row['module_name']]['info'] = $row;
    $modules[$row['module_name']]['plugins'][$row['plugin_type']][] = $row;
}
PMA_DBI_free_result($res);

// sort plugin list (modules are already sorted)
ksort($plugins);

/**
 * Displays the page
 */
?>
<script type="text/javascript">
pma_theme_image = '<?php echo $GLOBALS['pmaThemeImage']; ?>';
</script>
<div id="pluginsTabs">
    <ul>
        <li><a href="#plugins_plugins"><?php echo __('Plugins'); ?></a></li>
        <li><a href="#plugins_modules"><?php echo __('Modules'); ?></a></li>
    </ul>

    <div id="plugins_plugins">
        <div id="sectionlinks">
        <?php
        foreach ($plugins as $plugin_type => $plugin_list) {
            $key = 'plugins-' . preg_replace('/[^a-z]/', '', strtolower($plugin_type));
            echo '<a href="#' . $key . '">' . htmlspecialchars($plugin_type) . '</a>' . "\n";
        }
        ?>
        </div>
        <br />
        <?php
        foreach ($plugins as $plugin_type => $plugin_list) {
            $key = 'plugins-' . preg_replace('/[^a-z]/', '', strtolower($plugin_type));
            sort($plugin_list);
            ?>
            <table class="data_full_width" id="<?php echo $key; ?>">
            <caption class="tblHeaders">
                <a class="top" href="#serverinfo"><?php
                    echo __('Begin');
                    echo $GLOBALS['cfg']['MainPageIconic']
                        ? '<img src="' . $GLOBALS['pmaThemeImage'] .
                            's_asc.png" width="11" height="9" align="middle" alt="" />'
                        : ''; ?></a>
                <?php echo htmlspecialchars($plugin_type); ?>
            </caption>
            <thead>
                <tr>
                    <th><?php echo __('Plugin'); ?></th>
                    <th><?php echo __('Module'); ?></th>
                    <th><?php echo __('Library'); ?></th>
                    <th><?php echo __('Version'); ?></th>
                    <th><?php echo __('Author'); ?></th>
                    <th><?php echo __('License'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $odd_row = false;
            foreach ($plugin_list as $plugin) {
                $odd_row = !$odd_row;
            ?>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
                <th><?php echo htmlspecialchars($plugin['plugin_name']); ?></th>
                <td><?php echo htmlspecialchars($plugin['module_name']); ?></td>
                <td><?php echo htmlspecialchars($plugin['module_library']); ?></td>
                <td><?php echo htmlspecialchars($plugin['module_version']); ?></td>
                <td><?php echo htmlspecialchars($plugin['module_author']); ?></td>
                <td><?php echo htmlspecialchars($plugin['module_license']); ?></td>
            </tr>
            <?php
            }
            ?>
            </tbody>
            </table>
            <?php
        }
        ?>
    </div>
    <div id="plugins_modules">
        <table class="data_full_width">
        <thead>
            <tr>
                <th><?php echo __('Module'); ?></th>
                <th><?php echo __('Description'); ?></th>
                <th><?php echo __('Library'); ?></th>
                <th><?php echo __('Version'); ?></th>
                <th><?php echo __('Author'); ?></th>
                <th><?php echo __('License'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $odd_row = false;
        foreach ($modules as $module_name => $module) {
            $odd_row = !$odd_row;
        ?>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
                <th rowspan="2"><?php echo htmlspecialchars($module_name); ?></th>
                <td><?php echo htmlspecialchars($module['info']['module_description']); ?></td>
                <td><?php echo htmlspecialchars($module['info']['module_library']); ?></td>
                <td><?php echo htmlspecialchars($module['info']['module_version']); ?></td>
                <td><?php echo htmlspecialchars($module['info']['module_author']); ?></td>
                <td><?php echo htmlspecialchars($module['info']['module_license']); ?></td>
            </tr>
            <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
                <td colspan="5">
                    <table>
                        <tbody>
                        <?php
                        foreach ($module['plugins'] as $plugin_type => $plugin_list) {
                        ?>
                            <tr class="noclick">
                                <td><b class="plugin-type"><?php echo htmlspecialchars($plugin_type); ?></b></td>
                                <td>
                                <?php
                                for ($i = 0; $i < count($plugin_list); $i++) {
                                    echo ($i != 0 ? '<br />' : '') . htmlspecialchars($plugin_list[$i]['plugin_name']);
                                    if (!$plugin_list[$i]['is_active']) {
                                        echo ' <small class="attention">' . __('disabled') . '</small>';
                                    }
                                }
                                ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
        </table>
    </div>
</div>
<?php
/**
 * Sends the footer
 */
require './libraries/footer.inc.php';

?>
