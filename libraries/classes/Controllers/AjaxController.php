<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\AjaxController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;

/**
 * Class AjaxController
 * @package PhpMyAdmin\Controllers
 */
class AjaxController extends Controller
{
    /**
     * @var Config
     */
    private $config;

    /**
     * AjaxController constructor.
     *
     * @param \PhpMyAdmin\Response          $response Response instance
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface instance
     * @param Config                        $config   Config instance
     */
    public function __construct($response, $dbi, $config)
    {
        parent::__construct($response, $dbi);
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
     * @return array JSON
     */
    public function tables(array $params): array
    {
        return ['tables' => $this->dbi->getTables($params['db'])];
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function columns(array $params): array
    {
        return [
            'columns' => $this->dbi->getColumnNames(
                $params['db'],
                $params['table']
            ),
        ];
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function getConfig(array $params): array
    {
        return ['value' => $this->config->get($params['key'])];
    }

    /**
     * @param array $params Request parameters
     * @return true|\PhpMyAdmin\Message
     */
    public function setConfig(array $params)
    {
        return $this->config->setUserValue(
            null,
            $params['key'],
            json_decode($params['value'])
        );
    }
}
