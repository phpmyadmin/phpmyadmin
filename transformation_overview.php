<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Don't display the page heading
 */
define('PMA_DISPLAY_HEADING', 0);

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';
require_once './libraries/header.inc.php';
require_once './libraries/relation.lib.php';
require_once './libraries/transformations.lib.php';
$cfgRelation = PMA_getRelationsParam();

$types = PMA_getAvailableMIMEtypes();
?>

<h2><?php echo $strMIME_available_mime; ?></h2>
<?php
foreach ($types['mimetype'] as $key => $mimetype) {

    if (isset($types['empty_mimetype'][$mimetype])) {
        echo '<i>' . $mimetype . '</i><br />';
    } else {
        echo $mimetype . '<br />';
    }

}
?>
<br />
<i>(<?php echo $strMIME_without; ?>)</i>

<br />
<br />
<br />
<h2><?php echo $strMIME_available_transform; ?></h2>
<table border="0" width="90%">
<thead>
<tr>
    <th><?php echo $strMIME_transformation; ?></th>
    <th><?php echo $strMIME_description; ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
foreach ($types['transformation'] as $key => $transform) {
    $func = strtolower(preg_replace('@(\.inc\.php3?)$@i', '', $types['transformation_file'][$key]));
    $desc = 'strTransformation_' . $func;
    ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
        <td><?php echo $transform; ?></td>
        <td><?php echo (isset($$desc) ? $$desc : '<i>' . sprintf($strMIME_nodescription, 'PMA_transformation_' . $func . '()') . '</i>'); ?></td>
    </tr>
    <?php
    $odd_row = !$odd_row;
}
?>
</tbody>
</table>

<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
