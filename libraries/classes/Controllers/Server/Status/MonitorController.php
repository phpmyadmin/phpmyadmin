<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\Status\MonitorController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\SysInfo;
use PhpMyAdmin\Template;

/**
 * Class MonitorController
 * @package PhpMyAdmin\Controllers\Server\Status
 */
class MonitorController extends AbstractController
{
    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * MonitorController constructor.
     *
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param Data              $data     Data object
     * @param Monitor           $monitor  Monitor object
     */
    public function __construct($response, $dbi, Template $template, $data, $monitor)
    {
        parent::__construct($response, $dbi, $template, $data);
        $this->monitor = $monitor;
    }

    /**
     * @return string HTML
     */
    public function index(): string
    {
        $form = [
            'server_time' => microtime(true) * 1000,
            'server_os' => SysInfo::getOs(),
            'is_superuser' => $this->dbi->isSuperuser(),
            'server_db_isLocal' => $this->data->db_isLocal,
        ];

        $javascriptVariableNames = [];
        foreach ($this->data->status as $name => $value) {
            if (is_numeric($value)) {
                $javascriptVariableNames[] = $name;
            }
        }

        return $this->template->render('server/status/monitor/index', [
            'image_path' => $GLOBALS['pmaThemeImage'],
            'javascript_variable_names' => $javascriptVariableNames,
            'form' => $form,
        ]);
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function chartingData(array $params): array
    {
        $json = [];
        $json['message'] = $this->monitor->getJsonForChartingData(
            $params['requiredData'] ?? ''
        );

        return $json;
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function logDataTypeSlow(array $params): array
    {
        $json = [];
        $json['message'] = $this->monitor->getJsonForLogDataTypeSlow(
            (int) $params['time_start'],
            (int) $params['time_end']
        );

        return $json;
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function logDataTypeGeneral(array $params): array
    {
        $json = [];
        $json['message'] = $this->monitor->getJsonForLogDataTypeGeneral(
            (int) $params['time_start'],
            (int) $params['time_end'],
            (bool) $params['limitTypes'],
            (bool) $params['removeVariables']
        );

        return $json;
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function loggingVars(array $params): array
    {
        $json = [];
        $json['message'] = $this->monitor->getJsonForLoggingVars(
            $params['varName'],
            $params['varValue']
        );

        return $json;
    }

    /**
     * @param array $params Request parameters
     * @return array JSON
     */
    public function queryAnalyzer(array $params): array
    {
        $json = [];
        $json['message'] = $this->monitor->getJsonForQueryAnalyzer(
            $params['database'] ?? '',
            $params['query'] ?? ''
        );

        return $json;
    }
}
