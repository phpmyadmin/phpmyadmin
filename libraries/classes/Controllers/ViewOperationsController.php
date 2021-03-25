<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * View manipulations
 */
class ViewOperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, Operations $operations, $dbi)
    {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $sql_query, $urlParams, $reload, $result, $warning_messages;
        global $db, $table, $cfg, $errorUrl;

        $tableObject = $this->dbi->getTable($db, $table);

        $this->addScriptFiles(['table/operations.js']);

        Util::checkParameters(['db', 'table']);

        $urlParams = ['db' => $db, 'table' => $table];
        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $errorUrl .= Url::getCommon($urlParams, '&');

        DbTableExists::check();

        $urlParams['goto'] = $urlParams['back'] = Url::getFromRoute('/view/operations');

        $message = new Message();
        $type = 'success';
        if (isset($_POST['submitoptions'])) {
            if (isset($_POST['new_name'])) {
                if ($tableObject->rename($_POST['new_name'])) {
                    $message->addText($tableObject->getLastMessage());
                    $result = true;
                    $table = $tableObject->getName();
                    /* Force reread after rename */
                    $tableObject->getStatusInfo(null, true);
                    $reload = true;
                } else {
                    $message->addText($tableObject->getLastError());
                    $result = false;
                }
            }

            $warning_messages = $this->operations->getWarningMessagesArray();
        }

        if (isset($result)) {
            // set to success by default, because result set could be empty
            // (for example, a table rename)
            if (empty($message->getString())) {
                if ($result) {
                    $message->addText(
                        __('Your SQL query has been executed successfully.')
                    );
                } else {
                    $message->addText(__('Error'));
                }

                // $result should exist, regardless of $_message
                $type = $result ? 'success' : 'error';
            }

            if (! empty($warning_messages)) {
                $message->addMessagesString($warning_messages);
                $message->isError(true);
            }

            $this->response->addHTML(Generator::getMessage(
                $message,
                $sql_query,
                $type
            ));
        }

        $this->render('table/operations/view', [
            'db' => $db,
            'table' => $table,
            'url_params' => $urlParams,
        ]);
    }
}
