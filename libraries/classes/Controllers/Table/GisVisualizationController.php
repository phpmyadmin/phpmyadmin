<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\GisVisualizationController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * Class GisVisualizationController
 *
 * @package PhpMyAdmin\Controllers
 */
class GisVisualizationController extends AbstractController
{
    /**
     * @var array
     */
    protected $url_params;

    /**
     * @var string
     */
    protected $sql_query;

    /**
     * @var array
     */
    protected $visualizationSettings;

    /**
     * @var GisVisualization
     */
    protected $visualization;

    /**
     * Constructor
     *
     * @param Response          $response              Response object
     * @param DatabaseInterface $dbi                   DatabaseInterface object
     * @param Template          $template              Template object
     * @param string            $db                    Database name
     * @param string            $table                 Table name
     * @param string            $sql_query             SQL query for retrieving GIS data
     * @param array             $url_params            array of URL parameters
     * @param string            $goto                  goto script
     * @param string            $back                  back script
     * @param array             $visualizationSettings visualization settings
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        $sql_query,
        array $url_params,
        $goto,
        $back,
        array $visualizationSettings
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);

        require_once ROOT_PATH . 'libraries/common.inc.php';
        require_once ROOT_PATH . 'libraries/db_common.inc.php';

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
        $save_format = $_GET['fileFormat'];
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
            $this->response->setRequestStatus(false);
            $this->response->addHTML(
                Message::error(__('No SQL query was set to fetch data.'))
            );
            return;
        }

        // Execute the query and return the result
        $result = $this->dbi->tryQuery($this->sql_query);
        // Get the meta data of results
        $meta = $this->dbi->getFieldsMeta($result);

        // Find the candidate fields for label column and spatial column
        $labelCandidates = [];
        $spatialCandidates = [];
        foreach ($meta as $column_meta) {
            if ($column_meta->type == 'geometry') {
                $spatialCandidates[] = $column_meta->name;
            } else {
                $labelCandidates[] = $column_meta->name;
            }
        }

        // Get settings if any posted
        if (Core::isValid($_POST['visualizationSettings'], 'array')) {
            $this->visualizationSettings = $_POST['visualizationSettings'];
        }

        // Check mysql version
        $this->visualizationSettings['mysqlVersion'] = $this->dbi->getVersion();
        $this->visualizationSettings['isMariaDB'] = $this->dbi->isMariaDB();

        if (! isset($this->visualizationSettings['labelColumn'])
            && isset($labelCandidates[0])
        ) {
            $this->visualizationSettings['labelColumn'] = '';
        }

        // If spatial column is not set, use first geometric column as spatial column
        if (! isset($this->visualizationSettings['spatialColumn'])) {
            $this->visualizationSettings['spatialColumn'] = $spatialCandidates[0];
        }

        // Convert geometric columns from bytes to text.
        $pos = isset($_GET['pos']) ? $_GET['pos']
            : $_SESSION['tmpval']['pos'];
        if (isset($_GET['session_max_rows'])) {
            $rows = $_GET['session_max_rows'];
        } else {
            if ($_SESSION['tmpval']['max_rows'] != 'all') {
                $rows = $_SESSION['tmpval']['max_rows'];
            } else {
                $rows = $GLOBALS['cfg']['MaxRows'];
            }
        }
        $this->visualization = GisVisualization::get(
            $this->sql_query,
            $this->visualizationSettings,
            $rows,
            $pos
        );

        if (isset($_GET['saveToFile'])) {
            $this->saveToFileAction();
            return;
        }

        $this->response->getHeader()->getScripts()->addFiles(
            [
                'vendor/openlayers/OpenLayers.js',
                'vendor/jquery/jquery.svg.js',
                'table/gis_visualization.js',
            ]
        );

        // If all the rows contain SRID, use OpenStreetMaps on the initial loading.
        if (! isset($_POST['displayVisualization'])) {
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
        $downloadUrl = 'tbl_gis_visualization.php' . Url::getCommon(
            array_merge(
                $this->url_params,
                [
                    'saveToFile' => true,
                    'session_max_rows' => $rows,
                    'pos' => $pos,
                ]
            )
        );
        $html = $this->template->render('table/gis_visualization/gis_visualization', [
            'url_params' => $this->url_params,
            'download_url' => $downloadUrl,
            'label_candidates' => $labelCandidates,
            'spatial_candidates' => $spatialCandidates,
            'visualization_settings' => $this->visualizationSettings,
            'sql_query' => $this->sql_query,
            'visualization' => $this->visualization->toImage('svg'),
            'draw_ol' => $this->visualization->asOl(),
            'pma_theme_image' => $GLOBALS['pmaThemeImage'],
        ]);

        $this->response->addHTML($html);
    }
}
