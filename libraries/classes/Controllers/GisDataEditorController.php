<?php
/**
 * Editor for Geometry data types.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Gis\GisFactory;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Http\ServerRequest;

use function array_merge;
use function in_array;
use function intval;
use function is_array;
use function mb_strpos;
use function mb_strtoupper;
use function mb_substr;
use function substr;
use function trim;

/**
 * Editor for Geometry data types.
 */
class GisDataEditorController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['gis_data'] = $GLOBALS['gis_data'] ?? null;
        $GLOBALS['gis_types'] = $GLOBALS['gis_types'] ?? null;
        $GLOBALS['start'] = $GLOBALS['start'] ?? null;
        $GLOBALS['geom_type'] = $GLOBALS['geom_type'] ?? null;
        $GLOBALS['gis_obj'] = $GLOBALS['gis_obj'] ?? null;
        $GLOBALS['srid'] = $GLOBALS['srid'] ?? null;
        $GLOBALS['wkt'] = $GLOBALS['wkt'] ?? null;
        $GLOBALS['wkt_with_zero'] = $GLOBALS['wkt_with_zero'] ?? null;
        $GLOBALS['result'] = $GLOBALS['result'] ?? null;
        $GLOBALS['visualizationSettings'] = $GLOBALS['visualizationSettings'] ?? null;
        $GLOBALS['data'] = $GLOBALS['data'] ?? null;
        $GLOBALS['visualization'] = $GLOBALS['visualization'] ?? null;
        $GLOBALS['open_layers'] = $GLOBALS['open_layers'] ?? null;
        $GLOBALS['geom_count'] = $GLOBALS['geom_count'] ?? null;

        /** @var string|null $field */
        $field = $request->getParsedBodyParam('field');
        /** @var array|null $gisDataParam */
        $gisDataParam = $request->getParsedBodyParam('gis_data');
        /** @var string $type */
        $type = $request->getParsedBodyParam('type', '');
        /** @var string|null $value */
        $value = $request->getParsedBodyParam('value');
        /** @var string|null $generate */
        $generate = $request->getParsedBodyParam('generate');
        /** @var string|null $inputName */
        $inputName = $request->getParsedBodyParam('input_name');

        if (! isset($field)) {
            return;
        }

        // Get data if any posted
        $GLOBALS['gis_data'] = [];
        if (is_array($gisDataParam)) {
            $GLOBALS['gis_data'] = $gisDataParam;
        }

        $GLOBALS['gis_types'] = [
            'POINT',
            'MULTIPOINT',
            'LINESTRING',
            'MULTILINESTRING',
            'POLYGON',
            'MULTIPOLYGON',
            'GEOMETRYCOLLECTION',
        ];

        // Extract type from the initial call and make sure that it's a valid one.
        // Extract from field's values if available, if not use the column type passed.
        if (! isset($GLOBALS['gis_data']['gis_type'])) {
            if ($type !== '') {
                $GLOBALS['gis_data']['gis_type'] = mb_strtoupper($type);
            }

            if (isset($value) && trim($value) !== '') {
                $GLOBALS['start'] = substr($value, 0, 1) == "'" ? 1 : 0;
                $GLOBALS['gis_data']['gis_type'] = mb_substr(
                    $value,
                    $GLOBALS['start'],
                    (int) mb_strpos($value, '(') - $GLOBALS['start']
                );
            }

            if (
                ! isset($GLOBALS['gis_data']['gis_type'])
                || (! in_array($GLOBALS['gis_data']['gis_type'], $GLOBALS['gis_types']))
            ) {
                $GLOBALS['gis_data']['gis_type'] = $GLOBALS['gis_types'][0];
            }
        }

        $GLOBALS['geom_type'] = $GLOBALS['gis_data']['gis_type'];

        // Generate parameters from value passed.
        $GLOBALS['gis_obj'] = GisFactory::factory($GLOBALS['geom_type']);
        if ($GLOBALS['gis_obj'] === false) {
            return;
        }

        if (isset($value)) {
            $GLOBALS['gis_data'] = array_merge(
                $GLOBALS['gis_data'],
                $GLOBALS['gis_obj']->generateParams($value)
            );
        }

        // Generate Well Known Text
        $GLOBALS['srid'] = isset($GLOBALS['gis_data']['srid']) && $GLOBALS['gis_data']['srid'] != ''
            ? (int) $GLOBALS['gis_data']['srid'] : 0;
        $GLOBALS['wkt'] = $GLOBALS['gis_obj']->generateWkt($GLOBALS['gis_data'], 0);
        $GLOBALS['wkt_with_zero'] = $GLOBALS['gis_obj']->generateWkt($GLOBALS['gis_data'], 0, '0');
        $GLOBALS['result'] = "'" . $GLOBALS['wkt'] . "'," . $GLOBALS['srid'];

        // Generate SVG based visualization
        $GLOBALS['visualizationSettings'] = [
            'width' => 450,
            'height' => 300,
            'spatialColumn' => 'wkt',
            'mysqlVersion' => $GLOBALS['dbi']->getVersion(),
            'isMariaDB' => $GLOBALS['dbi']->isMariaDB(),
        ];
        $GLOBALS['data'] = [
            [
                'wkt' => $GLOBALS['wkt_with_zero'],
                'srid' => $GLOBALS['srid'],
            ],
        ];
        $GLOBALS['visualization'] = GisVisualization::getByData($GLOBALS['data'], $GLOBALS['visualizationSettings'])
            ->toImage('svg');

        $GLOBALS['open_layers'] = GisVisualization::getByData($GLOBALS['data'], $GLOBALS['visualizationSettings'])
            ->asOl();

        // If the call is to update the WKT and visualization make an AJAX response
        if ($generate) {
            $this->response->addJSON([
                'result' => $GLOBALS['result'],
                'visualization' => $GLOBALS['visualization'],
                'openLayers' => $GLOBALS['open_layers'],
            ]);

            return;
        }

        $GLOBALS['geom_count'] = 1;
        if ($GLOBALS['geom_type'] === 'GEOMETRYCOLLECTION') {
            $GLOBALS['geom_count'] = isset($GLOBALS['gis_data'][$GLOBALS['geom_type']]['geom_count'])
                ? intval($GLOBALS['gis_data'][$GLOBALS['geom_type']]['geom_count']) : 1;
            if (isset($GLOBALS['gis_data'][$GLOBALS['geom_type']]['add_geom'])) {
                $GLOBALS['geom_count']++;
            }
        }

        $templateOutput = $this->template->render('gis_data_editor_form', [
            'width' => $GLOBALS['visualizationSettings']['width'],
            'height' => $GLOBALS['visualizationSettings']['height'],
            'field' => $field,
            'input_name' => $inputName,
            'srid' => $GLOBALS['srid'],
            'visualization' => $GLOBALS['visualization'],
            'open_layers' => $GLOBALS['open_layers'],
            'gis_types' => $GLOBALS['gis_types'],
            'geom_type' => $GLOBALS['geom_type'],
            'geom_count' => $GLOBALS['geom_count'],
            'gis_data' => $GLOBALS['gis_data'],
            'result' => $GLOBALS['result'],
        ]);

        $this->response->addJSON(['gis_editor' => $templateOutput]);
    }
}
