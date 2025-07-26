import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import { CommonParams } from './modules/common.ts';
import { ajaxShowMessage } from './modules/ajax-message.ts';

/**
 * @fileoverview    functions used in GIS data editor
 *
 * @requires    jQuery
 */

let gisEditorLoaded = false;

let visualizationController;

function disposeGISEditorVisualization () {
    if (visualizationController) {
        visualizationController.dispose();
        visualizationController = undefined;
    }
}

/**
 * Initialize the visualization in the GIS data editor.
 */
function initGISEditorVisualization (olData: any[]) {
    visualizationController = new window.GisVisualizationController(olData);
}

function withIndex (prefix: string, ...index: Array<string|number>): string {
    let result = prefix;
    for (let i = 0; i < index.length; ++i) {
        result += '[' + index[i] + ']';
    }

    return result;
}

function makeDataLengthInput (prefix: string, length: number): string {
    return '<input type="hidden" name="' + prefix + '[data_length]" value="' + length + '">';
}

function makeAddButton (prefix: string, cls: string, label: string, type: string): string {
    return (
        '<button type="button"' +
        ' data-prefix="' + prefix + '"' +
        ' data-geometry-type="' + type + '"' +
        ' class="btn btn-secondary addJs ' + cls + '">' +
        '+ ' + label +
        '</button>'
    );
}

function makeCoordinateInputs (prefix: string, data): string {
    return (
        '<div class="col"><label class="input-group input-group-sm"><span class="input-group-text">' +
        window.Messages.strX +
        '</span><input class="form-control" type="text" name="' + prefix + '[x]" value="' + (data ? data.x : '') +  '">' +
        '</label></div>' +
        '<div class="col"><label class="input-group input-group-sm"><span class="input-group-text">' +
        window.Messages.strY +
        '</span><input class="form-control" type="text" name="' + prefix + '[y]" value="' + (data ? data.y : '') + '">' +
        '</label></div>'
    );
}

function makePointNInputs (prefix: string, index: number, data): string {
    return (
        '<div class="gis-coordinates row gx-2 align-items-center mb-2"><div class="col-3">' +
        window.Messages.strPoint + ' ' + (index + 1) + ':</div>' +
        makeCoordinateInputs(withIndex(prefix, index), data) +
        '</div>'
    );
}

function makePointInputs (prefix: string, data): string {
    return (
        '<div class="gis-coordinates-list card-body">' +
        '<div class="gis-coordinates row gx-2 align-items-center"><div class="col-3">' +
        window.Messages.strPoint + ':</div>' +
        makeCoordinateInputs(prefix, data) +
        '</div>' +
        '</div>'
    );
}

function makeMultiPointInputs (prefix: string, data): string {
    const d = data || [];
    const inputs = [];
    let i = 0;
    while (d[i] || i < 1) {
        inputs.push(makePointNInputs(prefix, i, d[i]));
        ++i;
    }

    return (
        '<div class="gis-coordinates-list card-body">' +
        inputs.join('') +
        makeDataLengthInput(prefix, i) +
        makeAddButton(prefix, 'addPoint', window.Messages.strAddPoint, 'POINT') +
        '</div>'
    );
}

function makeLineStringInputs (prefix: string, data, type: string): string {
    const d = data || [];
    const inputs = [];
    let i = 0;
    const min = type === 'POLYGON' || type === 'MULTIPOLYGON' ? 4 : 2;
    while (d[i] || i < min) {
        inputs.push(makePointNInputs(prefix, i, d[i]));
        ++i;
    }

    return (
        '<div class="gis-coordinates-list card-body">' +
        inputs.join('') +
        makeDataLengthInput(prefix, i) +
        makeAddButton(prefix, 'addPoint', window.Messages.strAddPoint, 'LINESTRING') +
        '</div>'
    );
}

function makeMultiLineStringInputs (prefix: string, data): string {
    const d = data || [];
    const inputs = [];
    let i = 0;
    while (d[i] || i < 1) {
        inputs.push(
            '<div class="card mb-3"><div class="card-header">',
            window.Messages.strLineString + ' ' + (i + 1) + ':</div>',
            makeLineStringInputs(withIndex(prefix, i), d[i], 'MULTILINESTRING'),
            '</div>'
        );

        ++i;
    }

    return (
        '<div class="gis-coordinates-list card-body">' +
        inputs.join('') +
        makeDataLengthInput(prefix, i) +
        makeAddButton(prefix, 'addLine', window.Messages.strAddLineString, 'MULTILINESTRING') +
        '</div>'
    );
}

