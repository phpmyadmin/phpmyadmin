<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

class PMA_StorageEngine_bdb extends PMA_StorageEngine {
    function getVariables () {
        return array(
            'version_bdb' => array(
                'title' => $GLOBALS['strVersionInformation']
            )
        );
    }
    function getVariablesLikePattern() {
        return 'version_bdb';
    }
}

?>
