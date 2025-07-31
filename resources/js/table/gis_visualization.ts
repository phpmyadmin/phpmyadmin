import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { escapeHtml } from '../modules/functions/escape.ts';
import { Feature, Map, View } from 'ol';
import { Attribution, MousePosition, Zoom } from 'ol/control';
import { createStringXY } from 'ol/coordinate';
import { isEmpty } from 'ol/extent';
import { LineString, MultiLineString, MultiPoint, MultiPolygon, Point, Polygon } from 'ol/geom';
import { Tile, Vector as VectorLayer } from 'ol/layer';
import { get as getProjection } from 'ol/proj';
import { OSM, Vector as VectorSource } from 'ol/source';
import { Circle, Fill, Stroke, Style, Text } from 'ol/style';

/**
 * @fileoverview    functions used for visualizing GIS data
 *
 * @requires    jquery
 * @requires    jQueryUI
 */

class GisVisualization {
    protected target: HTMLElement;

    constructor (target: HTMLElement) {
        this.target = target;
    }

    /**
     * Make this visualization visible
     */
    public show () {
        $(this.target).show();
    }

    /**
     * Hide this visualization
     */
    public hide () {
        $(this.target).hide();
    }

    /**
     * Do cleanup when it is no longer needed
     */
    public dispose () {
        $(this.target).empty();
    }
}

interface UI {
    /**  The jQuery object representing the helper that's being dragged. */
    helper: JQuery,
    /**
     * Current CSS position of the helper. The values may be changed to modify
     * where the element will be positioned. This is useful for custom containment,
     * snapping, etc.
     */
    position: {top: number, left: number},
    /** Current offset position of the helper. */
    offset: {top: number, left: number},
}

const DEFAULT_SCALE = 1.0;
const ZOOM_FACTOR = 1.5;

class SvgVisualization extends GisVisualization {
    private svgEl: SVGSVGElement;

    private originalWidth: number;

    private originalHeight: number;

    private x = 0;

    private y = 0;

    private scale = DEFAULT_SCALE;

    private dragX = 0;

    private dragY = 0;

    private width: number;

    private height: number;

    private boundOnMouseWheel: any;

    private boundOnDragStart: any;

    private boundOnDrag: any;

    private boundOnPlotDblClick: any;

    private boundOnZoomInClick: any;

    private boundOnZoomWorldClick: any;

    private boundOnZoomOutClick: any;

    private boundOnLeftArrowClick: any;

    private boundOnRightArrowClick: any;

    private boundOnUpArrowClick: any;

    private boundOnDownArrowClick: any;

    private boundOnMouseMove: any;

    private boundOnMouseLeave: any;

    private boundOnResize: any;

    private boundOnButtonDragStart: any;

    /**
     * @param {HTMLElement} target
     */
    constructor (target) {
        super(target);

        this.svgEl = $(this.target).find('svg').get(0);
        this.originalWidth = $(this.svgEl).width();
        this.originalHeight = $(this.svgEl).height();
        this.width = this.originalWidth;
        this.height = this.originalHeight;

        this.boundOnMouseWheel = this.onMouseWheel.bind(this);
        this.boundOnDragStart = this.onDragStart.bind(this);
        this.boundOnDrag = this.onDrag.bind(this);
        this.boundOnPlotDblClick = this.onPlotDblClick.bind(this);
        this.boundOnZoomInClick = this.onZoomInClick.bind(this);
        this.boundOnZoomWorldClick = this.onZoomWorldClick.bind(this);
        this.boundOnZoomOutClick = this.onZoomOutClick.bind(this);
        this.boundOnLeftArrowClick = this.onLeftArrowClick.bind(this);
        this.boundOnRightArrowClick = this.onRightArrowClick.bind(this);
        this.boundOnUpArrowClick = this.onUpArrowClick.bind(this);
        this.boundOnDownArrowClick = this.onDownArrowClick.bind(this);
        this.boundOnMouseMove = this.onMouseMove.bind(this);
        this.boundOnMouseLeave = this.onMouseLeave.bind(this);
        this.boundOnResize = this.onResize.bind(this);
        this.boundOnButtonDragStart = () => false;

        this.addControls();
        this.bindEvents();
    }

