<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

                    if (!empty($del_url)) {
                        echo '    <td align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n";
                        echo '        <input type="checkbox" name="rows_to_delete[]" value="' . $del_query . '" />' . "\n";
                        echo '    </td>' . "\n";
                    }
                    if (!empty($edit_url)) {
                        echo '    <td align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n";
                        echo PMA_linkOrButton($edit_url, $edit_str, '');
                        echo $bookmark_go;
                        echo '    </td>' . "\n";
                    }
                    if (!empty($del_url)) {
                        echo '    <td align="center" valign="' . ($bookmark_go != '' ? 'top' : 'middle') . '" bgcolor="' . $bgcolor . '">' . "\n";
                        echo PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''));
                        echo '    </td>' . "\n";
                    }
?>