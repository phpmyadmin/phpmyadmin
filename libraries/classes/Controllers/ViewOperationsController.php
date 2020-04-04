<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * View manipulations
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

    public function index(): void
    {
        global $sql_query, $url_params, $reload, $result, $warning_messages;
        global $db, $table;

        $tableObject = $this->dbi->getTable($db, $table);

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('table/operations.js');

        Common::table();

        $url_params['goto'] = $url_params['back'] = Url::getFromRoute('/view/operations');

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

        $this->response->addHTML($this->template->render('table/operations/view', [
            'db' => $db,
            'table' => $table,
            'url_params' => $url_params,
        ]));
    }
}
