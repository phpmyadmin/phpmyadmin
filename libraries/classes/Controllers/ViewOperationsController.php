<?php
/**
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * View manipulations
 * @package PhpMyAdmin\Controllers
 */
class ViewOperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /**
     * @param Response          $response   Response object
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param Template          $template   Template object
     * @param Operations        $operations Operations object
     */
    public function __construct($response, $dbi, Template $template, Operations $operations)
    {
        parent::__construct($response, $dbi, $template);
        $this->operations = $operations;
    }

    /**
     * @return void
     */
    public function index(): void
    {
        global $sql_query, $url_query, $url_params, $reload, $result, $warning_messages;
        global $drop_view_url_params, $db, $table;

        $pma_table = new Table($table, $db);

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('table/operations.js');

        require ROOT_PATH . 'libraries/tbl_common.inc.php';

        $url_params['goto'] = $url_params['back'] = Url::getFromRoute('/view/operations');
        $url_query .= Url::getCommon($url_params, '&');

        /**
         * Updates if required
         */
        $_message = new Message();
        $_type = 'success';
        if (isset($_POST['submitoptions'])) {
            if (isset($_POST['new_name'])) {
                if ($pma_table->rename($_POST['new_name'])) {
                    $_message->addText($pma_table->getLastMessage());
                    $result = true;
                    $table = $pma_table->getName();
                    /* Force reread after rename */
                    $pma_table->getStatusInfo(null, true);
                    $reload = true;
                } else {
                    $_message->addText($pma_table->getLastError());
                    $result = false;
                }
            }

            $warning_messages = $this->operations->getWarningMessagesArray();
        }

        if (isset($result)) {
            // set to success by default, because result set could be empty
            // (for example, a table rename)
            if (empty($_message->getString())) {
                if ($result) {
                    $_message->addText(
                        __('Your SQL query has been executed successfully.')
                    );
                } else {
                    $_message->addText(__('Error'));
                }
                // $result should exist, regardless of $_message
                $_type = $result ? 'success' : 'error';
            }
            if (! empty($warning_messages)) {
                $_message->addMessagesString($warning_messages);
                $_message->isError(true);
                unset($warning_messages);
            }
            echo Generator::getMessage(
                $_message,
                $sql_query,
                $_type
            );
        }
        unset($_message, $_type);

        $drop_view_url_params = array_merge(
            $url_params,
            [
                'sql_query' => 'DROP VIEW ' . Util::backquote($table),
                'goto' => Url::getFromRoute('/table/structure'),
                'reload' => '1',
                'purge' => '1',
                'message_to_show' => sprintf(
                    __('View %s has been dropped.'),
                    $table
                ),
                'table' => $table,
            ]
        );

        echo $this->template->render('table/operations/view', [
            'db' => $db,
            'table' => $table,
            'delete_data_or_table_link' => $this->operations->getDeleteDataOrTablelink(
                $drop_view_url_params,
                'DROP VIEW',
                __('Delete the view (DROP)'),
                'drop_view_anchor'
            ),
        ]);
    }
}
