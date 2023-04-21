import $ from 'jquery';
import { Functions } from '../modules/functions.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import refreshMainContent from '../modules/functions/refreshMainContent.ts';
import { Navigation } from '../modules/navigation.ts';
import { DesignerObjects } from './objects.ts';
import { DesignerHistory } from './history.ts';
import { DesignerPage } from './page.ts';
import { DesignerConfig } from './config.ts';

var change = 0; // variable to track any change in designer layout.
var showRelationLines = true;
var alwaysShowText = false;

const markSaved = function () {
    change = 0;
    $('#saved_state').text('');
};

const markUnsaved = function () {
    change = 1;
    $('#saved_state').text('*');
};

var mainDirection = $('html').attr('dir') === 'rtl' ? 'right' : 'left';
// Will be used to multiply the offsetLeft by -1 if the direction is rtl.
var directionEffect = mainDirection === 'right' ? -1 : 1;
var curClick = null;
var smS = 0;
var smAdd = 10;
var sLeft = 0;
var sRight = 0;
var onRelation = 0;
var onGrid = 0;
var onDisplayField = 0;
// relation_style: 0 - angular 1 - direct
var onAngularDirect = 1;
var clickField = 0;
var linkRelation = '';
var canvasWidth = 0;
var canvasHeight = 0;
var osnTabWidth = 0;
var osnTabHeight = 0;
var heightField = 7;
var globX;
var globY;
var layerMenuCurClick = 0;
window.fromArray = [];
var menuMoved = false;
var gridSize = 10;

// ------------------------------------------------------------------------------

const mouseDown = function (e) {
    globX = e.pageX;
    globY = e.pageY;

    if (e.target.tagName === 'SPAN') {
        curClick = e.target.parentNode.parentNode.parentNode.parentNode;
    } else if (e.target.className === 'tab_zag_2') {
        curClick = e.target.parentNode.parentNode.parentNode;
    } else if (e.target.id === 'layer_menu_sizer_btn') {
        layerMenuCurClick = 1;
    } else if (e.target.className === 'M_butt') {
        return false;
    }

    if (curClick !== null) {
        document.getElementById('canvas').style.display = 'none';
        curClick.style.zIndex = 2;
    }
};

const mouseMove = function (e) {
    if (e.preventDefault) {
        e.preventDefault();
    }

    var newDx = e.pageX;
    var newDy = e.pageY;

    var deltaX = globX - newDx;
    var deltaY = globY - newDy;

    globX = newDx;
    globY = newDy;

    if (curClick !== null) {
        DesignerMove.markUnsaved();

        var $curClick = $(curClick);

        var curX = parseFloat($curClick.attr('data-' + mainDirection) || $curClick.css(mainDirection));
        var curY = parseFloat($curClick.attr('data-top') || $curClick.css('top'));

        var newX = curX - directionEffect * deltaX;
        var newY = curY - deltaY;

        $curClick.attr('data-' + mainDirection, newX);
        $curClick.attr('data-top', newY);

        if (onGrid) {
            newX = parseInt((newX / gridSize).toString()) * gridSize;
            newY = parseInt((newY / gridSize).toString()) * gridSize;
        }

        if (newX < 0) {
            newX = 0;
        } else if (newY < 0) {
            newY = 0;
        }

        $curClick.css(mainDirection, newX + 'px');
        $curClick.css('top', newY + 'px');
    } else if (layerMenuCurClick) {
        if (menuMoved) {
            deltaX = -deltaX;
        }

        var $layerMenu = $('#layer_menu');
        var newWidth = $layerMenu.width() + directionEffect * deltaX;
        if (newWidth < 150) {
            newWidth = 150;
        }

        $layerMenu.width(newWidth);
    }

    if (onRelation || onDisplayField) {
        document.getElementById('designer_hint').style.left = (globX + 20) + 'px';
        document.getElementById('designer_hint').style.top = (globY + 20) + 'px';
    }
};

const mouseUp = function () {
    if (curClick !== null) {
        document.getElementById('canvas').style.display = 'inline-block';
        DesignerMove.reload();
        curClick.style.zIndex = 1;
        curClick = null;
    }

    layerMenuCurClick = 0;
};

// ------------------------------------------------------------------------------

const canvasPos = function () {
    canvasWidth = (document.getElementById('canvas') as HTMLCanvasElement).width = osnTabWidth - 3;
    canvasHeight = (document.getElementById('canvas') as HTMLCanvasElement).height = osnTabHeight - 3;
};

const osnTabPos = function () {
    osnTabWidth = parseInt(document.getElementById('osn_tab').style.width, 10);
    osnTabHeight = parseInt(document.getElementById('osn_tab').style.height, 10);
};

const setDefaultValuesFromSavedState = function () {
    if ($('#angular_direct_button').attr('class') === 'M_butt') {
        onAngularDirect = 0;
    } else {
        onAngularDirect = 1;
    }

    DesignerMove.angularDirect();

    if ($('#grid_button').attr('class') === 'M_butt') {
        onGrid = 1;
    } else {
        onGrid = 0;
    }

    DesignerMove.grid();

    var $relLineInvert = $('#relLineInvert');
    if ($relLineInvert.attr('class') === 'M_butt') {
        showRelationLines = false;
        $relLineInvert.attr('class', 'M_butt');
    } else {
        showRelationLines = true;
        $relLineInvert.attr('class', 'M_butt_Selected_down');
    }

    DesignerMove.relationLinesInvert();

    if ($('#pin_Text').attr('class') === 'M_butt_Selected_down') {
        alwaysShowText = true;
        DesignerMove.showText();
    } else {
        alwaysShowText = false;
    }

    var $keySbAll = $('#key_SB_all');
    if ($keySbAll.attr('class') === 'M_butt_Selected_down') {
        $keySbAll.trigger('click');
        $keySbAll.toggleClass('M_butt_Selected_down');
        $keySbAll.toggleClass('M_butt');
    }

    var $keyLeftRight = $('#key_Left_Right');
    if ($keyLeftRight.attr('class') === 'M_butt_Selected_down') {
        $keyLeftRight.trigger('click');
    }
};

const main = function () {
    // ---CROSS

    document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
    DesignerMove.osnTabPos();
    DesignerMove.canvasPos();
    DesignerMove.smallTabRefresh();
    DesignerMove.reload();
    DesignerMove.setDefaultValuesFromSavedState();
};

const resizeOsnTab = function () {
    var maxX = 0;
    var maxY = 0;
    for (var key in DesignerConfig.jTabs) {
        var kX = parseInt(document.getElementById(key).style[mainDirection], 10) + document.getElementById(key).offsetWidth;
        var kY = parseInt(document.getElementById(key).style.top, 10) + document.getElementById(key).offsetHeight;
        maxX = maxX < kX ? kX : maxX;
        maxY = maxY < kY ? kY : maxY;
    }

    osnTabWidth = maxX + 50;
    osnTabHeight = maxY + 50;
    DesignerMove.canvasPos();
};

/**
 * Draw a colored line
 *
 * @param {number} x1
 * @param {number} x2
 * @param {number} y1
 * @param {number} y2
 * @param {HTMLElement} osnTab
 * @param {string} colorTarget
 */
const drawLine0 = function (x1, x2, y1, y2, osnTab, colorTarget): void {
    DesignerMove.line0(
        x1 + directionEffect * osnTab.offsetLeft,
        y1 - osnTab.offsetTop,
        x2 + directionEffect * osnTab.offsetLeft,
        y2 - osnTab.offsetTop,
        DesignerMove.getColorByTarget(colorTarget)
    );
};

/**
 * refreshes display, must be called after state changes
 */
