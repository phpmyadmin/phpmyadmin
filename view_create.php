<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of VIEWs
 *
 * @todo js error when view name is empty (strFormEmpty)
 * @todo (also validate if js is disabled, after form submission?)
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Runs common work
 */
require './libraries/db_common.inc.php';
$url_params['goto'] = $cfg['DefaultTabDatabase'];
$url_params['back'] = 'view_create.php';

$view_algorithm_options = array(
    'UNDEFINED',
    'MERGE',
    'TEMPTABLE',
);

$view_with_options = array(
    'CASCADED CHECK OPTION',
    'LOCAL CHECK OPTION'
);

if (isset($_REQUEST['createview'])) {
    /**
     * Creates the view
     */
    $sep = "\r\n";

    $sql_query = 'CREATE';

    if (isset($_REQUEST['view']['or_replace'])) {
        $sql_query .= ' OR REPLACE';
    }

    if (PMA_isValid($_REQUEST['view']['algorithm'], $view_algorithm_options)) {
        $sql_query .= $sep . ' ALGORITHM = ' . $_REQUEST['view']['algorithm'];
    }

    $sql_query .= $sep . ' VIEW ' . PMA_CommonFunctions::getInstance()->backquote($_REQUEST['view']['name']);

    if (! empty($_REQUEST['view']['column_names'])) {
        $sql_query .= $sep . ' (' . $_REQUEST['view']['column_names'] . ')';
    }

    $sql_query .= $sep . ' AS ' . $_REQUEST['view']['as'];

    if (isset($_REQUEST['view']['with'])) {
        $options = array_intersect($_REQUEST['view']['with'], $view_with_options);
        if (count($options)) {
            $sql_query .= $sep . ' WITH ' . implode(' ', $options);
        }
    }

    if (PMA_DBI_try_query($sql_query)) {
        
        require_once './libraries/tbl_views.lib.php';
        
        // If different column names defined for VIEW
        $view_columns = array();
        if (isset($_REQUEST['view']['column_names'])) {
            $view_columns = explode(',', $_REQUEST['view']['column_names']);
        }
        
        $column_map = PMA_getColumnMap($_REQUEST['view']['as'], $view_columns);        
        $pma_tranformation_data = PMA_getExistingTranformationData($GLOBALS['db']);
        
        if ($pma_tranformation_data !== false) {
            
            // SQL for store new transformation details of VIEW
            $new_transformations_sql = PMA_getNewTransformationDataSql(
                $pma_tranformation_data, $column_map, $_REQUEST['view']['name'],
                $GLOBALS['db']
            );            
            
            // Store new transformations
            if ($new_transformations_sql != '') {
                PMA_DBI_try_query($new_transformations_sql);
            }
            
        }
        unset($pma_tranformation_data);
        
        if ($GLOBALS['is_ajax_request'] != true) {
            $message = PMA_Message::success();
            include './' . $cfg['DefaultTabDatabase'];
        } else {
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message',
                PMA_CommonFunctions::getInstance()->getMessage(
                    PMA_Message::success(), $sql_query
                )
            );
        }
        
        exit;
        
    } else {
        if ($GLOBALS['is_ajax_request'] != true) {
            $message = PMA_Message::rawError(PMA_DBI_getError());
        } else {
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message',
                PMA_Message::error(
                    "<i>$sql_query</i><br /><br />" . PMA_DBI_getError()
                )
            );
            $response->isSuccess(false);
            exit;
        }
    }
}

// prefill values if not already filled from former submission
$view = array(
    'or_replace' => '',
    'algorithm' => '',
    'name' => '',
    'column_names' => '',
    'as' => $sql_query,
    'with' => array(),
);

if (PMA_isValid($_REQUEST['view'], 'array')) {
    $view = array_merge($view, $_REQUEST['view']);
}

$url_params['db'] = $GLOBALS['db'];
$url_params['reload'] = 1;

/**
 * Displays the page
 */
?>
<!-- CREATE VIEW options -->
<div id="div_view_options">
<form method="post" action="view_create.php">
<?php echo PMA_generate_common_hidden_inputs($url_params); ?>
<fieldset>
    <legend><?php echo __('Create view') . PMA_CommonFunctions::getInstance()->showMySQLDocu('SQL-Syntax', 'CREATE_VIEW'); ?></legend>
    <table class="rte_table">
    <tr><td><label for="or_replace">OR REPLACE</label></td>
        <td><input type="checkbox" name="view[or_replace]" id="or_replace"
                <?php if ($view['or_replace']) { ?>
                checked="checked"
                <?php } ?>
                value="1" />
        </td>
    </tr>
    <tr>
        <td><label for="algorithm">ALGORITHM</label></td>
        <td><select name="view[algorithm]" id="algorithm">
            <?php
            foreach ($view_algorithm_options as $option) {
                echo '<option value="' . htmlspecialchars($option) . '"';
                if ($view['algorithm'] === $option) {
                    echo 'selected="selected"';
                }
                echo '>' . htmlspecialchars($option) . '</option>';
            }
            ?>
            </select>
        </td>
    </tr>
    <tr><td><?php echo __('VIEW name'); ?></td>
        <td><input type="text" size="20" name="view[name]" onfocus="this.select()"
                value="<?php echo htmlspecialchars($view['name']); ?>" />
        </td>
    </tr>
    <tr><td><?php echo __('Column names'); ?></td>
        <td><input type="text" maxlength="100" size="50" name="view[column_names]"
                onfocus="this.select()"
                value="<?php echo htmlspecialchars($view['column_names']); ?>" />
        </td>
    </tr>
    <tr><td>AS</td>
        <td>
            <textarea name="view[as]" rows="<?php echo $cfg['TextareaRows']; ?>"
                cols="<?php echo $cfg['TextareaCols']; ?>"
                dir="<?php echo $text_dir; ?>"<?php
                if ($GLOBALS['cfg']['TextareaAutoSelect'] || true) {
                    echo ' onclick="selectContent(this, sql_box_locked, true)"';
                }
                ?>><?php echo htmlspecialchars($view['as']); ?></textarea>
        </td>
    </tr>
    <tr><td>WITH</td>
        <td>
            <?php
            foreach ($view_with_options as $option) {
                echo '<input type="checkbox" name="view[with][]"';
                if (in_array($option, $view['with'])) {
                    echo ' checked="checked"';
                }
                echo ' id="view_with_' . str_replace(' ', '_', htmlspecialchars($option)) . '"';
                echo ' value="' . htmlspecialchars($option) . '" />';
                echo '<label for="view_with_' . str_replace(' ', '_', htmlspecialchars($option)) . '">&nbsp;';
                echo htmlspecialchars($option) . '</label><br />';
            }
            ?>
        </td>
    </tr>
    </table>
</fieldset>
<?php
    if ($GLOBALS['is_ajax_request'] != true) {
?>
<fieldset class="tblFooters">
    <input type="submit" name="createview" value="<?php echo __('Go'); ?>" />
</fieldset>
<?php
    } else {
?>
    <input type="hidden" name="createview" value="1" />
    <input type="hidden" name="ajax_request" value="1" />
<?php
    }
?>
</form>
</div>
