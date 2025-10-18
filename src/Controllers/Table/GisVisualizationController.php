<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Gis\GisVisualizationSettings;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlParser\Components\Limit;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function array_search;
use function in_array;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Handles creation of the GIS visualizations.
 */
#[Route('/table/gis-visualization', ['GET', 'POST'])]
final readonly class GisVisualizationController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private ResponseFactory $responseFactory,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        // SQL query for retrieving GIS data
        $sqlQuery = $this->getSqlQuery();

        // Throw error if no sql query is set
        if ($sqlQuery === null) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))->getDisplay(),
            );

            return $this->response->response();
        }

        [$labelCandidates, $spatialCandidates] = $this->getCandidateColumns($sqlQuery);

        if ($spatialCandidates === []) {
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No spatial column found for this SQL query.'))->getDisplay(),
            );

            return $this->response->response();
        }

        // Get settings if any posted
        $visualizationSettings = $this->getVisualizationSettings(
            $spatialCandidates,
            $labelCandidates,
            $request->getParam('visualizationSettings'),
        );

        $rows = $this->getRows();
        $pos = $this->getPos();

        $visualization = GisVisualization::get($sqlQuery, $visualizationSettings, $rows, $pos);

        if (isset($_GET['saveToFile'])) {
            $response = $this->responseFactory->createResponse();
            $filename = $visualization->getSpatialColumn();
            ob_start();
            $visualization->toFile($filename, $_GET['fileFormat']);
            $output = ob_get_clean();

            return $response->write((string) $output);
        }

        $this->response->addScriptFiles(['vendor/openlayers/openlayers.js', 'table/gis_visualization.js']);

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        $useBaseLayer = isset($_POST['redraw']) ? isset($_POST['useBaseLayer']) : $visualization->hasSrid();

        /**
         * Displays the page
         */
        $urlParams = UrlParams::$params;
        $urlParams['goto'] = Url::getFromRoute($this->config->settings['DefaultTabDatabase']);
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
            'open_layers_data' => $visualization->asOl(),
        ]);

        $this->response->addHTML($html);

        return $this->response->response();
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
     * @psalm-param non-empty-list<string> $spatialCandidates
     * @psalm-param list<string> $labelCandidates
     */
    private function getVisualizationSettings(
        array $spatialCandidates,
        array $labelCandidates,
        mixed $settingsIn,
    ): GisVisualizationSettings {
        if (! is_array($settingsIn)) {
            return new GisVisualizationSettings(600, 450, $spatialCandidates[0]);
        }

        $labelColumn = '';
        if (
            isset($settingsIn['labelColumn']) &&
            in_array($settingsIn['labelColumn'], $labelCandidates, true)
        ) {
            $labelColumn = $settingsIn['labelColumn'];
        }

        // If spatial column is not set, use first geometric column as spatial column
        $spatialColumn = $spatialCandidates[array_search(
            $settingsIn['spatialColumn'] ?? null,
            $spatialCandidates,
            true,
        )];

        return new GisVisualizationSettings(600, 450, $spatialColumn, $labelColumn);
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
            return $this->config->settings['MaxRows'];
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

    /** @return array{list<string>, list<string>} */
    private function getCandidateColumns(string $sqlQuery): array
    {
        $parser = new Parser($sqlQuery);
        /** @var SelectStatement $statement */
        $statement = $parser->statements[0];
        $statement->limit = new Limit(0, 0);
        $limitedSqlQuery = $statement->build();

        $meta = $this->getColumnMeta($limitedSqlQuery);

        $labelCandidates = [];
        $spatialCandidates = [];
        foreach ($meta as $columnMeta) {
            if ($columnMeta->isMappedTypeGeometry) {
                $spatialCandidates[] = $columnMeta->name;
            } else {
                $labelCandidates[] = $columnMeta->name;
            }
        }

        return [$labelCandidates, $spatialCandidates];
    }
}