const reload = function () {
    DesignerMove.resizeOsnTab();
    var n;
    var x1;
    var x2;
    var a = [];
    var K;
    var key;
    var key2;
    var key3;
    DesignerMove.clear();
    var osnTab = document.getElementById('osn_tab');
    for (K in DesignerConfig.contr) {
        for (key in DesignerConfig.contr[K]) {
            // contr name
            for (key2 in DesignerConfig.contr[K][key]) {
                // table name
                for (key3 in DesignerConfig.contr[K][key][key2]) {
                    // field name
                    if (! (document.getElementById('check_vis_' + key2) as HTMLInputElement).checked ||
                        ! (document.getElementById('check_vis_' + DesignerConfig.contr[K][key][key2][key3][0]) as HTMLInputElement).checked) {
                        // if hide
                        continue;
                    }

                    var x1Left = document.getElementById(key2).offsetLeft + 1;
                    var x1Right = x1Left + document.getElementById(key2).offsetWidth;
                    var x2Left = document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetLeft;
                    var x2Right = x2Left + document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1Left - x2Left);
                    a[1] = Math.abs(x1Left - x2Right);
                    a[2] = Math.abs(x1Right - x2Left);
                    a[3] = Math.abs(x1Right - x2Right);
                    n = sLeft = sRight = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }

                    if (n === 1) {
                        x1 = x1Left - smS;
                        x2 = x2Right + smS;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }

                    if (n === 2) {
                        x1 = x1Right + smS;
                        x2 = x2Left - smS;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }

                    if (n === 3) {
                        x1 = x1Right + smS;
                        x2 = x2Right + smS;
                        sRight = 1;
                    }

                    if (n === 0) {
                        x1 = x1Left - smS;
                        x2 = x2Left - smS;
                        sLeft = 1;
                    }

                    var rowOffsetTop = 0;
                    var tabHideButton = document.getElementById('id_hide_tbody_' + key2);

                    if (tabHideButton.innerHTML === 'v') {
                        var fromColumn = document.getElementById(key2 + '.' + key3);
                        if (fromColumn) {
                            rowOffsetTop = fromColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y1 = document.getElementById(key2).offsetTop +
                        rowOffsetTop +
                        heightField;


                    rowOffsetTop = 0;
                    tabHideButton = document.getElementById('id_hide_tbody_' + DesignerConfig.contr[K][key][key2][key3][0]);
                    if (tabHideButton.innerHTML === 'v') {
                        var toColumn = document.getElementById(DesignerConfig.contr[K][key][key2][key3][0] +
                            '.' + DesignerConfig.contr[K][key][key2][key3][1]);
                        if (toColumn) {
                            rowOffsetTop = toColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y2 =
                        document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetTop +
                        rowOffsetTop +
                        heightField;

                    DesignerMove.drawLine0(
                        x1, x2, y1, y2, osnTab, DesignerConfig.contr[K][key][key2][key3][0] + '.' + DesignerConfig.contr[K][key][key2][key3][1]
                    );
                }
            }
        }
    }
};

/**
 * draws a line from x1:y1 to x2:y2 with color
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
const line = function (x1, y1, x2, y2, colorLine) {
    var canvas = (document.getElementById('canvas') as HTMLCanvasElement);
    var ctx = canvas.getContext('2d');
    ctx.strokeStyle = colorLine;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.stroke();
};

/**
 * draws a relation/constraint line, whether angular or not
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
const line0 = function (x1, y1, x2, y2, colorLine) {
    if (! showRelationLines) {
        return;
    }

    DesignerMove.circle(x1, y1, 3, 3, colorLine);
    DesignerMove.rect(x2 - 1, y2 - 2, 4, 4, colorLine);

    if (onAngularDirect) {
        DesignerMove.line2(x1, y1, x2, y2, colorLine);
    } else {
        DesignerMove.line3(x1, y1, x2, y2, colorLine);
    }
};

/**
 * draws a angular relation/constraint line
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
const line2 = function (x1, y1, x2, y2, colorLine) {
    var x1Local = x1;
    var x2Local = x2;

    if (sRight) {
        x1Local += smAdd;
        x2Local += smAdd;
    } else if (sLeft) {
        x1Local -= smAdd;
        x2Local -= smAdd;
    } else if (x1 < x2) {
        x1Local += smAdd;
        x2Local -= smAdd;
    } else {
        x1Local -= smAdd;
        x2Local += smAdd;
    }

    DesignerMove.line(x1, y1, x1Local, y1, colorLine);
    DesignerMove.line(x2, y2, x2Local, y2, colorLine);
    DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);
};

/**
 * draws a relation/constraint line
 * @param x1
 * @param y1
 * @param x2
 * @param y2
 * @param colorLine
 */
const line3 = function (x1, y1, x2, y2, colorLine) {
    var x1Local = x1;
    var x2Local = x2;

    if (sRight) {
        if (x1 < x2) {
            x1Local += x2 - x1 + smAdd;
            x2Local += smAdd;
        } else {
            x2Local += x1 - x2 + smAdd;
            x1Local += smAdd;
        }

        DesignerMove.line(x1, y1, x1Local, y1, colorLine);
        DesignerMove.line(x2, y2, x2Local, y2, colorLine);
        DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);

        return;
    }

    if (sLeft) {
        if (x1 < x2) {
            x2Local -= x2 - x1 + smAdd;
            x1Local -= smAdd;
        } else {
            x1Local -= x1 - x2 + smAdd;
            x2Local -= smAdd;
        }

        DesignerMove.line(x1, y1, x1Local, y1, colorLine);
        DesignerMove.line(x2, y2, x2Local, y2, colorLine);
        DesignerMove.line(x1Local, y1, x2Local, y2, colorLine);

        return;
    }

    var xS = (x1 + x2) / 2;
    DesignerMove.line(x1, y1, xS, y1, colorLine);
    DesignerMove.line(xS, y2, x2, y2, colorLine);
    DesignerMove.line(xS, y1, xS, y2, colorLine);
};

const circle = function (x, y, r, w, color) {
    var ctx = (document.getElementById('canvas') as HTMLCanvasElement).getContext('2d');
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineWidth = w;
    ctx.strokeStyle = color;
    ctx.arc(x, y, r, 0, 2 * Math.PI, true);
    ctx.stroke();
};

const clear = function () {
    var canvas = (document.getElementById('canvas') as HTMLCanvasElement);
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvasWidth, canvasHeight);
};

const rect = function (x1, y1, w, h, color) {
    var ctx = (document.getElementById('canvas') as HTMLCanvasElement).getContext('2d');
    ctx.fillStyle = color;
    ctx.fillRect(x1, y1, w, h);
};

// --------------------------- FULLSCREEN -------------------------------------
const toggleFullscreen = function () {
    var valueSent = '';
    var $img = $('#toggleFullscreen').find('img');
    var $span = $img.siblings('span');
    var $content = $('#page_content');
    const pageContent = document.getElementById('page_content');

    if (! document.fullscreenElement) {
        $img.attr('src', $img.data('exit'))
            .attr('title', $span.data('exit'));

        $span.text($span.data('exit'));
        $content
            .addClass('content_fullscreen')
            .css({ 'width': screen.width - 5, 'height': screen.height - 5 });

        $('#osn_tab').css({ 'width': screen.width + 'px', 'height': screen.height });
        valueSent = 'on';
        pageContent.requestFullscreen();
    } else {
        $img.attr('src', $img.data('enter'))
            .attr('title', $span.data('enter'));

        $span.text($span.data('enter'));
        $content.removeClass('content_fullscreen')
            .css({ 'width': 'auto', 'height': 'auto' });

        $('#osn_tab').css({ 'width': 'auto', 'height': 'auto' });
        document.exitFullscreen();
        valueSent = 'off';
    }

    DesignerMove.saveValueInConfig('full_screen', valueSent);
};

const addTableToTablesList = function (index, tableDom) {
    var db = $(tableDom).find('.small_tab_pref').attr('db');
    var table = $(tableDom).find('.small_tab_pref').attr('table_name');
    var dbEncoded = $(tableDom).find('.small_tab_pref').attr('db_url');
    var tableEncoded = $(tableDom).find('.small_tab_pref').attr('table_name_url');
    var tableIsChecked = $(tableDom).css('display') === 'block' ? 'checked' : '';
    var checkboxStatus = (tableIsChecked === 'checked') ? window.Messages.strHide : window.Messages.strShow;
    var $newTableLine = $('<tr>' +
        '    <td title="' + window.Messages.strStructure + '"' +
        '        width="1px"' +
        '        class="L_butt2_1">' +
        '        <img alt=""' +
        '            db="' + dbEncoded + '"' +
        '            table_name="' + tableEncoded + '"' +
        '            class="scroll_tab_struct"' +
        '            src="' + window.themeImagePath + 'designer/exec.png"/>' +
        '    </td>' +
        '    <td width="1px">' +
        '        <input class="scroll_tab_checkbox"' +
        '            title="' + checkboxStatus + '"' +
        '            id="check_vis_' + dbEncoded + '.' + tableEncoded + '"' +
        '            style="margin:0;"' +
        '            type="checkbox"' +
        '            value="' + dbEncoded + '.' + tableEncoded + '"' + tableIsChecked +
        '            />' +
        '    </td>' +
        '    <td class="designer_Tabs"' +
        '        designer_url_table_name="' + dbEncoded + '.' + tableEncoded + '">' + $('<div/>').text(db + '.' + table).html() + '</td>' +
        '</tr>');
    $('#id_scroll_tab table').first().append($newTableLine);
    $($newTableLine).find('.scroll_tab_struct').on('click', function () {
        DesignerMove.startTabUpd(db, table);
    });

    $($newTableLine).on('click', '.designer_Tabs2,.designer_Tabs', function () {
        DesignerMove.selectTab($(this).attr('designer_url_table_name'));
    });

    $($newTableLine).find('.scroll_tab_checkbox').on('click', function () {
        $(this).attr('title', function (i, currentvalue) {
            return currentvalue === window.Messages.strHide ? window.Messages.strShow : window.Messages.strHide;
        });

        DesignerMove.visibleTab(this, $(this).val());
    });

    var $tablesCounter = $('#tables_counter');
    $tablesCounter.text(parseInt($tablesCounter.text(), 10) + 1);
};

