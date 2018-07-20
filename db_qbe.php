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
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

$response = Response::getInstance();
$relation = new Relation();

// Gets the relation settings
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
        ->setDbname($GLOBALS['db']);

    if (!empty($_POST['searchId'])) {
        $savedSearch->setId($_POST['searchId']);
    }

    //Action field is sent.
    if (isset($_POST['action'])) {
        $savedSearch->setSearchName($_POST['searchName']);
        if ('create' === $_POST['action']) {
            $saveResult = $savedSearch->setId(null)
                ->setCriterias($_POST)
                ->save();
        } elseif ('update' === $_POST['action']) {
            $saveResult = $savedSearch->setCriterias($_POST)
                ->save();
        } elseif ('delete' === $_POST['action']) {
            $deleteResult = $savedSearch->delete();
            //After deletion, reset search.
            $savedSearch = new SavedSearches($GLOBALS);
            $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                ->setDbname($GLOBALS['db']);
            $_POST = array();
        } elseif ('load' === $_POST['action']) {
            if (empty($_POST['searchId'])) {
                //when not loading a search, reset the object.
                $savedSearch = new SavedSearches($GLOBALS);
                $savedSearch->setUsername($GLOBALS['cfg']['Server']['user'])
                    ->setDbname($GLOBALS['db']);
                $_POST = array();
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
if (isset($_POST['submit_sql']) && ! empty($sql_query)) {
    if (! preg_match('@^SELECT@i', $sql_query)) {
        $message_to_display = true;
    } else {
        $goto = 'db_sql.php';
        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $_POST['db'], // db
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

$secondaryTabs = [
    'multi' => [
        'link' => 'db_multi_table_query.php',
        'text' => __('Multi-table query'),
    ],
    'qbe' => [
        'link' => 'db_qbe.php',
        'text' => __('Query by example'),
    ],
];
$response->addHTML(
    Template::get('secondary_tabs')->render([
        'url_params' => $url_params,
        'sub_tabs' => $secondaryTabs,
    ])
);

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
