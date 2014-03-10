<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';
require_once './libraries/transformations.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->disableMenu();

$types = PMA_getAvailableMIMEtypes();
?>

<h2><?php echo __('Available MIME types'); ?></h2>
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
<h2><?php echo __('Available transformations'); ?></h2>
<table width="90%">
<thead>
<tr>
    <th><?php echo __('Browser transformation'); ?></th>
    <th><?php echo _pgettext('for MIME transformation', 'Description'); ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
foreach ($types['transformation'] as $key => $transform) {
    $desc = PMA_getTransformationDescription($types['transformation_file'][$key]);
    ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
        <td><?php echo $transform; ?></td>
        <td><?php echo $desc; ?></td>
    </tr>
    <?php
    $odd_row = !$odd_row;
}
?>
</tbody>
</table>