/**
 * This function shows modal with Go buttons where required in designer
 * @param {object} form
 * @param {string} heading
 * @param {string} type
 *
 * @return {object} modal;
 */
const displayModal = function (form, heading, type) {
    var modal = $(type);
    modal.modal('show');
    modal.find('.modal-body').first().html(form);
    $(type + 'Label').first().html(heading);

    return modal;
};

const addOtherDbTables = function () {
    var $selectDb = $('<select id="add_table_from"></select>');
    $selectDb.append('<option value="">' + window.Messages.strNone + '</option>');

    var $selectTable = $('<select id="add_table"></select>');
    $selectTable.append('<option value="">' + window.Messages.strNone + '</option>');

    $.post('index.php?route=/sql', {
        'ajax_request': true,
        'sql_query': 'SHOW databases;',
        'server': CommonParams.get('server')
    }, function (data) {
        $(data.message).find('table.table_results.data.ajax').find('td.data').each(function () {
            var val = $(this)[0].innerText;
            $selectDb.append($('<option></option>').val(val).text(val));
        });
    });

    var $form = $('<form action="" class="ajax"></form>')
        .append($selectDb).append($selectTable);
    var modal = DesignerMove.displayModal($form, window.Messages.strAddTables, '#designerGoModal');
    $('#designerModalGoButton').on('click', function () {
        var db = ($('#add_table_from').val() as string);
        var table = ($('#add_table').val() as string);

        // Check if table already imported or not.
        var $table = $('[id="' + encodeURIComponent(db) + '.' + encodeURIComponent(table) + '"]');
        if ($table.length !== 0) {
            ajaxShowMessage(
                window.sprintf(window.Messages.strTableAlreadyExists, db + '.' + table),
                undefined,
                'error'
            );

            return;
        }

        $.post('index.php?route=/database/designer', {
            'ajax_request': true,
            'dialog': 'add_table',
            'db': db,
            'table': table,
            'server': CommonParams.get('server')
        }, function (data) {
            var $newTableDom = $(data.message);
            $newTableDom.find('a').first().remove();

            var dbEncoded = $($newTableDom).find('.small_tab_pref').attr('db_url');
            var tableEncoded = $($newTableDom).find('.small_tab_pref').attr('table_name_url');

            if (typeof dbEncoded === 'string' && typeof tableEncoded === 'string') { // Do not try to add if attr not found !
                $('#container-form').append($newTableDom);
                DesignerMove.enableTableEvents(null, $newTableDom);
                DesignerMove.addTableToTablesList(null, $newTableDom);
                DesignerConfig.jTabs[dbEncoded + '.' + tableEncoded] = 1;
                DesignerMove.markUnsaved();
            }
        });

        $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
        modal.modal('hide');
    });

    $('#add_table_from').on('change', function () {
        if ($(this).val()) {
            var dbName = $(this).val();
            var sqlQuery = 'SHOW tables;';
            $.post('index.php?route=/sql', {
                'ajax_request': true,
                'sql_query': sqlQuery,
                'db': dbName,
                'server': CommonParams.get('server')
            }, function (data) {
                $selectTable.html('');
                var rows = $(data.message).find('table.table_results.data.ajax').find('td.data');
                if (rows.length === 0) {
                    $selectTable.append('<option value="">' + window.Messages.strNone + '</option>');
                }

                rows.each(function () {
                    var val = $(this)[0].innerText;
                    $selectTable.append($('<option></option>').val(val).text(val));
                });
            });
        }
    });
};

// ------------------------------ NEW ------------------------------------------
const newPage = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        DesignerMove.loadPage(-1);
    });
};

// ------------------------------ SAVE ------------------------------------------
// (del?) no for pdf
const save = function (url) {
    for (var key in DesignerConfig.jTabs) {
        (document.getElementById('t_x_' + key + '_') as HTMLInputElement).value = parseInt(document.getElementById(key).style.left, 10).toString();
        (document.getElementById('t_y_' + key + '_') as HTMLInputElement).value = parseInt(document.getElementById(key).style.top, 10).toString();
        (document.getElementById('t_v_' + key + '_') as HTMLInputElement).value = document.getElementById('id_tbody_' + key).style.display === 'none' ? '0' : '1';
        (document.getElementById('t_h_' + key + '_') as HTMLInputElement).value = (document.getElementById('check_vis_' + key) as HTMLInputElement).checked ? '1' : '0';
    }

    (document.getElementById('container-form') as HTMLFormElement).action = url;
    $('#container-form').trigger('submit');
};

const getUrlPos = function (forceString = undefined) {
    var key;
    if (DesignerConfig.designerTablesEnabled || forceString) {
        var poststr = '';
        var argsep = CommonParams.get('arg_separator');
        var i = 1;
        for (key in DesignerConfig.jTabs) {
            poststr += argsep + 't_x[' + i + ']=' + parseInt(document.getElementById(key).style.left, 10);
            poststr += argsep + 't_y[' + i + ']=' + parseInt(document.getElementById(key).style.top, 10);
            poststr += argsep + 't_v[' + i + ']=' + (document.getElementById('id_tbody_' + key).style.display === 'none' ? 0 : 1);
            poststr += argsep + 't_h[' + i + ']=' + ((document.getElementById('check_vis_' + key) as HTMLInputElement).checked ? 1 : 0);
            poststr += argsep + 't_db[' + i + ']=' + $(document.getElementById(key)).attr('db_url');
            poststr += argsep + 't_tbl[' + i + ']=' + $(document.getElementById(key)).attr('table_name_url');
            i++;
        }

        return poststr;
    } else {
        var coords = [];
        for (key in DesignerConfig.jTabs) {
            if ((document.getElementById('check_vis_' + key) as HTMLInputElement).checked) {
                var x = parseInt(document.getElementById(key).style.left, 10);
                var y = parseInt(document.getElementById(key).style.top, 10);
                var tbCoords = new DesignerObjects.TableCoordinate(
                    $(document.getElementById(key)).attr('db_url'),
                    $(document.getElementById(key)).attr('table_name_url'),
                    -1, x, y);
                coords.push(tbCoords);
            }
        }

        return coords;
    }
};

