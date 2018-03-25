<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * query by example the whole database
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\SavedSearches;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

$response = Response::getInstance();

// Gets the relation settings
$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();

$savedSearchList = array();
$savedSearch = null;
$currentSearchId = null;
if ($cfgRelation['savedsearcheswork']) {
    $header = $response->getHeader();
    $scripts = $header->getScripts();
    $scripts->addFile('db_qbe.js');

    //Get saved search list.
    $savedSearch = new SavedSearches($GLOBALS);
    $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
        ->setDbname($_REQUEST['db']);

    if (!empty($_REQUEST['searchId'])) {
        $savedSearch->setId($_REQUEST['searchId']);
    }

    //Action field is sent.
    if (isset($_REQUEST['action'])) {
        $savedSearch->setSearchName($_REQUEST['searchName']);
        if ('create' === $_REQUEST['action']) {
            $saveResult = $savedSearch->setId(null)
                ->setCriterias($_REQUEST)
                ->save();
        } elseif ('update' === $_REQUEST['action']) {
            $saveResult = $savedSearch->setCriterias($_REQUEST)
                ->save();
        } elseif ('delete' === $_REQUEST['action']) {
            $deleteResult = $savedSearch->delete();
            //After deletion, reset search.
            $savedSearch = new SavedSearches($GLOBALS);
            $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($_REQUEST['db']);
            $_REQUEST = array();
        } elseif ('load' === $_REQUEST['action']) {
            if (empty($_REQUEST['searchId'])) {
                //when not loading a search, reset the object.
                $savedSearch = new SavedSearches($GLOBALS);
                $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                    ->setDbname($_REQUEST['db']);
                $_REQUEST = array();
            } else {
                $loadResult = $savedSearch->load();
            }
        }
        //Else, it's an "update query"
    }

    $savedSearchList = $savedSearch->getList();
    $currentSearchId = $savedSearch->getId();
}

/**
 * A query has been submitted -> (maybe) execute it
 */
$message_to_display = false;
if (isset($_REQUEST['submit_sql']) && ! empty($sql_query)) {
    if (! preg_match('@^SELECT@i', $sql_query)) {
        $message_to_display = true;
    } else {
        $goto = 'db_sql.php';
        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $_REQUEST['db'], // db
            null, // table
            false, // find_real_end
            null, // sql_query_for_bookmark
            null, // extra_data
            null, // message_to_show
            null, // message
            null, // sql_data
            $goto, // goto
            $pmaThemeImage, // pmaThemeImage
            null, // disp_query
            null, // disp_message
            null, // query_type
            $sql_query, // sql_query
            null, // selectedTables
            null // complete_query
        );
    }
}

$sub_part  = '_qbe';
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_qbe.php';
$url_params['goto'] = 'db_qbe.php';

list(
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos
) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

if ($message_to_display) {
    Message::error(
        __('You have to choose at least one column to display!')
    )
        ->display();
}
unset($message_to_display);

// create new qbe search instance
$db_qbe = new Qbe($GLOBALS['db'], $savedSearchList, $savedSearch);

$url = 'db_designer.php' . Url::getCommon(
    array_merge(
        $url_params,
        array('query' => 1)
    )
);
$response->addHTML(
    Message::notice(
        sprintf(
            __('Switch to %svisual builder%s'),
            '<a href="' . $url . '">',
            '</a>'
        )
    )
);

/**
 * Displays the Query by example form
 */
$response->addHTML($db_qbe->getSelectionForm());
