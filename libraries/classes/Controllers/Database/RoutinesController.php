<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Core;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function in_array;
use function strlen;

/**
 * Routines management.
 */
class RoutinesController extends AbstractController
{
    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, CheckUserPrivileges $checkUserPrivileges, $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $db, $table, $tables, $num_tables, $total_num_tables, $sub_part;
        global $tooltip_truename, $tooltip_aliasname, $pos;
        global $errors, $PMA_Theme, $text_dir, $err_url, $url_params, $cfg;

        $type = $_REQUEST['type'] ?? null;

        $this->checkUserPrivileges->getPrivileges();

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,,,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part ?? '');

        if (! $this->response->isAjax()) {
            /**
             * Displays the header and tabs
             */
            if (! empty($table) && in_array($table, $this->dbi->getTables($db))) {
                Util::checkParameters(['db', 'table']);

                $url_params = ['db' => $db, 'table' => $table];
                $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
                $err_url .= Url::getCommon($url_params, '&');

                DbTableExists::check();
            } else {
                $table = '';

                Util::checkParameters(['db']);

                $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
                $err_url .= Url::getCommon(['db' => $db], '&');

                if (! $this->hasDatabase()) {
                    return;
                }
            }
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $routines = new Routines($this->dbi, $this->template, $this->response);

        $routines->handleEditor();
        $routines->handleExecute();
        $routines->export();

        if (! Core::isValid($type, ['FUNCTION', 'PROCEDURE'])) {
            $type = null;
        }

        $items = $this->dbi->getRoutines($db, $type);
        $totalNumRoutines = count($items);
        $pageSize = $cfg['MaxRoutineList'];

        // Checks if there are any routines to be shown on current page.
        // If there are no routines, the user is redirected to the last page
        // having any.
        if ($totalNumRoutines > 0 && $pos >= $totalNumRoutines) {
            $redirParams = [
                'db' => $this->db,
                'pos' => max(0, $totalNumRoutines - $pageSize),
                'reload' => 1,
            ];
            if ($this->response->isAjax()) {
                $redirParams['ajax_request'] = 'true';
                $redirParams['ajax_page_request'] = 'true';
            }
            $uri = './index.php?route=/database/routines' . Url::getCommonRaw($redirParams, '&');
            Core::sendHeaderLocation($uri);
        }

        $items = array_slice($items, $pos, $pageSize);

        $isAjax = $this->response->isAjax() && empty($_REQUEST['ajax_page_request']);

        $rows = '';
        foreach ($items as $item) {
            $rows .= $routines->getRow($item, $isAjax ? 'ajaxInsert hide' : '');
        }

        $urlParams = [
            'pos' => $pos,
            'db' => $db,
        ];

        $listNavigator = Generator::getListNavigator(
                $totalNumRoutines,
                $pos,
                $urlParams,
                Url::getFromRoute('/database/routines'),
                'frame_content',
                $pageSize
            );

        $this->render('database/routines/index', [
            'db' => $db,
            'table' => $table,
            'items' => $items,
            'rows' => $rows,
            'select_all_arrow_src' => $PMA_Theme->getImgPath() . 'arrow_' . $text_dir . '.png',
            'has_privilege' => Util::currentUserHasPrivilege('CREATE ROUTINE', $db, $table),
            'list_navigator_html' => $listNavigator,
        ]);
    }
}