function makePolygonInputs (prefix: string, data, type: string): string {
    const d = data || [];
    const inputs = [];
    let i = 0;
    while (d[i] || i < 1) {
        inputs.push(
            '<div class="card mb-3"><div class="card-header">',
            (i === 0 ? window.Messages.strOuterRing : window.Messages.strInnerRing + ' ' + i) + ':</div>',
            makeLineStringInputs(withIndex(prefix, i), d[i], type),
            '</div>'
        );

        ++i;
    }

    return (
        '<div class="gis-coordinates-list card-body">' +
        inputs.join('') +
        makeDataLengthInput(prefix, i) +
        makeAddButton(prefix, 'addLine', window.Messages.strAddInnerRing, 'POLYGON') +
        '</div>'
    );
}

function makeMultiPolygonInputs (prefix: string, data): string {
    const d = data || [];
    const inputs = [];
    let i = 0;
    while (d[i] || i < 1) {
        inputs.push(
            '<div class="card mb-3"><div class="card-header">',
            window.Messages.strPolygon + ' ' + (i + 1) + ':</div>',
            makePolygonInputs(withIndex(prefix, i), d[i], 'MULTIPOLYGON'),
            '</div>'
        );

        ++i;
    }

    return (
        '<div class="gis-coordinates-list card-body">' +
        inputs.join('') +
        makeDataLengthInput(prefix, i) +
        makeAddButton(prefix, 'addPolygon', window.Messages.strAddPolygon, 'MULTIPOLYGON') +
        '</div>'
    );
}

const INPUTS_GENERATOR = {
    POINT: makePointInputs,
    MULTIPOINT: makeMultiPointInputs,
    LINESTRING: makeLineStringInputs,
    MULTILINESTRING: makeMultiLineStringInputs,
    POLYGON: makePolygonInputs,
    MULTIPOLYGON: makeMultiPolygonInputs,
};

function makeGeometryCollectionGeometryInputs (prefix: string, index: number, data): string {
    const type = data ? data.gis_type : 'POINT';
    const fn = INPUTS_GENERATOR[type];
    const $geomType = $('#gis_type_template').contents().filter('select').clone();
    const select = $geomType.get(0) as HTMLSelectElement;
    select.value = type || 'POINT';
    select.selectedOptions[0].setAttribute('selected', 'selected');
    select.setAttribute('name', withIndex(prefix, index, 'gis_type'));

    return (
        '<div class="gis-geometry card mb-3">' +
        '<div class="gis-geometry-type card-header"><div class="row align-items-center"><div class="col-auto">' +
        window.Messages.strGeometry + ' ' + (index + 1) + ':</div><div class="col">' + select.outerHTML +
        '</div></div></div>' +
        fn(withIndex(prefix, index, type), data ? data[type] : null, type) +
        '</div>'
    );
}

function makeGeometryCollectionInputs (prefix: string, data): string {
    let i = 0;
    let inputs = [];
    while (data[i]) {
        inputs.push(makeGeometryCollectionGeometryInputs(prefix, i, data[i]));
        ++i;
    }

    return (
        '<div class="gis-geometry-list card"><div class="card-body">' +
        inputs.join('') +
        makeDataLengthInput('gis_data[GEOMETRYCOLLECTION]', i) +
        makeAddButton(prefix, 'addGeom', window.Messages.strAddGeometry, 'GEOMETRYCOLLECTION') +
        '</div></div>'
    );
}

function makeGeometryInputs (gisData): string {
    const type = gisData.gis_type;
    const geometry = gisData[0][type];
    const fn = INPUTS_GENERATOR[type];

    return '<div class="card">' + fn(withIndex('gis_data', 0, type), geometry, type) + '</div>';
}

/**
 * Loads JavaScript files and the GIS editor.
 *
 * @param {function} resolve
 */
