<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * do not import request variable into global scope
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

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

/*
 * Import chart functions
 */
require_once './libraries/chart.lib.php';

/*
 * Execute the query and return the result
 */
$data = array();

$result = PMA_DBI_try_query($sql_query);
while ($row = PMA_DBI_fetch_assoc($result)) {
    $data[] = $row;
    /*foreach ($row as $key => $value) {
        $chartData[$key][] = $value;
    }*/
}

/**
 * Displays top menu links
 * We use db links because a chart is not necessarily on a single table
 */
$num_tables = 0;
require_once './libraries/db_links.inc.php';

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
    <legend><?php echo __('Display chart'); ?></legend>
    <?php echo PMA_chart_results($data); ?>

</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="displayChart" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>
</div>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';

?>
