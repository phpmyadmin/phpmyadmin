<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\TableIndexesController
 *
 * @package PMA
 */

namespace PMA\Controllers\Table;

use PMA\Template;
use PMA_GIS_Visualization;
use PMA_Message;
use PMA\Controllers\TableController;

require_once 'libraries/common.inc.php';
require_once 'libraries/db_common.inc.php';
require_once 'libraries/controllers/TableController.class.php';
require_once 'libraries/gis/GIS_Visualization.class.php';
require_once 'libraries/gis/GIS_Factory.class.php';
require_once 'libraries/Message.class.php';

/**
 * Class TableGisVisualizationController
 *
 * @package PMA\Controllers\Table
 */
class TableGisVisualizationController extends TableController
{

    /**
     * @var array $url_params
     */
    protected $url_params;

    /**
     * @var string $sql_query
     */
    protected $sql_query;

    /**
     * @var array $visualizationSettings
     */
    protected $visualizationSettings;

    /**
     * @var PMA_GIS_Visualization $visualization
     */
    protected $visualization;

    /**
     * Constructor
     *
     * @param string $sql_query             SQL query for retrieving GIS data
     * @param array  $url_params            array of URL parameters
     * @param string $goto                  goto script
     * @param string $back                  back script
     * @param array  $visualizationSettings visualization settings
     */
    public function __construct(
        $sql_query,
        $url_params,
        $goto,
        $back,
        $visualizationSettings
    ) {
        parent::__construct();

        $this->sql_query = $sql_query;
        $this->url_params = $url_params;
        $this->url_params['goto'] = $goto;
        $this->url_params['back'] = $back;
        $this->visualizationSettings = $visualizationSettings;
    }

    /**
     * Save to file
     *
     * @return void
     */
    public function saveToFileAction()
    {
        $this->response->disable();
        $file_name = $this->visualizationSettings['spatialColumn'];
        $save_format = $_REQUEST['fileFormat'];
        $this->visualization->toFile($file_name, $save_format);
    }

    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        // Throw error if no sql query is set
        if (! isset($this->sql_query) || $this->sql_query == '') {
            $this->response->isSuccess(false);
            $this->response->addHTML(
                PMA_Message::error(__('No SQL query was set to fetch data.'))
            );
            return;
        }

        // Execute the query and return the result
        $result = $this->dbi->tryQuery($this->sql_query);
        // Get the meta data of results
        $meta = $this->dbi->getFieldsMeta($result);

        // Find the candidate fields for label column and spatial column
        $labelCandidates = array();
        $spatialCandidates = array();
        foreach ($meta as $column_meta) {
            if ($column_meta->type == 'geometry') {
                $spatialCandidates[] = $column_meta->name;
            } else {
                $labelCandidates[] = $column_meta->name;
            }
        }

        // Get settings if any posted
        if (PMA_isValid($_REQUEST['visualizationSettings'], 'array')) {
            $this->visualizationSettings = $_REQUEST['visualizationSettings'];
        }

        if (!isset($this->visualizationSettings['labelColumn'])
            && isset($labelCandidates[0])
        ) {
            $this->visualizationSettings['labelColumn'] = '';
        }

        // If spatial column is not set, use first geometric column as spatial column
        if (! isset($this->visualizationSettings['spatialColumn'])) {
            $this->visualizationSettings['spatialColumn'] = $spatialCandidates[0];
        }

        // Convert geometric columns from bytes to text.
        $pos = isset($_REQUEST['pos']) ? $_REQUEST['pos']
            : $_SESSION['tmpval']['pos'];
        if (isset($_REQUEST['session_max_rows'])) {
            $rows = $_REQUEST['session_max_rows'];
        } else {
            if ($_SESSION['tmpval']['max_rows'] != 'all') {
                $rows = $_SESSION['tmpval']['max_rows'];
            } else {
                $rows = $GLOBALS['cfg']['MaxRows'];
            }
        }
        $this->visualization = PMA_GIS_Visualization::get(
            $this->sql_query,
            $this->visualizationSettings,
            $rows,
            $pos
        );

        if (isset($_REQUEST['saveToFile'])) {
            $this->saveToFileAction();
            return;
        }

        $this->response->getHeader()->getScripts()->addFiles(
            array(
                'openlayers/OpenLayers.js',
                'jquery/jquery.svg.js',
                'tbl_gis_visualization.js',
                'OpenStreetMap.js'
            )
        );

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        if (! isset($_REQUEST['displayVisualization'])) {
            if ($this->visualization->hasSrid()) {
                $this->visualizationSettings['choice'] = 'useBaseLayer';
            } else {
                unset($this->visualizationSettings['choice']);
            }
        }

        $this->visualization->setUserSpecifiedSettings($this->visualizationSettings);
        if ($this->visualizationSettings != null) {
            foreach ($this->visualization->getSettings() as $setting => $val) {
                if (! isset($this->visualizationSettings[$setting])) {
                    $this->visualizationSettings[$setting] = $val;
                }
            }
        }

        /**
         * Displays the page
         */
        $this->url_params['sql_query'] = $this->sql_query;
        $downloadUrl = 'tbl_gis_visualization.php' . PMA_URL_getCommon(
            $this->url_params
        ) . '&saveToFile=true';
        $svgSupport = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER <= 8)
            ? false : true;
        $html = Template::get('table/gis_visualization/gis_visualization')->render(
            array(
                'url_params' => $this->url_params,
                'downloadUrl' => $downloadUrl,
                'labelCandidates' => $labelCandidates,
                'spatialCandidates' => $spatialCandidates,
                'visualizationSettings' => $this->visualizationSettings,
                'sql_query' => $this->sql_query,
                'visualization' => $this->visualization->toImage(
                    $svgSupport ? 'svg' : 'png'
                ),
                'svgSupport' => $svgSupport,
                'drawOl' => $this->visualization->asOl()
            )
        );

        $this->response->addHTML($html);
    }
}