function loadJSAndGISEditor (resolve) {
    let script: HTMLScriptElement;

    // OpenLayers.js is BIG and takes time. So asynchronous loading would not work.
    // Load the JS and do a callback to load the content for the GIS Editor.
    script = document.createElement('script');
    script.src = 'js/vendor/openlayers/openlayers.js';
    script.addEventListener('load', function () {
        resolve();
    });

    script.addEventListener('error', function () {
        resolve();
    });

    document.head.appendChild(script);

    script = document.createElement('script');
    script.src = 'js/table/gis_visualization.js';
    document.head.appendChild(script);

    gisEditorLoaded = true;
}

/**
 * Loads the GIS editor via AJAX
 *
 * @param value      current value of the geometry field
 * @param field      field name
 * @param type       geometry type
 * @param inputName name of the input field
 */
function loadGISEditor (value, field, type, inputName) {
    const $gisEditorModal = $('#gisEditorModal');
    const data = {
        'field': field,
        'value': value,
        'type': type,
        'input_name': inputName,
        'ajax_request': true,
        'server': CommonParams.get('server'),
    };
    $.post('index.php?route=/gis-data-editor', data, function (data) {
        if (typeof data === 'undefined' || data.success !== true) {
            ajaxShowMessage(data.error, false);

            return;
        }

        $gisEditorModal.find('.modal-title').first().html(data.gis_editor_title);
        $gisEditorModal.find('.modal-body').first().html(data.gis_editor);
        $gisEditorModal.on('hidden.bs.modal', disposeGISEditorVisualization);
        initGISEditorVisualization(
            JSON.parse($('#visualization-placeholder').attr('data-ol-data')),
        );

        const gisData = $('#gis_data').data('gisData');
        if (gisData) {
            let html;
            if (gisData.gis_type === 'GEOMETRYCOLLECTION') {
                html = makeGeometryCollectionInputs('gis_data', gisData);
            } else {
                html = makeGeometryInputs(gisData);
            }

            $('#gis_data').append(html);
        }
    }, 'json');
}

/**
 * Opens up the dialog for the GIS data editor.
 *
 * @param value      current value of the geometry field
 * @param field      field name
 * @param type       geometry type
 * @param inputName name of the input field
 */
function openGISEditor (value, field, type, inputName) {
    if (gisEditorLoaded) {
        loadGISEditor(value, field, type, inputName);
    } else {
        loadJSAndGISEditor(loadGISEditor.bind(this, value, field, type, inputName));
    }
}

/**
 * Prepare and insert the GIS data in Well Known Text format
 * to the input field.
 */
function insertDataAndClose () {
    const $form = $('form#gis_data_editor_form');
    const inputName = $form.find('input[name=\'input_name\']').val();

    const argsep = CommonParams.get('arg_separator');
    const params = $form.serialize() + argsep + 'generate=true' + argsep + 'ajax_request=true';
    $.post('index.php?route=/gis-data-editor', params, function (data) {
        if (typeof data === 'undefined' || data.success !== true) {
            ajaxShowMessage(data.error, false);

            return;
        }

        $('input[name=\'' + inputName + '\']').val(data.result);
    }, 'json');
}

function onCoordinateEdit (data) {
    if (typeof data === 'undefined' || data.success !== true) {
        ajaxShowMessage(data.error, false);

        return;
    }

    disposeGISEditorVisualization();

    $('#visualization-placeholder > .visualization-target-svg').html(data.visualization);
    $('#gis_data_textarea').val(data.result);

    initGISEditorVisualization(data.openLayersData);
}

/**
 * Handles adding data points
 */
function addPoint () {
    const $a = $(this);
    const prefix = $a.data('prefix');
    const dataLength = this.parentElement.querySelectorAll(':scope > .gis-coordinates').length;
    $('input[name=\'' + prefix + '[data_length]' + '\']').val(dataLength + 1);

    const html = makePointNInputs(prefix, dataLength, null);
    $a.before(html);

    updateResult();
}

/**
 * Handles adding linestrings and inner rings
 */
