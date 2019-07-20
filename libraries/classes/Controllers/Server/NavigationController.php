<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\NavigationController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;
use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;

/**
 * Handles Server Navigation view
 *
 * @package PhpMyAdmin\Controllers
 */
class NavigationController extends AbstractController
{

    /**
     * @var array array of database details
     */
    private $databases = [];

    /**
     * @var int number of databases
     */
    private $databaseCount = 0;

    /**
     * @var string sort by column
     */
    private $sortBy;

    /**
     * @var string sort order of databases
     */
    private $sortOrder;

    /**
     * @var boolean whether to show database statistics
     */
    private $hasStatistics;

    /**
     * @var int position in list navigation
     */
    private $position;


    /**
     * Index action
     *
     * @param array $params Request parameters
     *
     * @return string
     */
    public function index(array $params): string
    {
        include ROOT_PATH . 'libraries/server_common.inc.php';

        $filterValue = ! empty($params['filter']) ? $params['filter'] : '';

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('server/variables.js');

        $variables = [];

        $this->getDatabases();
        $databases = $this->databases;
        foreach ($databases as $name => $value) {
            $tables = $this->dbi->getTables($value['SCHEMA_NAME']);
            foreach ($tables as $table) {
                $variables[] = [
                    'name' => $table,
                    'link' => 'sql.php?db=' . $value['SCHEMA_NAME'] . '&table=' . $table,
                    'value' => 'Table',

                ];
            }

            $variables[] = [
                'name' => $value['SCHEMA_NAME'],
                'link' => 'db_structure.php?db=' . $value['SCHEMA_NAME'] ,
                'value' => 'Database',

            ];
        }
        return $this->template->render('server/navigation/index', [
            'variables' => $variables,
            'filter_value' => $filterValue,
            'is_superuser' => $this->dbi->isSuperuser(),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);
    }

    /**
     * Handle the AJAX request for a single variable value
     *
     * @param array $params Request parameters
     *
     * @return array
     */
    public function getValue(array $params): array
    {
        // Send with correct charset
        header('Content-Type: text/html; charset=UTF-8');
        // Do not use double quotes inside the query to avoid a problem
        // when server is running in ANSI_QUOTES sql_mode
        $varValue = $this->dbi->fetchSingleRow(
            'SHOW GLOBAL VARIABLES WHERE Variable_name=\''
            . $this->dbi->escapeString($params['varName']) . '\';',
            'NUM'
        );

        $json = [];
        try {
            $type = KBSearch::getVariableType($params['varName']);
            if ($type === 'byte') {
                $json['message'] = implode(
                    ' ',
                    Util::formatByteDown($varValue[1], 3, 3)
                );
            } else {
                throw new KBException("Not a type=byte");
            }
        } catch (KBException $e) {
            $json['message'] = $varValue[1];
        }

        return $json;
    }

    /**
     * Returns database list
     *
     *
     * @return array
     */
    private function getDatabases()
    {
        $this->sortBy = 'SCHEMA_NAME';
        $this->sortOrder = 'asc';
        $this->hasStatistics = ! empty($params['statistics']);
        $this->position = ! empty($params['pos']) ? (int) $params['pos'] : 0;
        $this->databases = $this->dbi->getDatabasesFull(
            null,
            $this->hasStatistics,
            DatabaseInterface::CONNECT_USER,
            $this->sortBy,
            $this->sortOrder,
            $this->position,
            true
        );
        return;
    }
}
