<?php
/**
 * Editor for Geometry data types.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Gis\GisVisualizationSettings;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

use function __;
use function array_merge;
use function in_array;
use function is_array;
use function mb_strtoupper;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Editor for Geometry data types.
 */
#[Route('/gis-data-editor', ['GET', 'POST'])]
final class GisDataEditorController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Template $template)
    {
    }

    private const GIS_TYPES = [
        'POINT',
        'MULTIPOINT',
        'LINESTRING',
        'MULTILINESTRING',
        'POLYGON',
        'MULTIPOLYGON',
        'GEOMETRYCOLLECTION',
    ];

    public function __invoke(ServerRequest $request): Response
    {
        $field = $request->getParsedBodyParamAsStringOrNull('field');
        /** @var array|null $gisDataParam */
        $gisDataParam = $request->getParsedBodyParam('gis_data');
        $type = $request->getParsedBodyParamAsString('type', 'GEOMETRY');
        $value = $request->getParsedBodyParamAsStringOrNull('value');
        $inputName = $request->getParsedBodyParamAsStringOrNull('input_name');

        if ($field === null) {
            return $this->response->response();
        }

        // Get data if any posted
        $gisData = is_array($gisDataParam) ? $gisDataParam : [];

        $gisData['gis_type'] = $this->extractGisType($gisData['gis_type'] ?? null, $type, $value);
        $geomType = $gisData['gis_type'];

        // Generate parameters from value passed.
        $gisObj = GisFactory::fromType($geomType);
        if ($gisObj === null) {
            return $this->response->response();
        }

        if ($value !== null) {
            $gisData = array_merge($gisData, $gisObj->generateParams($value));
        }

        // Generate Well Known Text
        $srid = (int) ($gisData['srid'] ?? 0);
        $wkt = $gisObj->generateWkt($gisData, 0);
        $wktWithZero = $gisObj->generateWkt($gisData, 0, '0');
        $result = "'" . $wkt . "'," . $srid;

        // Generate SVG based visualization
        $visualizationSettings = new GisVisualizationSettings(450, 300, 'wkt');
        $data = [['wkt' => $wktWithZero, 'srid' => $srid]];

        $visualization = GisVisualization::getByData($data, $visualizationSettings);
        $svg = $visualization->asSVG();
        $openLayersData = $visualization->asOl();

        // If the call is to update the WKT and visualization make an AJAX response
        if ($request->hasBodyParam('generate')) {
            $this->response->addJSON([
                'result' => $result,
                'visualization' => $svg,
                'openLayersData' => $openLayersData,
            ]);

            return $this->response->response();
        }

        $templateOutput = $this->template->render('gis_data_editor_form', [
            'width' => $visualization->getWidth(),
            'height' => $visualization->getHeight(),
            'field' => $field,
            'input_name' => $inputName,
            'srid' => $srid,
            'visualization' => $svg,
            'open_layers_data' => $openLayersData,
            'column_type' => mb_strtoupper($type),
            'gis_types' => self::GIS_TYPES,
            'geom_type' => $geomType,
            'gis_data' => $gisData,
            'result' => $result,
        ]);

        $this->response->addJSON('gis_editor', $templateOutput);
        $this->response->addJSON('gis_editor_title', sprintf(__('Value for the column "%s"'), $field));

        return $this->response->response();
    }

    /**
     * Extract type from the initial call and make sure that it's a valid one.
     * Extract from field's values if available, if not use the column type passed.
     */
    private function extractGisType(mixed $gisType, string $type, string|null $value): string
    {
        if (! in_array($gisType, self::GIS_TYPES, true)) {
            if ($type !== '') {
                $gisType = mb_strtoupper($type);
            }

            if ($value !== null && trim($value) !== '' && preg_match('/^\'?(\w+)\b/', $value, $matches) === 1) {
                $gisType = $matches[1];
            }

            if (! in_array($gisType, self::GIS_TYPES, true)) {
                $gisType = self::GIS_TYPES[0];
            }
        }

        return $gisType;
    }
}