    /**
     * Adds controls for zooming and panning.
     */
    private addControls () {
        $(this.target).append(
            // pan arrows
            '<img class="button left_arrow" src="' + window.themeImagePath + 'west-mini.png">',
            '<img class="button right_arrow" src="' + window.themeImagePath + 'east-mini.png">',
            '<img class="button up_arrow" src="' + window.themeImagePath + 'north-mini.png">',
            '<img class="button down_arrow" src="' + window.themeImagePath + 'south-mini.png">',
            // zoom controls
            '<img class="button zoom_in" src="' + window.themeImagePath + 'zoom-plus-mini.png">',
            '<img class="button zoom_world" src="' + window.themeImagePath + 'zoom-world-mini.png">',
            '<img class="button zoom_out" src="' + window.themeImagePath + 'zoom-minus-mini.png">'
        );
    }

    /**
     * Zooms and pans the visualization.
     */
    private zoomAndPan () {
        $('g', this.svgEl)
            .first()
            .attr('transform', 'translate(' + this.x + ', ' + this.y + ') scale(' + this.scale + ')');

        $('circle.vector', this.svgEl)
            .attr('r', 3 / this.scale)
            .attr('stroke-width', 2 / this.scale);

        $('polyline.vector', this.svgEl).attr('stroke-width', 2 / this.scale);
        $('path.vector', this.svgEl).attr('stroke-width', 0.5 / this.scale);
    }

    /**
     * Resizes the GIS visualization to fit into the space available.
     */
    private resize () {
        const visWidth = Math.ceil($(this.target).width() || $(this.svgEl).width());
        const visHeight = Math.ceil($(this.target).height() || $(this.svgEl).height());

        this.x += (visWidth - this.width) / 2;
        this.y += (visHeight - this.height) / 2;
        this.width = visWidth;
        this.height = visHeight;
        this.svgEl.setAttribute('width', String(visWidth));
        this.svgEl.setAttribute('height', String(visHeight));

        this.zoomAndPan();
    }

    private reset () {
        this.scale = DEFAULT_SCALE;
        this.x = 0;
        this.y = 0;
        this.width = this.originalWidth;
        this.height = this.originalHeight;

        this.resize();
    }

    private getRelativeCoords (event: JQuery.TriggeredEvent|MouseEvent): {x: number, y: number} {
        const position = $(this.target).offset();

        return {
            x: event.pageX - position.left,
            y: event.pageY - position.top
        };
    }

    /**
     * @param {WheelEvent} event
     */
    private onMouseWheel (event) {
        if (event.deltaY === 0) {
            return;
        }

        event.preventDefault();

        const relCoords = this.getRelativeCoords(event);
        const factor = event.deltaY > 0 ? 1 / ZOOM_FACTOR : ZOOM_FACTOR;
        // zoom
        this.scale *= factor;
        // zooming keeping the position under mouse pointer unmoved.
        this.x = relCoords.x - (relCoords.x - this.x) * factor;
        this.y = relCoords.y - (relCoords.y - this.y) * factor;

        this.zoomAndPan();
    }

    show () {
        super.show();

        this.resize();
    }

    dispose () {
        this.unbindEvents();

        super.dispose();
    }

