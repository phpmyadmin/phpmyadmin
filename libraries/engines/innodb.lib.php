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
            )
        );
    }

    function getVariablesLikePattern () {
        return 'innodb\\_%';
    }

    function getInfoPages () {
        return array(
            'status' => $GLOBALS['strInnodbStat']
        );
    }

    function getPage($id) {
        if ($id == 'status') {
            $res = PMA_DBI_query('SHOW INNODB STATUS;');
            $row = PMA_DBI_fetch_row($res);
            PMA_DBI_free_result($res);
            return '<pre>' . "\n"
                  . htmlspecialchars($row[0]) . "\n"
                  . '</pre>' . "\n";
        } else {
            return FALSE;
        }
    }
}

?>
