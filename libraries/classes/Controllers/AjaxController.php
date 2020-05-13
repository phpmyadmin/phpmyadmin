<?php
/**
 * Generic AJAX endpoint for getting information about database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use function json_decode;

/**
 * Generic AJAX endpoint for getting information about database
 */
class AjaxController extends AbstractController
{
    /** @var Config */
    private $config;

    /**
     * @param Response          $response Response instance
     * @param DatabaseInterface $dbi      DatabaseInterface instance
     * @param Template          $template Template object
     * @param Config            $config   Config instance
     */
    public function __construct($response, $dbi, Template $template, $config)
    {
        parent::__construct($response, $dbi, $template);
        $this->config = $config;
    }

    public function databases(): void
    {
        global $dblist;

        $this->response->addJSON(['databases' => $dblist->databases]);
    }

    public function tables(): void
    {
        if (! isset($_POST['db'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['tables' => $this->dbi->getTables($_POST['db'])]);
    }

    public function columns(): void
    {
        if (! isset($_POST['db'], $_POST['table'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON([
            'columns' => $this->dbi->getColumnNames(
                $_POST['db'],
                $_POST['table']
            ),
        ]);
    }

    public function getConfig(): void
    {
        if (! isset($_POST['key'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $this->response->addJSON(['value' => $this->config->get($_POST['key'])]);
    }

    public function setConfig(): void
    {
        if (! isset($_POST['key'], $_POST['value'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);

            return;
        }

        $result = $this->config->setUserValue(
            null,
            $_POST['key'],
            json_decode($_POST['value'])
        );

        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