    private bindEvents () {
        $(this.svgEl)
            .on('dblclick', this.boundOnPlotDblClick)
            .on('dragstart', this.boundOnDragStart)
            .on('drag', this.boundOnDrag)
            .on('mousemove', '.vector', this.boundOnMouseMove)
            .on('mouseleave', '.vector', this.boundOnMouseLeave)
            .draggable({
                cursor: 'move',
                // Give a fake element to be used for dragging display
                helper: () => document.createElement('div'),
            });

        this.svgEl.addEventListener('wheel', this.boundOnMouseWheel, { passive: false });

        $(this.target)
            .on('dragstart', '.button', this.boundOnButtonDragStart)
            .on('click', '.zoom_in', this.boundOnZoomInClick)
            .on('click', '.zoom_world', this.boundOnZoomWorldClick)
            .on('click', '.zoom_out', this.boundOnZoomOutClick)
            .on('click', '.left_arrow', this.boundOnLeftArrowClick)
            .on('click', '.right_arrow', this.boundOnRightArrowClick)
            .on('click', '.up_arrow', this.boundOnUpArrowClick)
            .on('click', '.down_arrow', this.boundOnDownArrowClick);

        $(window).on('resize', this.boundOnResize);
    }

    private unbindEvents () {
        $(this.svgEl)
            .off('dblclick', this.boundOnPlotDblClick)
            .off('dragstart', this.boundOnDragStart)
            .off('drag', this.boundOnDrag)
            .off('mousemove', '.vector', this.boundOnMouseMove)
            .off('mouseleave', '.vector', this.boundOnMouseLeave)
            .draggable('destroy');

        this.svgEl.removeEventListener('wheel', this.boundOnMouseWheel);

        $(this.target)
            .off('dragstart', '.button', this.boundOnButtonDragStart)
            .off('click', '.zoom_in', this.boundOnZoomInClick)
            .off('click', '.zoom_world', this.boundOnZoomWorldClick)
            .off('click', '.zoom_out', this.boundOnZoomOutClick)
            .off('click', '.left_arrow', this.boundOnLeftArrowClick)
            .off('click', '.right_arrow', this.boundOnRightArrowClick)
            .off('click', '.up_arrow', this.boundOnUpArrowClick)
            .off('click', '.down_arrow'), this.boundOnDownArrowClick;

        $(window).off('resize', this.boundOnResize);
    }

    private onDragStart (event: JQuery.TriggeredEvent, dd: UI) {
        this.dragX = dd.offset.left;
        this.dragY = dd.offset.top;
    }

    private onDrag (event: JQuery.TriggeredEvent, dd: UI) {
        this.x += Math.round(dd.offset.left - this.dragX);
        this.dragX = dd.offset.left;

        this.y += Math.round(dd.offset.top - this.dragY);
        this.dragY = dd.offset.top;

        this.zoomAndPan();
    }

    private onPlotDblClick (event: JQuery.TriggeredEvent) {
        this.scale *= ZOOM_FACTOR;
        // zooming in keeping the position under mouse pointer unmoved.
        const relCoords = this.getRelativeCoords(event);
        this.x = relCoords.x - (relCoords.x - this.x) * ZOOM_FACTOR;
        this.y = relCoords.y - (relCoords.y - this.y) * ZOOM_FACTOR;

        this.zoomAndPan();
    }

    private onZoomInClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        // zoom in
        this.scale *= ZOOM_FACTOR;

        // zooming in keeping the center unmoved.
        this.x = this.width / 2 - (this.width / 2 - this.x) * ZOOM_FACTOR;
        this.y = this.height / 2 - (this.height / 2 - this.y) * ZOOM_FACTOR;