const save2 = function (callback) {
    if (DesignerConfig.designerTablesEnabled) {
        var argsep = CommonParams.get('arg_separator');
        var poststr = 'operation=savePage' + argsep + 'save_page=same' + argsep + 'ajax_request=true';
        poststr += argsep + 'server=' + DesignerConfig.server + argsep + 'db=' + encodeURIComponent(DesignerConfig.db) + argsep + 'selected_page=' + DesignerConfig.selectedPage;
        poststr += DesignerMove.getUrlPos();

        var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
        $.post('index.php?route=/database/designer', poststr, function (data) {
            if (data.success === false) {
                ajaxShowMessage(data.error, false);
            } else {
                ajaxRemoveMessage($msgbox);
                ajaxShowMessage(window.Messages.strModificationSaved);
                DesignerMove.markSaved();
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        var name = $('#page_name').html().trim();
        DesignerPage.saveToSelectedPage(DesignerConfig.db, DesignerConfig.selectedPage, name, DesignerMove.getUrlPos(), function () {
            DesignerMove.markSaved();
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

const submitSaveDialogAndClose = function (callback, modal) {
    var $form = ($('#save_page') as JQuery<HTMLFormElement>);
    var name = ($form.find('input[name="selected_value"]').val() as string).trim();
    if (name === '') {
        ajaxShowMessage(window.Messages.strEnterValidPageName, false);

        return;
    }

    modal.modal('hide');

    if (DesignerConfig.designerTablesEnabled) {
        var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
            if (data.success === false) {
                ajaxShowMessage(data.error, false);
            } else {
                ajaxRemoveMessage($msgbox);
                DesignerMove.markSaved();
                if (data.id) {
                    DesignerConfig.selectedPage = data.id;
                }

                $('#page_name').text(name);
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        DesignerPage.saveToNewPage(DesignerConfig.db, name, DesignerMove.getUrlPos(), function (page) {
            DesignerMove.markSaved();
            if (page.pgNr) {
                DesignerConfig.selectedPage = page.pgNr;
            }

            $('#page_name').text(page.pageDescr);
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

const save3 = function (callback = undefined) {
    if (DesignerConfig.selectedPage !== -1) {
        DesignerMove.save2(callback);
    } else {
        var $form = $('<form action="index.php?route=/database/designer" method="post" name="save_page" id="save_page" class="ajax"></form>')
            .append('<input type="hidden" name="server" value="' + DesignerConfig.server + '">')
            .append($('<input type="hidden" name="db" />').val(DesignerConfig.db))
            .append('<input type="hidden" name="operation" value="savePage">')
            .append('<input type="hidden" name="save_page" value="new">')
            .append('<label for="selected_value">' + window.Messages.strPageName +
                '</label>:<input type="text" name="selected_value">');
        var modal = DesignerMove.displayModal($form, window.Messages.strSavePage, '#designerGoModal');
        $form.on('submit', function (e) {
            e.preventDefault();
            DesignerMove.submitSaveDialogAndClose(callback, modal);
        });

        $('#designerModalGoButton').on('click', function () {
            var $form = $('#save_page');
            $form.trigger('submit');

            $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
            modal.modal('hide');
        });
    }
};

// ------------------------------ EDIT PAGES ------------------------------------------
const editPages = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        var $msgbox = ajaxShowMessage();
        $.post('index.php?route=/database/designer', {
            'ajax_request': true,
            'server': DesignerConfig.server,
            'db': DesignerConfig.db,
            'dialog': 'edit'
        }, function (data) {
            if (data.success === false) {
                ajaxShowMessage(data.error, false);
            } else {
                ajaxRemoveMessage($msgbox);

                if (! DesignerConfig.designerTablesEnabled) {
                    DesignerPage.createPageList(DesignerConfig.db, function (options) {
                        $('#selected_page').append(options);
                    });
                }

                var modal = DesignerMove.displayModal(data.message, window.Messages.strOpenPage, '#designerGoModal');
                $('#designerModalGoButton').on('click', function () {
                    var $form = $('#edit_delete_pages');
                    var selected = $form.find('select[name="selected_page"]').val();
                    if (selected === '0') {
                        ajaxShowMessage(window.Messages.strSelectPage, 2000);

                        return;
                    }

                    $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                    modal.modal('hide');
                    DesignerMove.loadPage(selected);
                });
            }
        }); // end $.post()
    });
};

// -----------------------------  DELETE PAGES ---------------------------------------
const deletePages = function () {
    var $msgbox = ajaxShowMessage();
    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': DesignerConfig.server,
        'db': DesignerConfig.db,
        'dialog': 'delete'
    }, function (data) {
        if (data.success === false) {
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);

            if (! DesignerConfig.designerTablesEnabled) {
                DesignerPage.createPageList(DesignerConfig.db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            var modal = DesignerMove.displayModal(data.message, window.Messages.strDeletePage, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                var $form = $('#edit_delete_pages');
                var selected = ($form.find('select[name="selected_page"]').val() as string);
                if (selected === '0') {
                    ajaxShowMessage(window.Messages.strSelectPage, 2000);

                    return;
                }

                var $messageBox = ajaxShowMessage(window.Messages.strProcessingRequest);
                var deletingCurrentPage = parseInt(selected) === DesignerConfig.selectedPage;
                Functions.prepareForAjaxRequest($form);

                if (DesignerConfig.designerTablesEnabled) {
                    $.post($form.attr('action'), $form.serialize(), function (data) {
                        if (data.success === false) {
                            ajaxShowMessage(data.error, false);
                        } else {
                            ajaxRemoveMessage($messageBox);
                            if (deletingCurrentPage) {
                                DesignerMove.loadPage(null);
                            } else {
                                ajaxShowMessage(window.Messages.strSuccessfulPageDelete);
                            }
                        }
                    }); // end $.post()
                } else {
                    DesignerPage.deletePage(selected, function (success) {
                        if (! success) {
                            ajaxShowMessage('Error', false);
                        } else {
                            ajaxRemoveMessage($messageBox);
                            if (deletingCurrentPage) {
                                DesignerMove.loadPage(null);
                            } else {
                                ajaxShowMessage(window.Messages.strSuccessfulPageDelete);
                            }
                        }
                    });
                }

                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });
        }
    }); // end $.post()
};

// ------------------------------ SAVE AS PAGES ---------------------------------------
const saveAs = function () {
    var $msgbox = ajaxShowMessage();
    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': DesignerConfig.server,
        'db': DesignerConfig.db,
        'dialog': 'save_as'
    }, function (data) {
        if (data.success === false) {
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);

            if (! DesignerConfig.designerTablesEnabled) {
                DesignerPage.createPageList(DesignerConfig.db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            var modal = DesignerMove.displayModal(data.message, window.Messages.strSavePageAs, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                var $form = $('#save_as_pages');
                var selectedValue = ($form.find('input[name="selected_value"]').val() as string).trim();
                var $selectedPage = $form.find('select[name="selected_page"]');
                var choice = $form.find('input[name="save_page"]:checked').val();
                var name = '';

                if (choice === 'same') {
                    if ($selectedPage.val() === '0') {
                        ajaxShowMessage(window.Messages.strSelectPage, 2000);

                        return;
                    }

                    name = $selectedPage.find('option:selected').text();
                } else if (choice === 'new') {
                    if (selectedValue === '') {
                        ajaxShowMessage(window.Messages.strEnterValidPageName, 2000);

                        return;
                    }

                    name = selectedValue;
                }

                var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
                if (DesignerConfig.designerTablesEnabled) {
                    Functions.prepareForAjaxRequest($form);
                    $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
                        if (data.success === false) {
                            ajaxShowMessage(data.error, false);
                        } else {
                            ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (data.id) {
                                DesignerConfig.selectedPage = data.id;
                            }

                            DesignerMove.loadPage(DesignerConfig.selectedPage);
                        }
                    }); // end $.post()
                } else {
                    if (choice === 'same') {
                        var selectedPageId = $selectedPage.find('option:selected').val();
                        DesignerPage.saveToSelectedPage(DesignerConfig.db, selectedPageId, name, DesignerMove.getUrlPos(), function (page) {
                            ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (page.pgNr) {
                                DesignerConfig.selectedPage = page.pgNr;
                            }

                            DesignerMove.loadPage(DesignerConfig.selectedPage);
                        });
                    } else if (choice === 'new') {
                        DesignerPage.saveToNewPage(DesignerConfig.db, name, DesignerMove.getUrlPos(), function (page) {
                            ajaxRemoveMessage($msgbox);
                            DesignerMove.markSaved();
                            if (page.pgNr) {
                                DesignerConfig.selectedPage = page.pgNr;
                            }

                            DesignerMove.loadPage(DesignerConfig.selectedPage);
                        });
                    }
                }

                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });

            // select current page by default
            if (DesignerConfig.selectedPage !== -1) {
                $('select[name="selected_page"]').val(DesignerConfig.selectedPage);
            }
        }
    }); // end $.post()
};

const promptToSaveCurrentPage = function (callback) {
    if (change === 1 || DesignerConfig.selectedPage === -1) {
        var modal = DesignerMove.displayModal('<div>' + window.Messages.strLeavingPage + '</div>',
            window.Messages.strSavePage, '#designerPromptModal');
        $('#designerModalYesButton').on('click', function () {
            modal.modal('hide');
            DesignerMove.save3(callback);
        });

        $('#designerModalNoButton').on('click', function () {
            modal.modal('hide');
            callback();
        });
    } else {
        callback();
    }
};

// ------------------------------ EXPORT PAGES ---------------------------------------
const exportPages = function () {
    var $msgbox = ajaxShowMessage();
    var argsep = CommonParams.get('arg_separator');

    $.post('index.php?route=/database/designer', {
        'ajax_request': true,
        'server': DesignerConfig.server,
        'db': DesignerConfig.db,
        'dialog': 'export',
        'selected_page': DesignerConfig.selectedPage
    }, function (data) {
        if (data.success === false) {
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);

            var $form = $(data.message);
            if (! DesignerConfig.designerTablesEnabled) {
                $form.append('<input type="hidden" name="offline_export" value="true">');
            }

            $.each((DesignerMove.getUrlPos(true) as string).substring(1).split(argsep), function () {
                var pair = this.split('=');
                var input = $('<input type="hidden">');
                input.attr('name', pair[0]);
                input.attr('value', pair[1]);
                $form.append(input);
            });

            var $formatDropDown = $form.find('#plugins');
            $formatDropDown.on('change', function () {
                var format = $formatDropDown.val();
                $form.find('.format_specific_options').hide();
                $form.find('#' + format + '_options').show();
            }).trigger('change');

            var modal = DesignerMove.displayModal($form, window.Messages.strExportRelationalSchema, '#designerGoModal');
            $('#designerModalGoButton').on('click', function () {
                $('#id_export_pages').trigger('submit');
                $('#designerModalGoButton').off('click');// Unregister the event for other modals to not call this one
                modal.modal('hide');
            });
        }
    }); // end $.post()
};

const loadPage = function (page) {
    if (DesignerConfig.designerTablesEnabled) {
        var paramPage = '';
        var argsep = CommonParams.get('arg_separator');
        if (page !== null) {
            paramPage = argsep + 'page=' + page;
        }

        $('<a href="index.php?route=/database/designer&server=' + DesignerConfig.server + argsep + 'db=' + encodeURIComponent(DesignerConfig.db) + paramPage + '"></a>')
            .appendTo($('#page_content'))
            .trigger('click');
    } else {
        if (page === null) {
            DesignerPage.showTablesInLandingPage(DesignerConfig.db);
        } else if (page > -1) {
            DesignerPage.loadHtmlForPage(page);
        } else if (page === -1) {
            DesignerPage.showNewPageTables(true);
        }
    }

    DesignerMove.markSaved();
};

const grid = function () {
    var valueSent = '';
    if (! onGrid) {
        onGrid = 1;
        valueSent = 'on';
        document.getElementById('grid_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('grid_button').className = 'M_butt';
        onGrid = 0;
        valueSent = 'off';
    }

    DesignerMove.saveValueInConfig('snap_to_grid', valueSent);
};

const angularDirect = function () {
    var valueSent = '';
    if (onAngularDirect) {
        onAngularDirect = 0;
        valueSent = 'angular';
        document.getElementById('angular_direct_button').className = 'M_butt_Selected_down';
    } else {
        onAngularDirect = 1;
        valueSent = 'direct';
        document.getElementById('angular_direct_button').className = 'M_butt';
    }

    DesignerMove.saveValueInConfig('angular_direct', valueSent);
    DesignerMove.reload();
};

const saveValueInConfig = function (indexSent, valueSent) {
    $.post('index.php?route=/database/designer',
        {
            'operation': 'save_setting_value',
            'index': indexSent,
            'ajax_request': true,
            'server': DesignerConfig.server,
            'value': valueSent
        },
        function (data) {
            if (data.success === false) {
                ajaxShowMessage(data.error, false);
            }
        });
};

// ++++++++++++++++++++++++++++++ RELATION ++++++++++++++++++++++++++++++++++++++
const startRelation = function () {
    if (onDisplayField) {
        return;
    }

    if (! onRelation) {
        document.getElementById('foreign_relation').style.display = '';
        onRelation = 1;
        document.getElementById('designer_hint').innerHTML = window.Messages.strSelectReferencedKey;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('rel_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('rel_button').className = 'M_butt';
        clickField = 0;
        onRelation = 0;
    }
};

// table field
const clickTableField = function (db, T, f, pk) {
    var pkLocal = parseInt(pk);
    var argsep = CommonParams.get('arg_separator');
    if (onRelation) {
        if (! clickField) {
            // .style.display=='none'        .style.display = 'none'
            if (! pkLocal) {
                alert(window.Messages.strPleaseSelectPrimaryOrUniqueKey);

                return;// 0;
            }// PK

            if (DesignerConfig.jTabs[db + '.' + T] !== 1) {
                document.getElementById('foreign_relation').style.display = 'none';
            }

            clickField = 1;
            linkRelation = 'DB1=' + db + argsep + 'T1=' + T + argsep + 'F1=' + f;
            document.getElementById('designer_hint').innerHTML = window.Messages.strSelectForeignKey;
        } else {
            DesignerMove.startRelation(); // hidden hint...
            if (DesignerConfig.jTabs[db + '.' + T] !== 1 || ! pkLocal) {
                document.getElementById('foreign_relation').style.display = 'none';
            }

            var left = globX - (document.getElementById('layer_new_relation').offsetWidth >> 1);
            document.getElementById('layer_new_relation').style.left = left + 'px';
            var top = globY - document.getElementById('layer_new_relation').offsetHeight;
            document.getElementById('layer_new_relation').style.top = top + 'px';
            document.getElementById('layer_new_relation').style.display = 'block';
            linkRelation += argsep + 'DB2=' + db + argsep + 'T2=' + T + argsep + 'F2=' + f;
        }
    }

    if (onDisplayField) {
        var fieldNameToSend = decodeURIComponent(f);
        var newDisplayFieldClass = 'tab_field';
        var oldTabField = document.getElementById('id_tr_' + T + '.' + DesignerConfig.displayField[T]);
        // if is display field
        if (DesignerConfig.displayField[T] === f) {// The display field is already the one defined, user wants to remove it
            newDisplayFieldClass = 'tab_field';
            delete DesignerConfig.displayField[T];
            if (oldTabField) {// Clear the style
                // Set display field class on old item
                oldTabField.className = 'tab_field';
            }

            fieldNameToSend = '';
        } else {
            newDisplayFieldClass = 'tab_field_3';
            if (DesignerConfig.displayField[T]) { // Had a previous one, clear it
                if (oldTabField) {
                    // Set display field class on old item
                    oldTabField.className = 'tab_field';
                }

                delete DesignerConfig.displayField[T];
            }

            DesignerConfig.displayField[T] = f;

            var tabField = document.getElementById('id_tr_' + T + '.' + DesignerConfig.displayField[T]);
            if (tabField) {
                // Set new display field class
                tabField.className = newDisplayFieldClass;
            }
        }

        onDisplayField = 0;
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';

        var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
        $.post('index.php?route=/database/designer',
            {
                'operation': 'setDisplayField',
                'ajax_request': true,
                'server': DesignerConfig.server,
                'db': db,
                'table': T,
                'field': fieldNameToSend
            },
            function (data) {
                if (data.success === false) {
                    ajaxShowMessage(data.error, false);
                } else {
                    ajaxRemoveMessage($msgbox);
                    ajaxShowMessage(window.Messages.strModificationSaved);
                }
            });
    }
};

const newRelation = function () {
    document.getElementById('layer_new_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    linkRelation += argsep + 'server=' + DesignerConfig.server + argsep + 'db=' + DesignerConfig.db + argsep + 'db2=p';
    linkRelation += argsep + 'on_delete=' + (document.getElementById('on_delete') as HTMLSelectElement).value + argsep + 'on_update=' + (document.getElementById('on_update') as HTMLSelectElement).value;
    linkRelation += argsep + 'operation=addNewRelation' + argsep + 'ajax_request=true';

    var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
    $.post('index.php?route=/database/designer', linkRelation, function (data) {
        if (data.success === false) {
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);
            ajaxShowMessage(data.message);
            DesignerMove.loadPage(DesignerConfig.selectedPage);
        }
    }); // end $.post()
};

// -------------------------- create tables -------------------------------------
const startTableNew = function () {
    Navigation.update(CommonParams.set('table', ''));
    refreshMainContent('index.php?route=/table/create');
};

const startTabUpd = function (db, table) {
    Navigation.update(CommonParams.set('db', db));
    Navigation.update(CommonParams.set('table', table));
    refreshMainContent('index.php?route=/table/structure');
};

// --------------------------- hide tables --------------------------------------
// max/min all tables
const smallTabAll = function (idThis) {
    var icon = idThis.children[0];
    var valueSent = '';

    if (icon.alt === 'v') {
        $('.designer_tab .small_tab,.small_tab2').each(function (index, element) {
            if ($(element).text() === 'v') {
                DesignerMove.smallTab($(element).attr('table_name'), 0);
            }
        });

        icon.alt = '>';
        icon.src = icon.dataset.right;
        valueSent = 'v';
    } else {
        $('.designer_tab .small_tab,.small_tab2').each(function (index, element) {
            if ($(element).text() !== 'v') {
                DesignerMove.smallTab($(element).attr('table_name'), 0);
            }
        });

        icon.alt = 'v';
        icon.src = icon.dataset.down;
        valueSent = '>';
    }

    DesignerMove.saveValueInConfig('small_big_all', valueSent);
    $('#key_SB_all').toggleClass('M_butt_Selected_down');
    $('#key_SB_all').toggleClass('M_butt');
    DesignerMove.reload();
};

// invert max/min all tables
const smallTabInvert = function () {
    for (var key in DesignerConfig.jTabs) {
        DesignerMove.smallTab(key, 0);
    }

    DesignerMove.reload();
};

const relationLinesInvert = function () {
    showRelationLines = ! showRelationLines;
    DesignerMove.saveValueInConfig('relation_lines', showRelationLines);
    $('#relLineInvert').toggleClass('M_butt_Selected_down');
    $('#relLineInvert').toggleClass('M_butt');
    DesignerMove.reload();
};

const smallTabRefresh = function () {
    for (var key in DesignerConfig.jTabs) {
        if (document.getElementById('id_hide_tbody_' + key).innerHTML !== 'v') {
            DesignerMove.smallTab(key, 0);
        }
    }
};

const smallTab = function (t, reload) {
    var id = document.getElementById('id_tbody_' + t);
    var idThis = document.getElementById('id_hide_tbody_' + t);
    if (idThis.innerHTML === 'v') {
        // ---CROSS
        id.style.display = 'none';
        idThis.innerHTML = '>';
    } else {
        id.style.display = '';
        idThis.innerHTML = 'v';
    }

    if (reload) {
        DesignerMove.reload();
    }
};

const selectTab = function (t) {
    var idZag = document.getElementById('id_zag_' + t);
    if (idZag.className !== 'tab_zag_3') {
        document.getElementById('id_zag_' + t).className = 'tab_zag_2';
    } else {
        document.getElementById('id_zag_' + t).className = 'tab_zag';
    }

    // ----------
    var idT = document.getElementById(t);
    window.scrollTo(parseInt(idT.style.left, 10) - 300, parseInt(idT.style.top, 10) - 300);
    setTimeout(
        function () {
            document.getElementById('id_zag_' + t).className = 'tab_zag';
        },
        800
    );
};

const canvasClick = function (id, event) {
    var n = 0;
    var selected = 0;
    var a = [];
    var Key0;
    var Key1;
    var Key2;
    var Key3;
    var Key;
    var x1;
    var x2;
    var K;
    var key;
    var key2;
    var key3;
    var localX = event.pageX;
    var localY = event.pageY;
    localX -= $('#osn_tab').offset().left;
    localY -= $('#osn_tab').offset().top;
    DesignerMove.clear();
    var osnTab = document.getElementById('osn_tab');
    for (K in DesignerConfig.contr) {
        for (key in DesignerConfig.contr[K]) {
            for (key2 in DesignerConfig.contr[K][key]) {
                for (key3 in DesignerConfig.contr[K][key][key2]) {
                    if (! (document.getElementById('check_vis_' + key2) as HTMLInputElement).checked ||
                        ! (document.getElementById('check_vis_' + DesignerConfig.contr[K][key][key2][key3][0]) as HTMLInputElement).checked) {
                        continue; // if hide
                    }

                    var x1Left = document.getElementById(key2).offsetLeft + 1;// document.getElementById(key2+"."+key3).offsetLeft;
                    var x1Right = x1Left + document.getElementById(key2).offsetWidth;
                    var x2Left = document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetLeft;// +document.getElementById(contr[K][key2][key3][0]+"."+contr[K][key2][key3][1]).offsetLeft
                    var x2Right = x2Left + document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1Left - x2Left);
                    a[1] = Math.abs(x1Left - x2Right);
                    a[2] = Math.abs(x1Right - x2Left);
                    a[3] = Math.abs(x1Right - x2Right);
                    n = sLeft = sRight = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }

                    if (n === 1) {
                        x1 = x1Left - smS;
                        x2 = x2Right + smS;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }

                    if (n === 2) {
                        x1 = x1Right + smS;
                        x2 = x2Left - smS;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }

                    if (n === 3) {
                        x1 = x1Right + smS;
                        x2 = x2Right + smS;
                        sRight = 1;
                    }

                    if (n === 0) {
                        x1 = x1Left - smS;
                        x2 = x2Left - smS;
                        sLeft = 1;
                    }

                    var y1 = document.getElementById(key2).offsetTop + document.getElementById(key2 + '.' + key3).offsetTop + heightField;
                    var y2 = document.getElementById(DesignerConfig.contr[K][key][key2][key3][0]).offsetTop +
                        document.getElementById(DesignerConfig.contr[K][key][key2][key3][0] + '.' + DesignerConfig.contr[K][key][key2][key3][1]).offsetTop + heightField;

                    if (! selected && localX > x1 - 10 && localX < x1 + 10 && localY > y1 - 7 && localY < y1 + 7) {
                        DesignerMove.drawLine0(
                            x1, x2, y1, y2, osnTab, 'rgba(255,0,0,1)'
                        );

                        selected = 1;
                        Key0 = DesignerConfig.contr[K][key][key2][key3][0];
                        Key1 = DesignerConfig.contr[K][key][key2][key3][1];
                        Key2 = key2;
                        Key3 = key3;
                        Key = K;
                    } else {
                        DesignerMove.drawLine0(
                            x1, x2, y1, y2, osnTab,
                            DesignerConfig.contr[K][key][key2][key3][0] + '.' + DesignerConfig.contr[K][key][key2][key3][1]
                        );
                    }
                }
            }
        }
    }

    if (selected) {
        // select relations
        var left = globX - (document.getElementById('layer_upd_relation').offsetWidth >> 1);
        document.getElementById('layer_upd_relation').style.left = left + 'px';
        var top = globY - document.getElementById('layer_upd_relation').offsetHeight - 10;
        document.getElementById('layer_upd_relation').style.top = top + 'px';
        document.getElementById('layer_upd_relation').style.display = 'block';
        var argsep = CommonParams.get('arg_separator');
        linkRelation = 'T1=' + Key0 + argsep + 'F1=' + Key1 + argsep + 'T2=' + Key2 + argsep + 'F2=' + Key3 + argsep + 'K=' + Key;
    }
};

const updRelation = function () {
    document.getElementById('layer_upd_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    linkRelation += argsep + 'server=' + DesignerConfig.server + argsep + 'db=' + DesignerConfig.db;
    linkRelation += argsep + 'operation=removeRelation' + argsep + 'ajax_request=true';

    var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
    $.post('index.php?route=/database/designer', linkRelation, function (data) {
        if (data.success === false) {
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);
            ajaxShowMessage(data.message);
            DesignerMove.loadPage(DesignerConfig.selectedPage);
        }
    }); // end $.post()
};

const visibleTab = function (id, tN) {
    if (id.checked) {
        document.getElementById(tN).style.display = 'block';
    } else {
        document.getElementById(tN).style.display = 'none';
    }

    DesignerMove.reload();
    DesignerMove.markUnsaved();
};

// max/min all tables
const hideTabAll = function (idThis) {
    if (idThis.alt === 'v') {
        idThis.alt = '>';
        idThis.src = idThis.dataset.right;
    } else {
        idThis.alt = 'v';
        idThis.src = idThis.dataset.down;
    }

    var E = (document.getElementById('container-form') as HTMLFormElement);
    var EelementsLength = E.elements.length;
    for (var i = 0; i < EelementsLength; i++) {
        if ((E.elements[i] as HTMLInputElement).type === 'checkbox' && E.elements[i].id.startsWith('check_vis_')) {
            if (idThis.alt === 'v') {
                (E.elements[i] as HTMLInputElement).checked = true;
                document.getElementById((E.elements[i] as HTMLInputElement).value).style.display = '';
            } else {
                (E.elements[i] as HTMLInputElement).checked = false;
                document.getElementById((E.elements[i] as HTMLInputElement).value).style.display = 'none';
            }
        }
    }

    DesignerMove.reload();
};

const inArrayK = function (x, m) {
    var b = 0;
    for (var u in m) {
        if (x === u) {
            b = 1;
            break;
        }
    }

    return b;
};

const noHaveConstr = function (idThis) {
    var a = [];
    var K;
    var key;
    var key2;
    var key3;
    for (K in DesignerConfig.contr) {
        for (key in DesignerConfig.contr[K]) {
            // contr name
            for (key2 in DesignerConfig.contr[K][key]) {
                // table name
                for (key3 in DesignerConfig.contr[K][key][key2]) {
                    // field name
                    a[key2] = a[DesignerConfig.contr[K][key][key2][key3][0]] = 1; // exist constr
                }
            }
        }
    }

    if (idThis.alt === 'v') {
        idThis.alt = '>';
        idThis.src = idThis.dataset.right;
    } else {
        idThis.alt = 'v';
        idThis.src = idThis.dataset.down;
    }

    var E = (document.getElementById('container-form') as HTMLFormElement);
    var EelementsLength = E.elements.length;
    for (var i = 0; i < EelementsLength; i++) {
        if ((E.elements[i] as HTMLInputElement).type === 'checkbox' && E.elements[i].id.startsWith('check_vis_')) {
            if (! DesignerMove.inArrayK((E.elements[i] as HTMLInputElement).value, a)) {
                if (idThis.alt === 'v') {
                    (E.elements[i] as HTMLInputElement).checked = true;
                    document.getElementById((E.elements[i] as HTMLInputElement).value).style.display = '';
                } else {
                    (E.elements[i] as HTMLInputElement).checked = false;
                    document.getElementById((E.elements[i] as HTMLInputElement).value).style.display = 'none';
                }
            }
        }
    }
};

// max/min all tables
const showLeftMenu = function (idThis) {
    var icon = idThis.children[0];
    $('#key_Show_left_menu').toggleClass('M_butt_Selected_down');
    if (icon.alt === 'v') {
        document.getElementById('layer_menu').style.top = '0px';
        document.getElementById('layer_menu').style.display = 'block';
        icon.alt = '>';
        icon.src = icon.dataset.up;
    } else {
        document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
        document.getElementById('layer_menu').style.display = 'none';
        icon.alt = 'v';
        icon.src = icon.dataset.down;
    }
};

const sideMenuRight = function (idThis) {
    $('#side_menu').toggleClass('right');
    $('#layer_menu').toggleClass('float-start');
    var moveMenuIcon = $(idThis.getElementsByTagName('img')[0]);
    var resizeIcon = $('#layer_menu_sizer > img')
        .toggleClass('float-start')
        .toggleClass('float-end');

    var srcResizeIcon = resizeIcon.attr('src');
    resizeIcon.attr('src', resizeIcon.attr('data-right'));
    resizeIcon.attr('data-right', srcResizeIcon);

    var srcMoveIcon = moveMenuIcon.attr('src');
    moveMenuIcon.attr('src', moveMenuIcon.attr('data-right'));
    moveMenuIcon.attr('data-right', srcMoveIcon);

    menuMoved = ! menuMoved;
    DesignerMove.saveValueInConfig('side_menu', $('#side_menu').hasClass('right'));
    $('#key_Left_Right').toggleClass('M_butt_Selected_down');
    $('#key_Left_Right').toggleClass('M_butt');
};

const showText = function () {
    $('#side_menu').find('.hidable').show();
};

const hideText = function () {
    if (! alwaysShowText) {
        $('#side_menu').find('.hidable').hide();
    }
};

const pinText = function () {
    alwaysShowText = ! alwaysShowText;
    $('#pin_Text').toggleClass('M_butt_Selected_down');
    $('#pin_Text').toggleClass('M_butt');
    DesignerMove.saveValueInConfig('pin_text', alwaysShowText);
};

const startDisplayField = function () {
    if (onRelation) {
        return;
    }

    if (! onDisplayField) {
        onDisplayField = 1;
        document.getElementById('designer_hint').innerHTML = window.Messages.strChangeDisplay;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('display_field_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';
        onDisplayField = 0;
    }
};

var TargetColors = [];

const getColorByTarget = function (target) {
    var color = '';  // "rgba(0,100,150,1)";

    for (var targetColor in TargetColors) {
        if (TargetColors[targetColor][0] === target) {
            color = TargetColors[targetColor][1];
            break;
        }
    }

    if (color.length === 0) {
        var i = TargetColors.length + 1;
        var d = i % 6;
        var j = (i - d) / 6;
        j = j % 4;
        j++;
        var colorCase = [
            [1, 0, 0],
            [0, 1, 0],
            [0, 0, 1],
            [1, 1, 0],
            [1, 0, 1],
            [0, 1, 1]
        ];
        var a = colorCase[d][0];
        var b = colorCase[d][1];
        var c = colorCase[d][2];
        var e = (1 - (j - 1) / 6);

        var r = Math.round(a * 200 * e);
        var g = Math.round(b * 200 * e);
        b = Math.round(c * 200 * e);
        color = 'rgba(' + r + ',' + g + ',' + b + ',1)';

        TargetColors.push([target, color]);
    }

    return color;
};

const clickOption = function (dbName, tableName, columnName, tableDbNameUrl, optionColNameString) {
    var designerOptions = document.getElementById('designer_optionse');
    var left = globX - (designerOptions.offsetWidth >> 1);
    designerOptions.style.left = left + 'px';
    // var top = Glob_Y - designerOptions.offsetHeight - 10;
    designerOptions.style.top = (screen.height / 4) + 'px';
    designerOptions.style.display = 'block';
    (document.getElementById('ok_add_object_db_and_table_name_url') as HTMLInputElement).value = tableDbNameUrl;
    (document.getElementById('ok_add_object_db_name') as HTMLInputElement).value = dbName;
    (document.getElementById('ok_add_object_table_name') as HTMLInputElement).value = tableName;
    (document.getElementById('ok_add_object_col_name') as HTMLInputElement).value = columnName;
    document.getElementById('option_col_name').innerHTML = optionColNameString;
};

const closeOption = function () {
    document.getElementById('designer_optionse').style.display = 'none';
    (document.getElementById('rel_opt') as HTMLSelectElement).value = '--';
    (document.getElementById('Query') as HTMLTextAreaElement).value = '';
    (document.getElementById('new_name') as HTMLInputElement).value = '';
    (document.getElementById('operator') as HTMLSelectElement).value = '---';
    (document.getElementById('groupby') as HTMLInputElement).checked = false;
    (document.getElementById('h_rel_opt') as HTMLSelectElement).value = '--';
    (document.getElementById('h_operator') as HTMLSelectElement).value = '---';
    (document.getElementById('having') as HTMLTextAreaElement).value = '';
    (document.getElementById('orderby') as HTMLSelectElement).value = '---';
};

const selectAll = function (tableName, dbName, idSelectAll) {
    var parentIsChecked = $('#' + idSelectAll).is(':checked');
    var checkboxAll = ($('#container-form input[id_check_all=\'' + idSelectAll + '\']:checkbox') as JQuery<HTMLInputElement>);

    checkboxAll.each(function () {
        // already checked and then check parent
        if (parentIsChecked === true && this.checked) {
            // was checked, removing column from selected fields
            // trigger unchecked event
            this.click();
        }

        this.checked = parentIsChecked;
        this.disabled = parentIsChecked;
    });

    if (parentIsChecked) {
        DesignerHistory.selectField.push('`' + tableName + '`.*');
        window.fromArray.push(tableName);
    } else {
        var i;
        for (i = 0; i < DesignerHistory.selectField.length; i++) {
            if (DesignerHistory.selectField[i] === ('`' + tableName + '`.*')) {
                DesignerHistory.selectField.splice(i, 1);
            }
        }

        var k;
        for (k = 0; k < window.fromArray.length; k++) {
            if (window.fromArray[k] === tableName) {
                window.fromArray.splice(k, 1);
                break;
            }
        }
    }

    DesignerMove.reload();
};

const tableOnOver = function (idThis, val, buil) {
    var builLocal = parseInt(buil);
    if (! val) {
        document.getElementById('id_zag_' + idThis).className = 'tab_zag_2';
        if (builLocal) {
            document.getElementById('id_zag_' + idThis + '_2').className = 'tab_zag_2';
        }
    } else {
        document.getElementById('id_zag_' + idThis).className = 'tab_zag';
        if (builLocal) {
            document.getElementById('id_zag_' + idThis + '_2').className = 'tab_zag';
        }
    }
};

/**
 * This function stores selected column information in DesignerHistory.selectField[]
 * In case column is checked it add else it deletes
 *
 * @param {string} tableName
 * @param {string} colName
 * @param {string} checkboxId
 */
const storeColumn = function (tableName, colName, checkboxId) {
    var i;
    var k;
    var selectKeyField = '`' + tableName + '`.`' + colName + '`';
    if ((document.getElementById(checkboxId) as HTMLInputElement).checked === true) {
        DesignerHistory.selectField.push(selectKeyField);
        window.fromArray.push(tableName);
    } else {
        for (i = 0; i < DesignerHistory.selectField.length; i++) {
            if (DesignerHistory.selectField[i] === selectKeyField) {
                DesignerHistory.selectField.splice(i, 1);
                break;
            }
        }

        for (k = 0; k < window.fromArray.length; k++) {
            if (window.fromArray[k] === tableName) {
                window.fromArray.splice(k, 1);
                break;
            }
        }
    }
};

/**
 * This function builds object and adds them to DesignerHistory.historyArray
 * first it does a few checks on each object, then makes an object(where,rename,groupby,aggregate,orderby)
 * then a new history object is made and finally all these history objects are added to DesignerHistory.historyArray[]
 *
 * @param {string} dbName
 * @param {string} tableName
 * @param {string} colName
 * @param {string} dbTableNameUrl
 */
const addObject = function (dbName, tableName, colName, dbTableNameUrl) {
    var p;
    var whereObj;
    var rel = (document.getElementById('rel_opt') as HTMLSelectElement);
    var sum = 0;
    var init = DesignerHistory.historyArray.length;
    if (rel.value !== '--') {
        if ((document.getElementById('Query') as HTMLTextAreaElement).value === '') {
            ajaxShowMessage(window.sprintf(window.Messages.strQueryEmpty));

            return;
        }

        p = (document.getElementById('Query') as HTMLTextAreaElement);
        whereObj = new DesignerHistory.Where(rel.value, p.value);// make where object
        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, whereObj, tableName, DesignerConfig.hTabs[dbTableNameUrl], 'Where'));
        sum = sum + 1;
    }

    if ((document.getElementById('new_name') as HTMLInputElement).value !== '') {
        var renameObj = new DesignerHistory.Rename((document.getElementById('new_name') as HTMLInputElement).value);// make Rename object
        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, renameObj, tableName, DesignerConfig.hTabs[dbTableNameUrl], 'Rename'));
        sum = sum + 1;
    }

    if ((document.getElementById('operator') as HTMLSelectElement).value !== '---') {
        var aggregateObj = new DesignerHistory.Aggregate((document.getElementById('operator') as HTMLSelectElement).value);
        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, aggregateObj, tableName, DesignerConfig.hTabs[dbTableNameUrl], 'Aggregate'));
        sum = sum + 1;
        // make aggregate operator
    }

    if ((document.getElementById('groupby') as HTMLInputElement).checked === true) {
        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, 'GroupBy', tableName, DesignerConfig.hTabs[dbTableNameUrl], 'GroupBy'));
        sum = sum + 1;
        // make groupby
    }

    if ((document.getElementById('h_rel_opt') as HTMLSelectElement).value !== '--') {
        if ((document.getElementById('having') as HTMLTextAreaElement).value === '') {
            return;
        }

        whereObj = new DesignerHistory.Having(
            (document.getElementById('h_rel_opt') as HTMLSelectElement).value,
            (document.getElementById('having') as HTMLTextAreaElement).value,
            (document.getElementById('h_operator') as HTMLSelectElement).value
        );// make where object

        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, whereObj, tableName, DesignerConfig.hTabs[dbTableNameUrl], 'Having'));
        sum = sum + 1;
        // make having
    }

    if ((document.getElementById('orderby') as HTMLSelectElement).value !== '---') {
        var orderByObj = new DesignerHistory.OrderBy((document.getElementById('orderby') as HTMLSelectElement).value);
        DesignerHistory.historyArray.push(new DesignerHistory.HistoryObj(colName, orderByObj, tableName, DesignerConfig.hTabs[dbTableNameUrl], 'OrderBy'));
        sum = sum + 1;
        // make orderby
    }

    ajaxShowMessage(window.sprintf(window.Messages.strObjectsCreated, sum));
    // output sum new objects created
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(init, DesignerHistory.historyArray.length);
    DesignerMove.closeOption();
    $('#ab').accordion('refresh');
};

