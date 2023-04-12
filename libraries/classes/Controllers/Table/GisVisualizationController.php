<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function in_array;
use function is_array;
use function is_string;

/**
 * Handles creation of the GIS visualizations.
 */
final class GisVisualizationController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        // SQL query for retrieving GIS data
        $sqlQuery = $this->getSqlQuery();

        // Throw error if no sql query is set
        if ($sqlQuery === null) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay(),
            );

            return;
        }

        $meta = $this->getColumnMeta($sqlQuery);

        // Find the candidate fields for label column and spatial column
        $labelCandidates = [];
        $spatialCandidates = [];
        foreach ($meta as $columnMeta) {
            if ($columnMeta->isMappedTypeGeometry) {
                $spatialCandidates[] = $columnMeta->name;
            } else {
                $labelCandidates[] = $columnMeta->name;
            }
        }

        if ($spatialCandidates === []) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No spatial column found for this SQL query.'))->getDisplay(),
            );

            return;
        }

        // Get settings if any posted
        $visualizationSettings = $this->getVisualizationSettings($spatialCandidates, $labelCandidates);
        $visualizationSettings['width'] = 600;
        $visualizationSettings['height'] = 450;

        $rows = $this->getRows();
        $pos = $this->getPos();

        $visualization = GisVisualization::get($sqlQuery, $visualizationSettings, $rows, $pos);

        if (isset($_GET['saveToFile'])) {
            $this->response->disable();
            $filename = $visualization->getSpatialColumn();
            $visualization->toFile($filename, $_GET['fileFormat']);

            return;
        }

        $this->addScriptFiles(['vendor/openlayers/OpenLayers.js', 'table/gis_visualization.js']);

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        $useBaseLayer = isset($_POST['redraw']) ? isset($_POST['useBaseLayer']) : $visualization->hasSrid();

        /**
         * Displays the page
         */
        $urlParams = $GLOBALS['urlParams'] ?? [];
        $urlParams['goto'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $urlParams['back'] = Url::getFromRoute('/sql');
        $urlParams['sql_query'] = $sqlQuery;
        $urlParams['sql_signature'] = Core::signSqlQuery($sqlQuery);
        $downloadParams = [
            'saveToFile' => true,
            'session_max_rows' => $visualization->getRows(),
            'pos' => $visualization->getPos(),
            'visualizationSettings[spatialColumn]' => $visualization->getSpatialColumn(),
            'visualizationSettings[labelColumn]' => $visualization->getLabelColumn(),
        ];
        $downloadUrl = Url::getFromRoute('/table/gis-visualization', $downloadParams + $urlParams);

        $startAndNumberOfRowsFieldset = Generator::getStartAndNumberOfRowsFieldsetData($sqlQuery);

        $html = $this->template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $urlParams,
            'download_url' => $downloadUrl,
            'label_candidates' => $labelCandidates,
            'spatial_candidates' => $spatialCandidates,
            'spatialColumn' => $visualization->getSpatialColumn(),
            'labelColumn' => $visualization->getLabelColumn(),
            'width' => $visualization->getWidth(),
            'height' => $visualization->getHeight(),
            'start_and_number_of_rows_fieldset' => $startAndNumberOfRowsFieldset,
            'useBaseLayer' => $useBaseLayer,
            'visualization' => $visualization->asSVG(),
            'draw_ol' => $visualization->asOl(),
        ]);

        $this->response->addHTML($html);
    }

    /**
     * Reads the sql query from POST or GET
     *
     * @psalm-return non-empty-string|null
     */
    private function getSqlQuery(): string|null
    {
        $getQuery = $_GET['sql_query'] ?? null;
        $getSignature = $_GET['sql_signature'] ?? null;
        $postQuery = $_POST['sql_query'] ?? null;

        $sqlQuery = null;
        if (is_string($getQuery) && is_string($getSignature)) {
            if (Core::checkSqlQuerySignature($getQuery, $getSignature)) {
                $sqlQuery = $getQuery;
            }
        } elseif (is_string($postQuery)) {
            $sqlQuery = $postQuery;
        }

        return $sqlQuery === '' ? null : $sqlQuery;
    }

    /**
     * @param string[] $spatialCandidates
     * @param string[] $labelCandidates
     * @psalm-param non-empty-list<non-empty-string> $spatialCandidates
     * @psalm-param list<non-empty-string> $labelCandidates
     *
     * @return mixed[];
     * @psalm-return array{spatialColumn:non-empty-string,labelColumn?:non-empty-string}
     */
    private function getVisualizationSettings(array $spatialCandidates, array $labelCandidates): array
    {
        $settingsIn = [];
        // Download as PNG/SVG/PDF use _GET and the normal form uses _POST
        if (is_array($_POST['visualizationSettings'] ?? null)) {
            /** @var mixed[] $settingsIn */
            $settingsIn = $_POST['visualizationSettings'];
        } elseif (is_array($_GET['visualizationSettings'] ?? null)) {
            /** @var mixed[] $settingsIn */
            $settingsIn = $_GET['visualizationSettings'];
        }

        $settings = [];
        if (
            isset($settingsIn['labelColumn']) &&
            in_array($settingsIn['labelColumn'], $labelCandidates, true)
        ) {
            $settings['labelColumn'] = $settingsIn['labelColumn'];
        }

        // If spatial column is not set, use first geometric column as spatial column
        $spatialColumnValid = isset($settingsIn['spatialColumn']) &&
            in_array($settingsIn['spatialColumn'], $spatialCandidates, true);
        $settings['spatialColumn'] = $spatialColumnValid ? $settingsIn['spatialColumn'] : $spatialCandidates[0];

        return $settings;
    }

    private function getPos(): int
    {
        // Download as PNG/SVG/PDF use _GET and the normal form uses _POST
        return (int) ($_POST['pos'] ?? $_GET['pos'] ?? $_SESSION['tmpval']['pos']);
    }

    private function getRows(): int
    {
        if (isset($_POST['session_max_rows']) || isset($_GET['session_max_rows'])) {
            return (int) ($_POST['session_max_rows'] ?? $_GET['session_max_rows']);
        }

        if ($_SESSION['tmpval']['max_rows'] === 'all') {
            return (int) $GLOBALS['cfg']['MaxRows'];
        }

        return (int) $_SESSION['tmpval']['max_rows'];
    }

    /**
     * Execute the query and return the result
     *
     * @return FieldMetadata[]
     */
    private function getColumnMeta(string $sqlQuery): array
    {
        $result = $this->dbi->tryQuery($sqlQuery);

        return $result === false ? [] : $this->dbi->getFieldsMeta($result);
    }
}