        this.zoomAndPan();
    }

    private onZoomWorldClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        this.reset();
    }

    private onZoomOutClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        // zoom out
        this.scale /= ZOOM_FACTOR;

        // zooming out keeping the center unmoved.
        this.x = this.width / 2 - (this.width / 2 - this.x) / ZOOM_FACTOR;
        this.y = this.height / 2 - (this.height / 2 - this.y) / ZOOM_FACTOR;

        this.zoomAndPan();
    }

    private onLeftArrowClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        this.x += 100;

        this.zoomAndPan();
    }

    private onRightArrowClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        this.x -= 100;

        this.zoomAndPan();
    }

    private onUpArrowClick (event: JQuery.TriggeredEvent) {
        event.preventDefault();

        this.y += 100;

        this.zoomAndPan();
    }

    private onDownArrowClick (event) {
        event.preventDefault();

        this.y -= 100;

        this.zoomAndPan();
    }

    /**
     * Detect the mousemove event and show tooltips.
     */
    private onMouseMove (event: JQuery.TriggeredEvent) {
        $('#tooltip').remove();

        const target = event.target as SVGElement;
        const contents = target.getAttribute('data-label');
        if (!contents) {
            return;
        }

        $('<div id="tooltip">' + escapeHtml(contents) + '</div>')
            .css({
                top: event.pageY + 10,
                left: event.pageX + 10,
            })
            .appendTo('body')
            .fadeIn(200);
    }

    /**
     * Detect the mouseout event and hide tooltips.
     */
    private onMouseLeave () {
        $('#tooltip').remove();
    }

    private onResize () {
        this.resize();
    }
}

class OlVisualization extends GisVisualization {
    private olMap: any = undefined;

    private data: any[];

    /**
     * @param {function(HTMLElement): ol.Map} initFn
     */
    constructor (target: HTMLElement, data: any[]) {
        super(target);

        this.data = data;
    }

    drawOpenLayers () {
        if (! document.querySelector('script[src*="js/vendor/openlayers/openlayers.js"]')) {
            return undefined;
        }

        const olCss = 'js/vendor/openlayers/openlayers.css';
        if (! document.querySelector('link[rel="stylesheet"][href="' + olCss + '"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.type = 'text/css';
            link.href = olCss;
            document.head.appendChild(link);
        }

        const vectorSource = new VectorSource({
            features: getFeaturesFromOpenLayersData(this.data),
        });
        const map = new Map({
            target: this.target,
            layers: [
                new Tile({ source: new OSM() }),
                new VectorLayer({ source: vectorSource }),
            ],
            view: new View({ center: [0, 0], zoom: 4 }),
            controls: [
                new MousePosition({
                    coordinateFormat: createStringXY(4),
                    projection: 'EPSG:4326'
                }),
                new Zoom,
                new Attribution
            ]
        });

        const extent = vectorSource.getExtent();
        if (! isEmpty(extent)) {
            map.getView().fit(extent, { padding: [20, 20, 20, 20] });
        }

        return map;
    }

    show () {
        super.show();

        if (this.olMap) {
            this.olMap.updateSize();
        } else {
            this.olMap = this.drawOpenLayers();
        }
    }

    dispose () {
        if (this.olMap) {
            // Removes ol.Map's resize listener from window
            this.olMap.setTarget(null);
            this.olMap = undefined;
        }

        super.dispose();
    }
}

function getFeaturesFromOpenLayersData (geometries: any[]): any[] {
    let features = [];
    for (const geometry of geometries) {
        if (geometry.isCollection) {
            features = features.concat(getFeaturesFromOpenLayersData(geometry.geometries));

            continue;
        }

        let olGeometry: any = null;
        const style: any = {};
        if (geometry.geometry.type === 'LineString') {
            olGeometry = new LineString(geometry.geometry.coordinates);
            style.stroke = new Stroke(geometry.style.stroke);
        } else if (geometry.geometry.type === 'MultiLineString') {
            olGeometry = new MultiLineString(geometry.geometry.coordinates);
            style.stroke = new Stroke(geometry.style.stroke);
        } else if (geometry.geometry.type === 'MultiPoint') {
            olGeometry = new MultiPoint(geometry.geometry.coordinates);
            style.image = new Circle({
                fill: new Fill(geometry.style.circle.fill),
                stroke: new Stroke(geometry.style.circle.stroke),
                radius: geometry.style.circle.radius,
            });
        } else if (geometry.geometry.type === 'MultiPolygon') {
            olGeometry = new MultiPolygon(geometry.geometry.coordinates);
            style.fill = new Fill(geometry.style.fill);
            style.stroke = new Stroke(geometry.style.stroke);
        } else if (geometry.geometry.type === 'Point') {
            olGeometry = new Point(geometry.geometry.coordinates);
            style.image = new Circle({
                fill: new Fill(geometry.style.circle.fill),
                stroke: new Stroke(geometry.style.circle.stroke),
                radius: geometry.style.circle.radius,
            });
        } else if (geometry.geometry.type === 'Polygon') {
            olGeometry = new Polygon(geometry.geometry.coordinates);
            style.fill = new Fill(geometry.style.fill);
            style.stroke = new Stroke(geometry.style.stroke);
        } else {
            throw new Error();
        }

        if (geometry.geometry.srid !== 3857) {
            const source  = 'EPSG:' + (geometry.geometry.srid !== 0 ? geometry.geometry.srid : 4326);
            const sourceProj = getProjection(source);

            if (sourceProj) {
                olGeometry = olGeometry.transform(
                    source,
                    'EPSG:3857'
                );
            }
        }

        if (geometry.style.text) {
            style.text = new Text(geometry.style.text);
        }

        const feature = new Feature(olGeometry);
        feature.setStyle(new Style(style));
        features.push(feature);
    }

    return features;
}

class GisVisualizationController {
    private svgVis: SvgVisualization|undefined = undefined;

