<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Change to basedir for including/requiring other fields
 */
chdir('../../');
define('PMA_PATH_TO_BASEDIR', '../../'); // rabus: required for the CSS link tag.

/**
 * Don't display the page heading
 */
define('PMA_DISPLAY_HEADING', 0);

/**
 * Gets some core libraries and displays a top message if required
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./header.inc.php');
require_once('./libraries/relation.lib.php');
require_once('./libraries/transformations.lib.php');
$cfgRelation = PMA_getRelationsParam();

$types = PMA_getAvailableMIMEtypes();
?>

<h2><?php echo $strMIME_available_mime; ?></h2>
<?php
foreach($types['mimetype'] AS $key => $mimetype) {

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
    <tr>
        <th><?php echo $strMIME_transformation; ?></th>
        <th><?php echo $strMIME_description; ?></th>
    </tr>

<?php
@reset($types);
$i = 0;
foreach($types['transformation'] AS $key => $transform) {
    $i++;
    $func = strtolower(preg_replace('@(\.inc\.php3?)$@i', '', $types['transformation_file'][$key]));
    $desc = 'strTransformation_' . $func;
?>
    <tr bgcolor="<?php echo ($i % 2 ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']); ?>">
        <td><?php echo $transform; ?></td>
        <td><?php echo (isset($$desc) ? $$desc : '<font size="-1"><i>' . sprintf($strMIME_nodescription, 'PMA_transformation_' . $func . '()') . '</i></font>'); ?></td>
    </tr>
<?php
}
?>

<?php
/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');

?>
