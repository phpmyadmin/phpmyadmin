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

    /**
     * @param array $params Request parameters
     */
    public function tables(array $params): void
    {
        $this->response->addJSON(['tables' => $this->dbi->getTables($params['database'])]);
    }

    /**
     * @param array $params Request parameters
     */
    public function columns(array $params): void
    {
        $this->response->addJSON([
            'columns' => $this->dbi->getColumnNames(
                $params['database'],
                $params['table']
            ),
        ]);
    }

    /**
     * @param array $params Request parameters
     */
    public function getConfig(array $params): void
    {
        if (! isset($params['key'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);
            return;
        }

        $this->response->addJSON(['value' => $this->config->get($params['key'])]);
    }

    /**
     * @param array $params Request parameters
     */
    public function setConfig(array $params): void
    {
        if (! isset($params['key'], $params['value'])) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON(['message' => Message::error()]);
            return;
        }

        $result = $this->config->setUserValue(
            null,
            $params['key'],
            json_decode($params['value'])
        );

        if ($result === true) {
            return;
        }

        $this->response->setRequestStatus(false);
        $this->response->addJSON(['message' => $result]);
    }
}