    private olVis: OlVisualization|undefined = undefined;

    private boundOnChoiceChange: any;

    private olData: any[];

    constructor (olData: any[]) {
        this.boundOnChoiceChange = this.onChoiceChange.bind(this);
        this.olData = olData;

        $(document).on('click', '#useOsmAsBaseLayerSwitch', this.boundOnChoiceChange);

        if (! document.querySelector('script[src*="js/vendor/openlayers/openlayers.js"]')) {
            $('#useOsmAsBaseLayerSwitch, #useOsmAsBaseLayerSwitchLabel').hide();
            $('#useOsmAsBaseLayerSwitch').prop('checked', false);
        }

        this.selectVisualization();
    }

    private onChoiceChange () {
        this.selectVisualization();
    }

    /**
     * Initially loads either SVG or OSM visualization based on the choice.
     */
    private selectVisualization () {
        const showOl = $('#useOsmAsBaseLayerSwitch').prop('checked') === true;
        const oldVis = showOl ? this.svgVis : this.olVis;
        if (oldVis) {
            oldVis.hide();
        }

        let newVis: GisVisualization;
        if (showOl) {
            if (!this.olVis) {
                this.olVis = new OlVisualization(
                    $('#visualization-placeholder > .visualization-target-ol').get(0),
                    this.olData,
                );
            }

            newVis = this.olVis;
        } else {
            if (!this.svgVis) {
                this.svgVis = new SvgVisualization(
                    $('#visualization-placeholder > .visualization-target-svg').get(0),
                );
            }

            newVis = this.svgVis;
        }

        newVis.show();
    }

    /**
     * Cleanup events when no longer needed
     */
    public dispose () {
        $(document).off('click', '#useOsmAsBaseLayerSwitch');

        if (this.svgVis) {
            this.svgVis.dispose();
        }

        if (this.olVis) {
            this.olVis.dispose();
        }
    }
}

declare global {
    interface Window {
        GisVisualizationController: typeof GisVisualizationController;
    }
}

window.GisVisualizationController = GisVisualizationController;

let visualizationController: GisVisualizationController|undefined;

/**
 * Ajax handlers for GIS visualization page
 *
 * Actions Ajaxified here:
 * Create visualization for the gis data
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/gis_visualization.js', function () {
    if (visualizationController) {
        visualizationController.dispose();
        visualizationController = undefined;
    }
});

AJAX.registerOnload('table/gis_visualization.js', function () {
    // If we are in GIS visualization, initialize it

    if ($('#gis_div').length > 0) {
        visualizationController = new GisVisualizationController(
            JSON.parse($('#visualization-placeholder').attr('data-ol-data'))
        );
    }
});
