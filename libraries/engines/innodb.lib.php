<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

class PMA_StorageEngine_innodb extends PMA_StorageEngine {
    function getVariables() {
        return array(
            'innodb_data_home_dir' => array(
                'title' => $GLOBALS['strInnoDBDataHomeDir'],
                'desc'  => $GLOBALS['strInnoDBDataHomeDirDesc']
            ),
            'innodb_data_file_path' => array(
                'title' => $GLOBALS['strInnoDBDataFilePath']
            ),
            'innodb_autoextend_increment' => array(
                'title' => $GLOBALS['strInnoDBAutoextendIncrement'],
                'desc'  => $GLOBALS['strInnoDBAutoextendIncrementDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
            )
        );
    }

    function getVariablesLikePattern () {
        return 'innodb\\_%';
    }

    function getInfoPages () {
        if ($this->support < PMA_ENGINE_SUPPORT_YES) {
            return array();
        }
        $pages = array();
        if (PMA_MYSQL_INT_VERSION >= 50002) {
            $pages['bufferpool'] = $GLOBALS['strBufferPool'];
        }
        $pages['status'] = $GLOBALS['strInnodbStat'];
        return $pages;
    }

    function getPage($id) {
        global $cfg;

        switch ($id) {
            case 'bufferpool':
                if (PMA_MYSQL_INT_VERSION < 50002) {
                    return FALSE;
                }
                $res = PMA_DBI_query('SHOW STATUS LIKE \'Innodb\\_buffer\\_pool\\_%\'');
                $status = array();
                while ($row = PMA_DBI_fetch_row($res)) {
                    $status[$row[0]] = $row[1];
                }
                PMA_DBI_free_result($res);
                unset($res, $row);
                $output = '<table>' . "\n"
                        . '    <thead>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <th colspan="4">' . "\n"
                        . '                ' . $GLOBALS['strBufferPoolUsage'] . "\n"
                        . '            </th>' . "\n"
                        . '        </tr>' . "\n"
                        . '    </thead>' . "\n"
                        . '    <tfoot>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <th>' . "\n"
                        . '                ' . $GLOBALS['strTotalUC'] . "\n"
                        . '            </th>' . "\n"
                        . '            <th>' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_total']) . "\n"
                        . '            </th>' . "\n"
                        . '        </tr>' . "\n"
                        . '    </tfoot>' . "\n"
                        . '    <tbody>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strFreePages'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_free']) . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strDirtyPages'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_dirty']) . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strDataPages'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_data']) . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strPagesToBeFlushed'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_flushed']) . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strBusyPages'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_misc']) . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strLatchedPages'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_pages_latched']) . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '    </tbody>' . "\n"
                        . '</table>' . "\n\n"
                        . '<br />' . "\n\n"
                        . '<table>' . "\n"
                        . '    <thead>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <th colspan="4">' . "\n"
                        . '                ' . $GLOBALS['strBufferPoolActivity'] . "\n"
                        . '            </th>' . "\n"
                        . '        </tr>' . "\n"
                        . '    </thead>' . "\n"
                        . '    <tbody>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strReadRequests'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_read_requests']) . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strWriteRequests'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_write_requests']) . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strBufferReadMisses'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_reads']) . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strBufferWriteWaits'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars($status['Innodb_buffer_pool_wait_free']) . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '        <tr>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strBufferReadMissesInPercent'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars(number_format($status['Innodb_buffer_pool_reads'] * 100 / $status['Innodb_buffer_pool_read_requests'], 2, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator'])) . '&nbsp;%' . "\n"
                        . '            </td>' . "\n"
                        . '            <td bgcolor="' . $cfg['BgcolorOne'] . '">' . "\n"
                        . '                &nbsp;' . $GLOBALS['strBufferWriteWaitsInPercent'] . '&nbsp;' . "\n"
                        . '            </td>' . "\n"
                        . '            <td align="right" bgcolor="' . $cfg['BgcolorTwo'] . '">' . "\n"
                        . '                ' . htmlspecialchars(number_format($status['Innodb_buffer_pool_wait_free'] * 100 / $status['Innodb_buffer_pool_write_requests'], 2, $GLOBALS['number_decimal_separator'], $GLOBALS['number_thousands_separator'])) . '&nbsp;%' . "\n"
                        . '            </td>' . "\n"
                        . '        </tr>' . "\n"
                        . '    </tbody>' . "\n"
                        . '</table>' . "\n";
                return $output;
            case 'status':
                $res = PMA_DBI_query('SHOW INNODB STATUS;');
                $row = PMA_DBI_fetch_row($res);
                PMA_DBI_free_result($res);
                return '<pre>' . "\n"
                      . htmlspecialchars($row[0]) . "\n"
                      . '</pre>' . "\n";
            default:
                return FALSE;
        }
    }
}

?>