function addLineStringOrInnerRing () {
    const $a = $(this);
    const prefix = $a.data('prefix');
    const type = $a.data('geometryType');
    const dataLength = this.parentElement.querySelectorAll(':scope > .card > .gis-coordinates-list').length;
    $('input[name=\'' + prefix + '[data_length]' + '\']').val(dataLength + 1);

    const label = type === 'MULTILINESTRING' ? window.Messages.strLineString : window.Messages.strInnerRing;
    const n = type === 'MULTILINESTRING' ? dataLength + 1 : dataLength;
    const html = makeLineStringInputs(withIndex(prefix, dataLength), null, type);
    $a.before('<div class="card mb-3"><div class="gis-geometry-type card-header">' + label + ' ' + n + ':</div>' + html + '</div>');

    updateResult();
}

/**
 * Handles adding polygons
 */
function addPolygon () {
    const $a = $(this);
    const prefix = $a.data('prefix');
    const dataLength = this.parentElement.querySelectorAll(':scope > .card > .gis-coordinates-list').length;
    $('input[name=\'' + prefix + '[data_length]' + '\']').val(dataLength + 1);

    const html = makePolygonInputs(withIndex(prefix, dataLength), null, 'MULTIPOLYGON');
    $a.before('<div class="card mb-3"><div class="gis-geometry-type card-header">' + window.Messages.strPolygon + ' ' + (dataLength +  1) + ':</div>' + html + '</div>');

    updateResult();
}

/**
 * Handles adding geoms
 */
function addGeometry () {
    const dataLength = this.parentElement.querySelectorAll(':scope > .gis-geometry').length;
    $('input[name="gis_data[GEOMETRYCOLLECTION][data_length]"]').val(dataLength + 1);

    const html = makeGeometryCollectionGeometryInputs('gis_data', dataLength, null);
    $(this).before(html);

    updateResult();
}

/**
 * Update the form on change of the GIS type.
 */
function onGeometryTypeChange () {
    const typeSelect = this as HTMLSelectElement;
    const prefix = typeSelect.getAttribute('name').match(/^(.*)\[gis_type\]$/)[1];
    const isSubGeom = prefix !== 'gis_data';
    const type = typeSelect.value;

    let html;
    if (isSubGeom) {
        const fn = INPUTS_GENERATOR[type];
        html = fn(withIndex(prefix, type), null, type);

        $(typeSelect.parentElement.parentElement.parentElement.nextElementSibling).replaceWith(html);
    } else {
        html = type === 'GEOMETRYCOLLECTION'
            ? makeGeometryCollectionInputs(prefix, {})
            : makeGeometryInputs({ 'gis_type': type, '0': {} });

        $(typeSelect.parentElement.parentElement.nextElementSibling).replaceWith(html);
    }

    updateResult();
}

/**
 * Trigger asynchronous calls on data change and update the output.
 */
function updateResult () {
    const $form = $('form#gis_data_editor_form');
    const argsep = CommonParams.get('arg_separator');
    const data = $form.serialize() + argsep + 'generate=true' + argsep + 'ajax_request=true';
    $.post('index.php?route=/gis-data-editor', data, onCoordinateEdit, 'json');
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('gis_data_editor.js', function () {
    $(document).off('click', '#gis_editor button.gis-copy-data');
    $(document).off('change', '#gis_editor input[type=\'text\']');
    $(document).off('change', '#gis_editor select.gis_type');
    $(document).off('click', '#gis_editor button.addJs.addPoint');
    $(document).off('click', '#gis_editor button.addJs.addLine');
    $(document).off('click', '#gis_editor button.addJs.addPolygon');
    $(document).off('click', '#gis_editor button.addJs.addGeom');
});

AJAX.registerOnload('gis_data_editor.js', function () {
    $(document).on('click', '#gis_editor button.gis-copy-data', insertDataAndClose);

    $(document).on('change', '#gis_editor input[type=\'text\']', updateResult);
    $(document).on('change', '#gis_editor select.gis_type', onGeometryTypeChange);

    $(document).on('click', '#gis_editor button.addJs.addPoint', addPoint);
    $(document).on('click', '#gis_editor button.addJs.addLine', addLineStringOrInnerRing);
    $(document).on('click', '#gis_editor button.addJs.addPolygon', addPolygon);
    $(document).on('click', '#gis_editor button.addJs.addGeom', addGeometry);
});

declare global {
    interface Window {
        openGISEditor: typeof openGISEditor;
    }
}

window.openGISEditor = openGISEditor;