const enablePageContentEvents = function () {
    $(document).off('mousedown');
    $(document).off('mouseup');
    $(document).off('mousemove');
    $(document).on('mousedown', function (e) {
        DesignerMove.mouseDown(e);
    });

    $(document).on('mouseup', function () {
        DesignerMove.mouseUp();
    });

    $(document).on('mousemove', function (e) {
        DesignerMove.mouseMove(e);
    });
};

/**
 * This function enables the events on table items.
 * It helps to enable them on page loading and when a table is added on the fly.
 * @param {number} index
 * @param {object} element
 */
const enableTableEvents = function (index, element) {
    $(element).on('click', '.select_all_1', function () {
        DesignerMove.selectAll($(this).attr('table_name'), $(this).attr('db_name'), $(this).attr('id'));
    });

    $(element).on('click', '.small_tab,.small_tab2', function () {
        DesignerMove.smallTab($(this).attr('table_name'), 1);
    });

    $(element).on('click', '.small_tab_pref_1', function () {
        DesignerMove.startTabUpd($(this).attr('db_url'), $(this).attr('table_name_url'));
    });

    $(element).on('click', '.select_all_store_col', function () {
        DesignerMove.storeColumn($(this).attr('table_name'), $(this).attr('col_name'), $(this).attr('id'));
    });

    $(element).on('click', '.small_tab_pref_click_opt', function () {
        DesignerMove.clickOption(
            $(this).attr('db_name'),
            $(this).attr('table_name'),
            $(this).attr('col_name'),
            $(this).attr('db_table_name_url'),
            $(this).attr('option_col_name_modal')
        );
    });

    $(element).on('click', '.tab_field_2,.tab_field_3,.tab_field', function () {
        var params = ($(this).attr('click_field_param')).split(',');
        DesignerMove.clickField(params[3], params[0], params[1], params[2]);
    });

    $(element).find('.tab_zag_noquery').on('mouseover', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'), 0, $(this).attr('query_set'));
    });

    $(element).find('.tab_zag_noquery').on('mouseout', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'), 1, $(this).attr('query_set'));
    });

    $(element).find('.tab_zag_query').on('mouseover', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'), 0, 1);
    });

    $(element).find('.tab_zag_query').on('mouseout', function () {
        DesignerMove.tableOnOver($(this).attr('table_name'), 1, 1);
    });

    DesignerMove.enablePageContentEvents();
};

