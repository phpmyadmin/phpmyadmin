<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

if (!empty($del_url)) {
    echo '    <td width="10" align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n"
       . '        <input type="checkbox" name="rows_to_delete[' . $uva_condition . ']" value="' . $del_query . '" />' . "\n"
       . '    </td>' . "\n";
}
if (!empty($edit_url)) {
    echo '    <td width="10" align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n"
       . PMA_linkOrButton($edit_url, $edit_str, '')
       . $bookmark_go
       . '    </td>' . "\n";
}
if (!empty($del_url)) {
    echo '    <td width="10" align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n"
       . PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''))
       . '    </td>' . "\n";
}
?>
