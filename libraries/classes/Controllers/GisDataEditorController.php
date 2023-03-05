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
    private const GIS_TYPES = [
        'POINT',
        'MULTIPOINT',
        'LINESTRING',
        'MULTILINESTRING',
        'POLYGON',
        'MULTIPOLYGON',
        'GEOMETRYCOLLECTION',
    ];

    public function __invoke(ServerRequest $request): void
    {
        /** @var string|null $field */
        $field = $request->getParsedBodyParam('field');
        /** @var array|null $gisDataParam */
        $gisDataParam = $request->getParsedBodyParam('gis_data');
        /** @var string $type */
        $type = $request->getParsedBodyParam('type', '');
        /** @var string|null $value */
        $value = $request->getParsedBodyParam('value');
        /** @var string|null $inputName */
        $inputName = $request->getParsedBodyParam('input_name');

        if (! isset($field)) {
            return;
        }

        // Get data if any posted
        $gisData = is_array($gisDataParam) ? $gisDataParam : [];

        // Extract type from the initial call and make sure that it's a valid one.
        // Extract from field's values if available, if not use the column type passed.
        if (! isset($gisData['gis_type'])) {
            if ($type !== '') {
                $gisData['gis_type'] = mb_strtoupper($type);
            }

            if (isset($value) && trim($value) !== '') {
                $start = substr($value, 0, 1) == "'" ? 1 : 0;
                $gisData['gis_type'] = mb_substr(
                    $value,
                    $start,
                    (int) mb_strpos($value, '(') - $start,
                );
            }

            if (
                ! isset($gisData['gis_type'])
                || (! in_array($gisData['gis_type'], self::GIS_TYPES))
            ) {
                $gisData['gis_type'] = self::GIS_TYPES[0];
            }
        }

        $geomType = $gisData['gis_type'];

        // Generate parameters from value passed.
        $gisObj = GisFactory::factory($geomType);
        if ($gisObj === false) {
            return;
        }

        if (isset($value)) {
            $gisData = array_merge($gisData, $gisObj->generateParams($value));
        }

        // Generate Well Known Text
        $srid = (int) ($gisData['srid'] ?? 0);
        $wkt = $gisObj->generateWkt($gisData, 0);
        $wktWithZero = $gisObj->generateWkt($gisData, 0, '0');
        $result = "'" . $wkt . "'," . $srid;

        // Generate SVG based visualization
        $visualizationSettings = [
            'width' => 450,
            'height' => 300,
            'spatialColumn' => 'wkt',
        ];
        $data = [
            [
                'wkt' => $wktWithZero,
                'srid' => $srid,
            ],
        ];

        $visualization = GisVisualization::getByData($data, $visualizationSettings);
        $svg = $visualization->asSVG();
        $openLayers = $visualization->asOl();

        // If the call is to update the WKT and visualization make an AJAX response
        if ($request->hasBodyParam('generate')) {
            $this->response->addJSON([
                'result' => $result,
                'visualization' => $svg,
                'openLayers' => $openLayers,
            ]);

            return;
        }

        $geomCount = 1;
        if ($geomType === 'GEOMETRYCOLLECTION') {
            $geomCount = isset($gisData[$geomType]['geom_count'])
                ? intval($gisData[$geomType]['geom_count']) : 1;
            if (isset($gisData[$geomType]['add_geom'])) {
                $geomCount++;
            }
        }

        $templateOutput = $this->template->render('gis_data_editor_form', [
            'width' => $visualization->getWidth(),
            'height' => $visualization->getHeight(),
            'field' => $field,
            'input_name' => $inputName,
            'srid' => $srid,
            'visualization' => $svg,
            'open_layers' => $openLayers,
            'gis_types' => self::GIS_TYPES,
            'geom_type' => $geomType,
            'geom_count' => $geomCount,
            'gis_data' => $gisData,
            'result' => $result,
        ]);

        $this->response->addJSON(['gis_editor' => $templateOutput]);
    }
}
