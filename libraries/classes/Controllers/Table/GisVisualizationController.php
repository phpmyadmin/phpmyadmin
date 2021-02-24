<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_merge;

/**
 * Handles creation of the GIS visualizations.
 */
final class GisVisualizationController extends AbstractController
{
    /** @var GisVisualization */
    private $visualization;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $table, $dbi)
    {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $cfg, $url_params, $PMA_Theme, $db, $err_url;

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        // SQL query for retrieving GIS data
        $sqlQuery = '';
        if (isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $sqlQuery = $_GET['sql_query'];
            }
        } elseif (isset($_POST['sql_query'])) {
            $sqlQuery = $_POST['sql_query'];
        }

        // Throw error if no sql query is set
        if ($sqlQuery == '') {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))
            );

            return;
        }

        // Execute the query and return the result
        $result = $this->dbi->tryQuery($sqlQuery);
        // Get the meta data of results
        $meta = $this->dbi->getFieldsMeta($result);

        // Find the candidate fields for label column and spatial column
        $labelCandidates = [];
        $spatialCandidates = [];
        foreach ($meta as $column_meta) {
            if ($column_meta->type === 'geometry') {
                $spatialCandidates[] = $column_meta->name;
            } else {
                $labelCandidates[] = $column_meta->name;
            }
        }

        // Get settings if any posted
        $visualizationSettings = [];
        if (Core::isValid($_POST['visualizationSettings'], 'array')) {
            $visualizationSettings = $_POST['visualizationSettings'];
        }

        // Check mysql version
        $visualizationSettings['mysqlVersion'] = $this->dbi->getVersion();
        $visualizationSettings['isMariaDB'] = $this->dbi->isMariaDB();

        if (! isset($visualizationSettings['labelColumn'])
            && isset($labelCandidates[0])
        ) {
            $visualizationSettings['labelColumn'] = '';
        }

        // If spatial column is not set, use first geometric column as spatial column
        if (! isset($visualizationSettings['spatialColumn'])) {
            $visualizationSettings['spatialColumn'] = $spatialCandidates[0];
        }

        // Convert geometric columns from bytes to text.
        $pos = $_GET['pos'] ?? $_SESSION['tmpval']['pos'];
        if (isset($_GET['session_max_rows'])) {
            $rows = $_GET['session_max_rows'];
        } else {
            if ($_SESSION['tmpval']['max_rows'] !== 'all') {
                $rows = $_SESSION['tmpval']['max_rows'];
            } else {
                $rows = $GLOBALS['cfg']['MaxRows'];
            }
        }
        $this->visualization = GisVisualization::get(
            $sqlQuery,
            $visualizationSettings,
            $rows,
            $pos
        );

        if (isset($_GET['saveToFile'])) {
            $this->saveToFile($visualizationSettings['spatialColumn'], $_GET['fileFormat']);

            return;
        }

        $this->addScriptFiles([
            'vendor/openlayers/OpenLayers.js',
            'vendor/jquery/jquery.svg.js',
            'table/gis_visualization.js',
        ]);

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        if (! isset($_POST['displayVisualization'])) {
            if ($this->visualization->hasSrid()) {
                $visualizationSettings['choice'] = 'useBaseLayer';
            } else {
                unset($visualizationSettings['choice']);
            }
        }

        $this->visualization->setUserSpecifiedSettings($visualizationSettings);
        if ($visualizationSettings != null) {
            foreach ($this->visualization->getSettings() as $setting => $val) {
                if (isset($visualizationSettings[$setting])) {
                    continue;
                }

                $visualizationSettings[$setting] = $val;
            }
        }

        /**
         * Displays the page
         */
        $url_params['goto'] = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );
        $url_params['back'] = Url::getFromRoute('/sql');
        $url_params['sql_query'] = $sqlQuery;
        $downloadUrl = Url::getFromRoute('/table/gis-visualization', array_merge(
            $url_params,
            [
                'saveToFile' => true,
                'session_max_rows' => $rows,
                'pos' => $pos,
            ]
        ));
        $html = $this->template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $url_params,
            'download_url' => $downloadUrl,
            'label_candidates' => $labelCandidates,
            'spatial_candidates' => $spatialCandidates,
            'visualization_settings' => $visualizationSettings,
            'sql_query' => $sqlQuery,
            'visualization' => $this->visualization->toImage('svg'),
            'draw_ol' => $this->visualization->asOl(),
            'theme_image_path' => $PMA_Theme->getImgPath(),
        ]);

        $this->response->addHTML($html);
    }

    /**
     * @param string $filename File name
     * @param string $format   Save format
     */
    private function saveToFile(string $filename, string $format): void
    {
        $this->response->disable();
        $this->visualization->toFile($filename, $format);
    }
}
