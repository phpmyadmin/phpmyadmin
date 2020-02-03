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

    /**
     * @return array JSON
     */
    public function databases(): array
    {
        global $dblist;

        return ['databases' => $dblist->databases];
    }

    /**
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function tables(array $params): array
    {
        return ['tables' => $this->dbi->getTables($params['database'])];
    }

    /**
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function columns(array $params): array
    {
        return [
            'columns' => $this->dbi->getColumnNames(
                $params['database'],
                $params['table']
            ),
        ];
    }

    /**
     * @param array $params Request parameters
     *
     * @return array JSON
     */
    public function getConfig(array $params): array
    {
        if (! isset($params['key'])) {
            $this->response->setRequestStatus(false);
            return ['message' => Message::error()];
        }

        return ['value' => $this->config->get($params['key'])];
    }

    /**
     * @param array $params Request parameters
     *
     * @return array
     */
    public function setConfig(array $params): array
    {
        if (! isset($params['key'], $params['value'])) {
            $this->response->setRequestStatus(false);
            return ['message' => Message::error()];
        }

        $result = $this->config->setUserValue(
            null,
            $params['key'],
            json_decode($params['value'])
        );
        $json = [];
        if ($result !== true) {
            $this->response->setRequestStatus(false);
            $json['message'] = $result;
        }
        return $json;
    }
}
