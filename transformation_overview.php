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
$header->disableMenuAndConsole();

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
$transformation_types = array(
    'transformation', 'input_transformation'
);
$label = array(
    'transformation' => __('Available browser display transformations'),
    'input_transformation' => __('Available input transformations')
);
$th = array(
    'transformation' => __('Browser display transformation'),
    'input_transformation' => __('Input transformation')
);
?>
<br />
<?php foreach ($transformation_types as $ttype) { ?>
    <a name="<?php echo $ttype; ?>"></a>
    <h2><?php echo $label[$ttype] ?></h2>
    <table width="90%">
    <thead>
    <tr>
        <th><?php echo $th[$ttype] ?></th>
        <th><?php echo _pgettext('for MIME transformation', 'Description'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $odd_row = true;
    foreach ($types[$ttype] as $key => $transform) {
        $desc = PMA_getTransformationDescription($types[$ttype . '_file'][$key]);
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
    <?php
} // End of foreach ($transformation_types)
