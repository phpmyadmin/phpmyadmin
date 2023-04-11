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
use function mb_strtoupper;
use function preg_match;
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

        $gisData = $this->validateGisData($gisData, $type, $value);
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
        $visualizationSettings = ['width' => 450, 'height' => 300, 'spatialColumn' => 'wkt'];
        $data = [['wkt' => $wktWithZero, 'srid' => $srid]];

        $visualization = GisVisualization::getByData($data, $visualizationSettings);
        $svg = $visualization->asSVG();
        $openLayers = $visualization->asOl();

        // If the call is to update the WKT and visualization make an AJAX response
        if ($request->hasBodyParam('generate')) {
            $this->response->addJSON(['result' => $result, 'visualization' => $svg, 'openLayers' => $openLayers]);

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

    /**
     * Extract type from the initial call and make sure that it's a valid one.
     * Extract from field's values if available, if not use the column type passed.
     *
     * @param mixed[] $gisData
     *
     * @return mixed[]
     * @psalm-return array{gis_type:value-of<self::GIS_TYPES>}&mixed[]
     */
    private function validateGisData(array $gisData, string $type, string|null $value): array
    {
        if (! isset($gisData['gis_type']) || ! in_array($gisData['gis_type'], self::GIS_TYPES, true)) {
            if ($type !== '') {
                $gisData['gis_type'] = mb_strtoupper($type);
            }

            if (isset($value) && trim($value) !== '' && preg_match('/^\'?(\w+)\b/', $value, $matches)) {
                $gisData['gis_type'] = $matches[1];
            }

            if (! isset($gisData['gis_type']) || (! in_array($gisData['gis_type'], self::GIS_TYPES, true))) {
                $gisData['gis_type'] = self::GIS_TYPES[0];
            }
        }

        return $gisData;
    }
}