const DesignerMove = {
    markSaved: markSaved,
    markUnsaved: markUnsaved,
    mouseDown: mouseDown,
    mouseMove: mouseMove,
    mouseUp: mouseUp,
    canvasPos: canvasPos,
    osnTabPos: osnTabPos,
    setDefaultValuesFromSavedState: setDefaultValuesFromSavedState,
    main: main,
    resizeOsnTab: resizeOsnTab,
    drawLine0: drawLine0,
    reload: reload,
    line: line,
    line0: line0,
    line2: line2,
    line3: line3,
    circle: circle,
    clear: clear,
    rect: rect,
    toggleFullscreen: toggleFullscreen,
    addTableToTablesList: addTableToTablesList,
    displayModal: displayModal,
    addOtherDbTables: addOtherDbTables,
    new: newPage,
    save: save,
    getUrlPos: getUrlPos,
    save2: save2,
    submitSaveDialogAndClose: submitSaveDialogAndClose,
    save3: save3,
    editPages: editPages,
    deletePages: deletePages,
    saveAs: saveAs,
    promptToSaveCurrentPage: promptToSaveCurrentPage,
    exportPages: exportPages,
    loadPage: loadPage,
    grid: grid,
    angularDirect: angularDirect,
    saveValueInConfig: saveValueInConfig,
    startRelation: startRelation,
    clickField: clickTableField,
    newRelation: newRelation,
    startTableNew: startTableNew,
    startTabUpd: startTabUpd,
    smallTabAll: smallTabAll,
    smallTabInvert: smallTabInvert,
    relationLinesInvert: relationLinesInvert,
    smallTabRefresh: smallTabRefresh,
    smallTab: smallTab,
    selectTab: selectTab,
    canvasClick: canvasClick,
    updRelation: updRelation,
    visibleTab: visibleTab,
    hideTabAll: hideTabAll,
    inArrayK: inArrayK,
    noHaveConstr: noHaveConstr,
    showLeftMenu: showLeftMenu,
    sideMenuRight: sideMenuRight,
    showText: showText,
    hideText: hideText,
    pinText: pinText,
    startDisplayField: startDisplayField,
    getColorByTarget: getColorByTarget,
    clickOption: clickOption,
    closeOption: closeOption,
    selectAll: selectAll,
    tableOnOver: tableOnOver,
    storeColumn: storeColumn,
    addObject: addObject,
    enablePageContentEvents: enablePageContentEvents,
    enableTableEvents: enableTableEvents,
};

declare global {
    interface Window {
        fromArray: any[];
    }
}

export { DesignerMove };
