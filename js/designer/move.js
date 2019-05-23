/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Designer
 */

var DesignerMove = {};

var _change = 0; // variable to track any change in designer layout.
var _staying = 0; //  variable to check if the user stayed after seeing the confirmation prompt.
var show_relation_lines = true;
var always_show_text = false;

AJAX.registerTeardown('designer/move.js', function () {
    $(document).off('fullscreenchange');
    $('#selflink').show();
});

AJAX.registerOnload('designer/move.js', function () {
    $('#page_content').css({ 'margin-left': '3px' });
    $(document).on('fullscreenchange', function () {
        if (! $.fn.fullScreen()) {
            $('#page_content').removeClass('content_fullscreen')
                .css({ 'width': 'auto', 'height': 'auto' });
            var $img = $('#toggleFullscreen').find('img');
            var $span = $img.siblings('span');
            $span.text($span.data('enter'));
            $img.attr('src', $img.data('enter'))
                .attr('title', $span.data('enter'));
        }
    });

    $('#selflink').hide();
});

DesignerMove.makeZero = function () {   // Function called if the user stays after seeing the confirmation prompt.
    _staying = 0;
};

DesignerMove.markSaved = function () {
    _change = 0;
    $('#saved_state').text('');
};

DesignerMove.markUnsaved = function () {
    _change = 1;
    $('#saved_state').text('*');
};

var dx;
var dy;
var dy2;
var cur_click = null;
// update in DesignerMove.main()
var sm_x = 2;
var sm_y = 2;
var sm_s           = 0;
var sm_add         = 10;
var s_left         = 0;
var s_right        = 0;
var ON_relation    = 0;
var ON_grid        = 0;
var ON_display_field = 0;
// relation_style: 0 - angular 1 - direct
var ON_angular_direct = 1;
var click_field    = 0;
var link_relation  = '';
var id_hint;
var canvas_width   = 0;
var canvas_height  = 0;
var osn_tab_width  = 0;
var osn_tab_height = 0;
var height_field   = 7;
var Glob_X;
var Glob_Y;
var timeoutID;
var layer_menu_cur_click = 0;
var step = 10;
var old_class;
var from_array = [];
var downer;
var menu_moved = false;
var grid_size = 10;

// ------------------------------------------------------------------------------

var isIE = document.all && !window.opera;

if (isIE) {
    window.onscroll = DesignerMove.generalScroll;
    document.onselectstart = function () {
        return false;
    };
}

DesignerMove.mouseDown = function (e) {
    Glob_X = dx = isIE ? e.clientX + document.body.scrollLeft : e.pageX;
    Glob_Y = dy = isIE ? e.clientY + document.body.scrollTop : e.pageY;

    if (e.target.tagName === 'SPAN') {
        cur_click = e.target.parentNode.parentNode.parentNode.parentNode;
    } else if (e.target.className === 'tab_zag_2') {
        cur_click = e.target.parentNode.parentNode.parentNode;
    } else if (e.target.className === 'icon') {
        layer_menu_cur_click = 1;
    } else if (e.target.className === 'M_butt') {
        return false;
    }

    if (cur_click !== null) {
        document.getElementById('canvas').style.display = 'none';
        cur_click.style.zIndex = 2;
    }
};

DesignerMove.mouseMove = function (e) {
    if (e.preventDefault) {
        e.preventDefault();
    }

    var new_dx = isIE ? e.clientX + document.body.scrollLeft : e.pageX;
    var new_dy = isIE ? e.clientY + document.body.scrollTop : e.pageY;

    var delta_x = Glob_X - new_dx;
    var delta_y = Glob_Y - new_dy;

    Glob_X = new_dx;
    Glob_Y = new_dy;

    if (cur_click !== null) {
        DesignerMove.markUnsaved();

        var $cur_click = $(cur_click);

        var cur_x = parseFloat($cur_click.attr('data-left') || $cur_click.css('left'));
        var cur_y = parseFloat($cur_click.attr('data-top') || $cur_click.css('top'));

        var new_x = cur_x - delta_x;
        var new_y = cur_y - delta_y;

        dx = new_dx;
        dy = new_dy;

        $cur_click.attr('data-left', new_x);
        $cur_click.attr('data-top', new_y);

        if (ON_grid) {
            new_x = parseInt(new_x / grid_size) * grid_size;
            new_y = parseInt(new_y / grid_size) * grid_size;
        }

        if (new_x < 0) {
            new_x = 0;
        } else if (new_y < 0) {
            new_y = 0;
        }
        $cur_click.css('left', new_x + 'px');
        $cur_click.css('top', new_y + 'px');
    } else if (layer_menu_cur_click) {
        dx = new_dx;
        dy = new_dy;
        if (menu_moved) {
            delta_x = -delta_x;
        }
        var $layer_menu = $('#layer_menu');
        var new_width = $layer_menu.width() + delta_x;
        if (new_width < 150) {
            new_width = 150;
        } else {
            dx = e.pageX;
        }
        $layer_menu.width(new_width);
    }

    if (ON_relation || ON_display_field) {
        document.getElementById('designer_hint').style.left = (Glob_X + 20) + 'px';
        document.getElementById('designer_hint').style.top  = (Glob_Y + 20) + 'px';
    }
};

DesignerMove.mouseUp = function (e) {
    if (cur_click !== null) {
        document.getElementById('canvas').style.display = 'inline-block';
        DesignerMove.reload();
        cur_click.style.zIndex = 1;
        cur_click = null;
    }
    layer_menu_cur_click = 0;
    // window.releaseEvents(Event.MOUSEMOVE);
};

// ------------------------------------------------------------------------------

DesignerMove.canvasPos = function () {
    canvas_width  = document.getElementById('canvas').width  = osn_tab_width  - 3;
    canvas_height = document.getElementById('canvas').height = osn_tab_height - 3;

    if (isIE) {
        document.getElementById('canvas').style.width  = ((osn_tab_width  - 3) ? (osn_tab_width  - 3) : 0) + 'px';
        document.getElementById('canvas').style.height = ((osn_tab_height - 3) ? (osn_tab_height - 3) : 0) + 'px';
    }
};

DesignerMove.osnTabPos = function () {
    osn_tab_width  = parseInt(document.getElementById('osn_tab').style.width, 10);
    osn_tab_height = parseInt(document.getElementById('osn_tab').style.height, 10);
};

DesignerMove.setDefaultValuesFromSavedState = function () {
    if ($('#angular_direct_button').attr('class') === 'M_butt') {
        ON_angular_direct = 0;
    } else {
        ON_angular_direct = 1;
    }
    DesignerMove.angularDirect();

    if ($('#grid_button').attr('class') === 'M_butt') {
        ON_grid = 1;
    } else {
        ON_grid = 0;
    }
    DesignerMove.grid();

    var $relLineInvert = $('#relLineInvert');
    if ($relLineInvert.attr('class') === 'M_butt') {
        show_relation_lines = false;
        $relLineInvert.attr('class', 'M_butt');
    } else {
        show_relation_lines = true;
        $relLineInvert.attr('class', 'M_butt_Selected_down');
    }
    DesignerMove.relationLinesInvert();

    if ($('#pin_Text').attr('class') === 'M_butt_Selected_down') {
        always_show_text = true;
        DesignerMove.showText();
    } else {
        always_show_text = false;
    }

    var $key_SB_all = $('#key_SB_all');
    if ($key_SB_all.attr('class') === 'M_butt_Selected_down') {
        $key_SB_all.trigger('click');
        $key_SB_all.toggleClass('M_butt_Selected_down');
        $key_SB_all.toggleClass('M_butt');
    }

    var $key_Left_Right = $('#key_Left_Right');
    if ($key_Left_Right.attr('class') === 'M_butt_Selected_down') {
        $key_Left_Right.trigger('click');
    }
};

DesignerMove.main = function () {
    // ---CROSS

    document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
    // sm_x += document.getElementById('osn_tab').offsetLeft;
    // sm_y += document.getElementById('osn_tab').offsetTop;
    DesignerMove.osnTabPos();
    DesignerMove.canvasPos();
    DesignerMove.smallTabRefresh();
    DesignerMove.reload();
    DesignerMove.setDefaultValuesFromSavedState();
    id_hint = document.getElementById('designer_hint');
    if (isIE) {
        DesignerMove.generalScroll();
    }
};

DesignerMove.resizeOsnTab = function () {
    var max_X = 0;
    var max_Y = 0;
    for (var key in j_tabs) {
        var k_x = parseInt(document.getElementById(key).style.left, 10) + document.getElementById(key).offsetWidth;
        var k_y = parseInt(document.getElementById(key).style.top, 10) + document.getElementById(key).offsetHeight;
        max_X = max_X < k_x ? k_x : max_X;
        max_Y = max_Y < k_y ? k_y : max_Y;
    }

    osn_tab_width  = max_X + 50;
    osn_tab_height = max_Y + 50;
    DesignerMove.canvasPos();
    document.getElementById('osn_tab').style.width = osn_tab_width + 'px';
    document.getElementById('osn_tab').style.height = osn_tab_height + 'px';
};

/**
 * refreshes display, must be called after state changes
 */
DesignerMove.reload = function () {
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
    for (K in contr) {
        for (key in contr[K]) {
            // contr name
            for (key2 in contr[K][key]) {
                // table name
                for (key3 in contr[K][key][key2]) {
                    // field name
                    if (!document.getElementById('check_vis_' + key2).checked ||
                        !document.getElementById('check_vis_' + contr[K][key][key2][key3][0]).checked) {
                        // if hide
                        continue;
                    }
                    var x1_left  = document.getElementById(key2).offsetLeft + 1;
                    var x1_right = x1_left + document.getElementById(key2).offsetWidth;
                    var x2_left  = document.getElementById(contr[K][key][key2][key3][0]).offsetLeft;
                    var x2_right = x2_left + document.getElementById(contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1_left - x2_left);
                    a[1] = Math.abs(x1_left - x2_right);
                    a[2] = Math.abs(x1_right - x2_left);
                    a[3] = Math.abs(x1_right - x2_right);
                    n = s_left = s_right = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }
                    if (n === 1) {
                        x1 = x1_left - sm_s;
                        x2 = x2_right + sm_s;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n === 2) {
                        x1 = x1_right + sm_s;
                        x2 = x2_left - sm_s;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n === 3) {
                        x1 = x1_right + sm_s;
                        x2 = x2_right + sm_s;
                        s_right = 1;
                    }
                    if (n === 0) {
                        x1 = x1_left - sm_s;
                        x2 = x2_left - sm_s;
                        s_left = 1;
                    }

                    var row_offset_top = 0;
                    var tab_hide_button = document.getElementById('id_hide_tbody_' + key2);

                    if (tab_hide_button.innerHTML === 'v') {
                        var fromColumn = document.getElementById(key2 + '.' + key3);
                        if (fromColumn) {
                            row_offset_top = fromColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y1 = document.getElementById(key2).offsetTop +
                        row_offset_top +
                        height_field;


                    row_offset_top = 0;
                    tab_hide_button = document.getElementById('id_hide_tbody_' + contr[K][key][key2][key3][0]);
                    if (tab_hide_button.innerHTML === 'v') {
                        var toColumn = document.getElementById(contr[K][key][key2][key3][0] +
                            '.' + contr[K][key][key2][key3][1]);
                        if (toColumn) {
                            row_offset_top = toColumn.offsetTop;
                        } else {
                            continue;
                        }
                    }

                    var y2 =
                        document.getElementById(contr[K][key][key2][key3][0]).offsetTop +
                        row_offset_top +
                        height_field;

                    var osn_tab = document.getElementById('osn_tab');

                    DesignerMove.line0(
                        x1 + osn_tab.offsetLeft,
                        y1 - osn_tab.offsetTop,
                        x2 + osn_tab.offsetLeft,
                        y2 - osn_tab.offsetTop,
                        DesignerMove.getColorByTarget(contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1])
                    );
                }
            }
        }
    }
};

/**
 * draws a line from x1:y1 to x2:y2 with color
 */
DesignerMove.line = function (x1, y1, x2, y2, color_line) {
    var canvas = document.getElementById('canvas');
    var ctx    = canvas.getContext('2d');
    ctx.strokeStyle = color_line;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.stroke();
};

/**
 * draws a relation/constraint line, whether angular or not
 */
DesignerMove.line0 = function (x1, y1, x2, y2, color_line) {
    if (! show_relation_lines) {
        return;
    }
    DesignerMove.circle(x1, y1, 3, 3, color_line);
    DesignerMove.rect(x2 - 1, y2 - 2, 4, 4, color_line);

    if (ON_angular_direct) {
        DesignerMove.line2(x1, y1, x2, y2, color_line);
    } else {
        DesignerMove.line3(x1, y1, x2, y2, color_line);
    }
};

/**
 * draws a angular relation/constraint line
 */
DesignerMove.line2 = function (x1, y1, x2, y2, color_line) {
    var x1_ = x1;
    var x2_ = x2;

    if (s_right) {
        x1_ += sm_add;
        x2_ += sm_add;
    } else if (s_left) {
        x1_ -= sm_add;
        x2_ -= sm_add;
    } else if (x1 < x2) {
        x1_ += sm_add;
        x2_ -= sm_add;
    } else {
        x1_ -= sm_add;
        x2_ += sm_add;
    }

    DesignerMove.line(x1, y1, x1_, y1, color_line);
    DesignerMove.line(x2, y2, x2_, y2, color_line);
    DesignerMove.line(x1_, y1, x2_, y2, color_line);
};

/**
 * draws a relation/constraint line
 */
DesignerMove.line3 = function (x1, y1, x2, y2, color_line) {
    var x1_ = x1;
    var x2_ = x2;

    if (s_right) {
        if (x1 < x2) {
            x1_ += x2 - x1 + sm_add;
            x2_ += sm_add;
        } else {
            x2_ += x1 - x2 + sm_add;
            x1_ += sm_add;
        }

        DesignerMove.line(x1, y1, x1_, y1, color_line);
        DesignerMove.line(x2, y2, x2_, y2, color_line);
        DesignerMove.line(x1_, y1, x2_, y2, color_line);
        return;
    }
    if (s_left) {
        if (x1 < x2) {
            x2_ -= x2 - x1 + sm_add;
            x1_ -= sm_add;
        } else {
            x1_ -= x1 - x2 + sm_add;
            x2_ -= sm_add;
        }

        DesignerMove.line(x1, y1, x1_, y1, color_line);
        DesignerMove.line(x2, y2, x2_, y2, color_line);
        DesignerMove.line(x1_, y1, x2_, y2, color_line);
        return;
    }

    var x_s = (x1 + x2) / 2;
    DesignerMove.line(x1, y1, x_s, y1, color_line);
    DesignerMove.line(x_s, y2, x2, y2, color_line);
    DesignerMove.line(x_s, y1, x_s, y2, color_line);
};

DesignerMove.circle = function (x, y, r, w, color) {
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineWidth = w;
    ctx.strokeStyle = color;
    ctx.arc(x, y, r, 0, 2 * Math.PI, true);
    ctx.stroke();
};

DesignerMove.clear = function () {
    var canvas = document.getElementById('canvas');
    var ctx    = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas_width, canvas_height);
};

DesignerMove.rect = function (x1, y1, w, h, color) {
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.fillStyle = color;
    ctx.fillRect(x1, y1, w, h);
};

// --------------------------- FULLSCREEN -------------------------------------
DesignerMove.toggleFullscreen = function () {
    var value_sent = '';
    var $img = $('#toggleFullscreen').find('img');
    var $span = $img.siblings('span');
    var $content = $('#page_content');
    if (! $content.fullScreen()) {
        $img.attr('src', $img.data('exit'))
            .attr('title', $span.data('exit'));
        $span.text($span.data('exit'));
        $content
            .addClass('content_fullscreen')
            .css({ 'width': screen.width - 5, 'height': screen.height - 5 });
        value_sent = 'on';
        $content.fullScreen(true);
    } else {
        $content.fullScreen(false);
        value_sent = 'off';
    }
    DesignerMove.saveValueInConfig('full_screen', value_sent);
};

DesignerMove.addOtherDbTables = function () {
    var button_options = {};
    button_options[Messages.strGo] = function () {
        var db = $('#add_table_from').val();
        var table = $('#add_table').val();
        $.post('db_designer.php', {
            'ajax_request' : true,
            'dialog' : 'add_table',
            'db' : db,
            'table' : table,
            'server': CommonParams.get('server')
        }, function (data) {
            $new_table_dom = $(data.message);
            $new_table_dom.find('a').first().remove();
            $('#container-form').append($new_table_dom);
            $('.designer_tab').on('click','.tab_field_2,.tab_field_3,.tab_field', function () {
                var params = ($(this).attr('click_field_param')).split(',');
                DesignerMove.clickField(params[3], params[0], params[1], params[2]);
            });
            $('.designer_tab').on('click', '.select_all_store_col', function () {
                var params = ($(this).attr('store_column_param')).split(',');
                DesignerMove.storeColumn(params[0], params[1], params[2]);
            });
            $('.designer_tab').on('click', '.small_tab_pref_click_opt', function () {
                var params = ($(this).attr('Click_option_param')).split(',');
                DesignerMove.clickOption(params[0], params[1], params[2]);
            });
        });
        $(this).dialog('close');
    };
    button_options[Messages.strCancel] = function () {
        $(this).dialog('close');
    };

    var $select_db = $('<select id="add_table_from"></select>');
    $select_db.append('<option value="">None</option>');

    var $select_table = $('<select id="add_table"></select>');
    $select_table.append('<option value="">None</option>');

    $.post('sql.php', {
        'ajax_request' : true,
        'sql_query' : 'SHOW databases;',
        'server': CommonParams.get('server')
    }, function (data) {
        $(data.message).find('table.table_results.data.ajax').find('td.data').each(function () {
            var val = $(this)[0].innerHTML;
            $select_db.append('<option value="' + val + '">' + val + '</option>');
        });
    });

    var $form = $('<form action="" class="ajax"></form>')
        .append($select_db).append($select_table);
    $('<div id="page_add_tables_dialog"></div>')
        .append($form)
        .dialog({
            appendTo: '#page_content',
            title: Messages.strAddTables,
            width: 500,
            modal: true,
            buttons: button_options,
            close: function () {
                $(this).remove();
            }
        });

    $('#add_table_from').on('change', function () {
        if ($(this).val()) {
            var db_name = $(this).val();
            var sql_query = 'SHOW tables;';
            $.post('sql.php', {
                'ajax_request' : true,
                'sql_query': sql_query,
                'db' : db_name,
                'server': CommonParams.get('server')
            }, function (data) {
                $select_table.html('');
                $(data.message).find('table.table_results.data.ajax').find('td.data').each(function () {
                    var val = $(this)[0].innerHTML;
                    $select_table.append('<option value="' + val + '">' + val + '</option>');
                });
            });
        }
    });
};

// ------------------------------ NEW ------------------------------------------
DesignerMove.new = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        DesignerMove.loadPage(-1);
    });
};

// ------------------------------ SAVE ------------------------------------------
// (del?) no for pdf
DesignerMove.save = function (url) {
    for (var key in j_tabs) {
        document.getElementById('t_x_' + key + '_').value = parseInt(document.getElementById(key).style.left, 10);
        document.getElementById('t_y_' + key + '_').value = parseInt(document.getElementById(key).style.top, 10);
        document.getElementById('t_v_' + key + '_').value = document.getElementById('id_tbody_' + key).style.display === 'none' ? 0 : 1;
        document.getElementById('t_h_' + key + '_').value = document.getElementById('check_vis_' + key).checked ? 1 : 0;
    }
    document.form1.action = url;
    $(document.form1).submit();
};

DesignerMove.getUrlPos = function (forceString) {
    if (designer_tables_enabled || forceString) {
        var poststr = '';
        var argsep = CommonParams.get('arg_separator');
        for (var key in j_tabs) {
            poststr += argsep + 't_x[' + key + ']=' + parseInt(document.getElementById(key).style.left, 10);
            poststr += argsep + 't_y[' + key + ']=' + parseInt(document.getElementById(key).style.top, 10);
            poststr += argsep + 't_v[' + key + ']=' + (document.getElementById('id_tbody_' + key).style.display === 'none' ? 0 : 1);
            poststr += argsep + 't_h[' + key + ']=' + (document.getElementById('check_vis_' + key).checked ? 1 : 0);
        }
        return poststr;
    } else {
        var coords = [];
        for (var key in j_tabs) {
            if (document.getElementById('check_vis_' + key).checked) {
                var x = parseInt(document.getElementById(key).style.left, 10);
                var y = parseInt(document.getElementById(key).style.top, 10);
                var tbCoords = new TableCoordinate(db, key.split('.')[1], -1, x, y);
                coords.push(tbCoords);
            }
        }
        return coords;
    }
};

DesignerMove.save2 = function (callback) {
    if (designer_tables_enabled) {
        var argsep = CommonParams.get('arg_separator');
        var poststr = argsep + 'operation=savePage' + argsep + 'save_page=same' + argsep + 'ajax_request=true';
        poststr += argsep + 'server=' + server + argsep + 'db=' + db + argsep + 'selected_page=' + selected_page;
        poststr += DesignerMove.getUrlPos();

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        $.post('db_designer.php', poststr, function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);
                Functions.ajaxShowMessage(Messages.strModificationSaved);
                DesignerMove.markSaved();
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        var name = $('#page_name').html().trim();
        Save_to_selected_page(db, selected_page, name, DesignerMove.getUrlPos(), function (page) {
            DesignerMove.markSaved();
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

DesignerMove.submitSaveDialogAndClose = function (callback) {
    var $form = $('#save_page');
    var name = $form.find('input[name="selected_value"]').val().trim();
    if (name === '') {
        Functions.ajaxShowMessage(Messages.strEnterValidPageName, false);
        return;
    }
    $('#page_save_dialog').dialog('close');

    if (designer_tables_enabled) {
        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);
                DesignerMove.markSaved();
                if (data.id) {
                    selected_page = data.id;
                }
                $('#page_name').text(name);
                if (typeof callback !== 'undefined') {
                    callback();
                }
            }
        });
    } else {
        Save_to_new_page(db, name, DesignerMove.getUrlPos(), function (page) {
            DesignerMove.markSaved();
            if (page.pg_nr) {
                selected_page = page.pg_nr;
            }
            $('#page_name').text(page.page_descr);
            if (typeof callback !== 'undefined') {
                callback();
            }
        });
    }
};

DesignerMove.save3 = function (callback) {
    if (parseInt(selected_page) !== -1) {
        DesignerMove.save2(callback);
    } else {
        var button_options = {};
        button_options[Messages.strGo] = function () {
            var $form = $('#save_page');
            $form.submit();
        };
        button_options[Messages.strCancel] = function () {
            $(this).dialog('close');
        };

        var $form = $('<form action="db_designer.php" method="post" name="save_page" id="save_page" class="ajax"></form>')
            .append('<input type="hidden" name="server" value="' + server + '">')
            .append('<input type="hidden" name="db" value="' + db + '">')
            .append('<input type="hidden" name="operation" value="savePage">')
            .append('<input type="hidden" name="save_page" value="new">')
            .append('<label for="selected_value">' + Messages.strPageName +
                '</label>:<input type="text" name="selected_value">');
        $form.on('submit', function (e) {
            e.preventDefault();
            DesignerMove.submitSaveDialogAndClose(callback);
        });
        $('<div id="page_save_dialog"></div>')
            .append($form)
            .dialog({
                appendTo: '#page_content',
                title: Messages.strSavePage,
                width: 300,
                modal: true,
                buttons: button_options,
                close: function () {
                    $(this).remove();
                }
            });
    }
};

// ------------------------------ EDIT PAGES ------------------------------------------
DesignerMove.editPages = function () {
    DesignerMove.promptToSaveCurrentPage(function () {
        var button_options = {};
        button_options[Messages.strGo] = function () {
            var $form = $('#edit_delete_pages');
            var selected = $form.find('select[name="selected_page"]').val();
            if (selected === '0') {
                Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
                return;
            }
            $(this).dialog('close');
            DesignerMove.loadPage(selected);
        };
        button_options[Messages.strCancel] = function () {
            $(this).dialog('close');
        };

        var $msgbox = Functions.ajaxShowMessage();
        $.post('db_designer.php', {
            'ajax_request': true,
            'server': server,
            'db': db,
            'dialog': 'edit'
        }, function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            } else {
                Functions.ajaxRemoveMessage($msgbox);

                if (! designer_tables_enabled) {
                    Create_page_list(db, function (options) {
                        $('#selected_page').append(options);
                    });
                }
                $('<div id="page_edit_dialog"></div>')
                    .append(data.message)
                    .dialog({
                        appendTo: '#page_content',
                        title: Messages.strOpenPage,
                        width: 350,
                        modal: true,
                        buttons: button_options,
                        close: function () {
                            $(this).remove();
                        }
                    });
            }
        }); // end $.get()
    });
};

// -----------------------------  DELETE PAGES ---------------------------------------
DesignerMove.deletePages = function () {
    var button_options = {};
    button_options[Messages.strGo] = function () {
        var $form = $('#edit_delete_pages');
        var selected = $form.find('select[name="selected_page"]').val();
        if (selected === '0') {
            Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
            return;
        }

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        var deleting_current_page = selected === selected_page;
        Functions.prepareForAjaxRequest($form);

        if (designer_tables_enabled) {
            $.post($form.attr('action'), $form.serialize(), function (data) {
                if (data.success === false) {
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxRemoveMessage($msgbox);
                    if (deleting_current_page) {
                        DesignerMove.loadPage(null);
                    } else {
                        Functions.ajaxShowMessage(Messages.strSuccessfulPageDelete);
                    }
                }
            }); // end $.post()
        } else {
            Delete_page(selected, function (success) {
                if (! success) {
                    Functions.ajaxShowMessage('Error', false);
                } else {
                    Functions.ajaxRemoveMessage($msgbox);
                    if (deleting_current_page) {
                        DesignerMove.loadPage(null);
                    } else {
                        Functions.ajaxShowMessage(Messages.strSuccessfulPageDelete);
                    }
                }
            });
        }

        $(this).dialog('close');
    };
    button_options[Messages.strCancel] = function () {
        $(this).dialog('close');
    };

    var $msgbox = Functions.ajaxShowMessage();
    $.post('db_designer.php', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'delete'
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            if (! designer_tables_enabled) {
                Create_page_list(db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            $('<div id="page_delete_dialog"></div>')
                .append(data.message)
                .dialog({
                    appendTo: '#page_content',
                    title: Messages.strDeletePage,
                    width: 350,
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
        }
    }); // end $.get()
};

// ------------------------------ SAVE AS PAGES ---------------------------------------
DesignerMove.saveAs = function () {
    var button_options = {};
    button_options[Messages.strGo] = function () {
        var $form           = $('#save_as_pages');
        var selected_value  = $form.find('input[name="selected_value"]').val().trim();
        var $selected_page  = $form.find('select[name="selected_page"]');
        var choice          = $form.find('input[name="save_page"]:checked').val();
        var name            = '';

        if (choice === 'same') {
            if ($selected_page.val() === '0') {
                Functions.ajaxShowMessage(Messages.strSelectPage, 2000);
                return;
            }
            name = $selected_page.find('option:selected').text();
        } else if (choice === 'new') {
            if (selected_value === '') {
                Functions.ajaxShowMessage(Messages.strEnterValidPageName, 2000);
                return;
            }
            name = selected_value;
        }

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        if (designer_tables_enabled) {
            Functions.prepareForAjaxRequest($form);
            $.post($form.attr('action'), $form.serialize() + DesignerMove.getUrlPos(), function (data) {
                if (data.success === false) {
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxRemoveMessage($msgbox);
                    DesignerMove.markSaved();
                    if (data.id) {
                        selected_page = data.id;
                    }
                    $('#page_name').text(name);
                }
            }); // end $.post()
        } else {
            if (choice === 'same') {
                var selected_page_id = $selected_page.find('option:selected').val();
                Save_to_selected_page(db, selected_page_id, name, DesignerMove.getUrlPos(), function (page) {
                    Functions.ajaxRemoveMessage($msgbox);
                    DesignerMove.markSaved();
                    if (page.pg_nr) {
                        selected_page = page.pg_nr;
                    }
                    $('#page_name').text(page.page_descr);
                });
            } else if (choice === 'new') {
                Save_to_new_page(db, name, DesignerMove.getUrlPos(), function (page) {
                    Functions.ajaxRemoveMessage($msgbox);
                    DesignerMove.markSaved();
                    if (page.pg_nr) {
                        selected_page = page.pg_nr;
                    }
                    $('#page_name').text(page.page_descr);
                });
            }
        }

        $(this).dialog('close');
    };
    button_options[Messages.strCancel] = function () {
        $(this).dialog('close');
    };

    var $msgbox = Functions.ajaxShowMessage();
    $.post('db_designer.php', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'save_as'
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            if (! designer_tables_enabled) {
                Create_page_list(db, function (options) {
                    $('#selected_page').append(options);
                });
            }

            $('<div id="page_save_as_dialog"></div>')
                .append(data.message)
                .dialog({
                    appendTo: '#page_content',
                    title: Messages.strSavePageAs,
                    width: 450,
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
            // select current page by default
            if (selected_page !== '-1') {
                $('select[name="selected_page"]').val(selected_page);
            }
        }
    }); // end $.get()
};

DesignerMove.promptToSaveCurrentPage = function (callback) {
    if (_change === 1 || selected_page === '-1') {
        var button_options = {};
        button_options[Messages.strYes] = function () {
            $(this).dialog('close');
            DesignerMove.save3(callback);
        };
        button_options[Messages.strNo] = function () {
            $(this).dialog('close');
            callback();
        };
        button_options[Messages.strCancel] = function () {
            $(this).dialog('close');
        };
        $('<div id="prompt_save_dialog"></div>')
            .append('<div>' + Messages.strLeavingPage + '</div>')
            .dialog({
                appendTo: '#page_content',
                title: Messages.strSavePage,
                width: 300,
                modal: true,
                buttons: button_options,
                close: function () {
                    $(this).remove();
                }
            });
    } else {
        callback();
    }
};

// ------------------------------ EXPORT PAGES ---------------------------------------
DesignerMove.exportPages = function () {
    var button_options = {};
    button_options[Messages.strGo] = function () {
        $('#id_export_pages').submit();
        $(this).dialog('close');
    };
    button_options[Messages.strCancel] = function () {
        $(this).dialog('close');
    };
    var $msgbox = Functions.ajaxShowMessage();
    var argsep = CommonParams.get('arg_separator');

    $.post('db_designer.php', {
        'ajax_request': true,
        'server': server,
        'db': db,
        'dialog': 'export',
        'selected_page': selected_page
    }, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);

            var $form = $(data.message);
            if (!designer_tables_enabled) {
                $form.append('<input type="hidden" name="offline_export" value="true">');
            }
            $.each(DesignerMove.getUrlPos(true).substring(1).split(argsep), function () {
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

            $('<div id="page_export_dialog"></div>')
                .append($form)
                .dialog({
                    appendTo: '#page_content',
                    title: Messages.strExportRelationalSchema,
                    width: 550,
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
        }
    }); // end $.get()
};

DesignerMove.loadPage = function (page) {
    if (designer_tables_enabled) {
        var param_page = '';
        var argsep = CommonParams.get('arg_separator');
        if (page !== null) {
            param_page = argsep + 'page=' + page;
        }
        $('<a href="db_designer.php?server=' + server + argsep + 'db=' + encodeURI(db) + param_page + '"></a>')
            .appendTo($('#page_content'))
            .trigger('click');
    } else {
        if (page === null) {
            Show_tables_in_landing_page(db);
        } else if (page > -1) {
            Load_HTML_for_page(page);
        } else if (page === -1) {
            Show_new_page_tables(true);
        }
    }
    DesignerMove.markSaved();
};

DesignerMove.grid = function () {
    var value_sent = '';
    if (!ON_grid) {
        ON_grid = 1;
        value_sent = 'on';
        document.getElementById('grid_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('grid_button').className = 'M_butt';
        ON_grid = 0;
        value_sent = 'off';
    }
    DesignerMove.saveValueInConfig('snap_to_grid', value_sent);
};

DesignerMove.angularDirect = function () {
    var value_sent = '';
    if (ON_angular_direct) {
        ON_angular_direct = 0;
        value_sent = 'angular';
        document.getElementById('angular_direct_button').className = 'M_butt_Selected_down';
    } else {
        ON_angular_direct = 1;
        value_sent = 'direct';
        document.getElementById('angular_direct_button').className = 'M_butt';
    }
    DesignerMove.saveValueInConfig('angular_direct', value_sent);
    DesignerMove.reload();
};

DesignerMove.saveValueInConfig = function (index_sent, value_sent) {
    $.post('db_designer.php',
        { operation: 'save_setting_value', index: index_sent, ajax_request: true, server: server, value: value_sent },
        function (data) {
            if (data.success === false) {
                Functions.ajaxShowMessage(data.error, false);
            }
        });
};

// ++++++++++++++++++++++++++++++ RELATION ++++++++++++++++++++++++++++++++++++++
DesignerMove.startRelation = function () {
    if (ON_display_field) {
        return;
    }

    if (!ON_relation) {
        document.getElementById('foreign_relation').style.display = '';
        ON_relation = 1;
        document.getElementById('designer_hint').innerHTML = Messages.strSelectReferencedKey;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('rel_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('rel_button').className = 'M_butt';
        click_field = 0;
        ON_relation = 0;
    }
};

// table field
DesignerMove.clickField = function (db, T, f, PK) {
    PK = parseInt(PK);
    var argsep = CommonParams.get('arg_separator');
    if (ON_relation) {
        if (!click_field) {
            // .style.display=='none'        .style.display = 'none'
            if (!PK) {
                alert(Messages.strPleaseSelectPrimaryOrUniqueKey);
                return;// 0;
            }// PK
            if (j_tabs[db + '.' + T] !== 1) {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            click_field = 1;
            link_relation = 'DB1=' + db + argsep + 'T1=' + T + argsep + 'F1=' + f;
            document.getElementById('designer_hint').innerHTML = Messages.strSelectForeignKey;
        } else {
            DesignerMove.startRelation(); // hidden hint...
            if (j_tabs[db + '.' + T] !== 1 || !PK) {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            var left = Glob_X - (document.getElementById('layer_new_relation').offsetWidth >> 1);
            document.getElementById('layer_new_relation').style.left = left + 'px';
            var top = Glob_Y - document.getElementById('layer_new_relation').offsetHeight;
            document.getElementById('layer_new_relation').style.top  = top + 'px';
            document.getElementById('layer_new_relation').style.display = 'block';
            link_relation += argsep + 'DB2=' + db + argsep + 'T2=' + T + argsep + 'F2=' + f;
        }
    }

    if (ON_display_field) {
        // if is display field
        if (display_field[T] === f) {
            old_class = 'tab_field';
            delete display_field[T];
        } else {
            old_class = 'tab_field_3';
            if (display_field[T]) {
                document.getElementById('id_tr_' + T + '.' + display_field[T]).className = 'tab_field';
                delete display_field[T];
            }
            display_field[T] = f;
        }
        ON_display_field = 0;
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';

        var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
        $.post('db_designer.php',
            { operation: 'setDisplayField', ajax_request: true, server: server, db: db, table: T, field: f },
            function (data) {
                if (data.success === false) {
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxRemoveMessage($msgbox);
                    Functions.ajaxShowMessage(Messages.strModificationSaved);
                }
            });
    }
};

DesignerMove.newRelation = function () {
    document.getElementById('layer_new_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    link_relation += argsep + 'server=' + server + argsep + 'db=' + db + argsep + 'db2=p';
    link_relation += argsep + 'on_delete=' + document.getElementById('on_delete').value + argsep + 'on_update=' + document.getElementById('on_update').value;
    link_relation += argsep + 'operation=addNewRelation' + argsep + 'ajax_request=true';

    var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
    $.post('db_designer.php', link_relation, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);
            DesignerMove.loadPage(selected_page);
        }
    }); // end $.post()
};

// -------------------------- create tables -------------------------------------
DesignerMove.startTableNew = function () {
    CommonParams.set('table', '');
    CommonActions.refreshMain('tbl_create.php');
};

DesignerMove.startTabUpd = function (table) {
    CommonParams.set('table', table);
    CommonActions.refreshMain('tbl_structure.php');
};

// --------------------------- hide tables --------------------------------------
// max/min all tables
DesignerMove.smallTabAll = function (id_this) {
    var icon = id_this.children[0];
    var key;
    var value_sent = '';

    if (icon.alt === 'v') {
        for (key in j_tabs) {
            if (document.getElementById('id_hide_tbody_' + key).innerHTML === 'v') {
                DesignerMove.smallTab(key, 0);
            }
        }
        icon.alt = '>';
        icon.src = icon.dataset.right;
        value_sent = 'v';
    } else {
        for (key in j_tabs) {
            if (document.getElementById('id_hide_tbody_' + key).innerHTML !== 'v') {
                DesignerMove.smallTab(key, 0);
            }
        }
        icon.alt = 'v';
        icon.src = icon.dataset.down;
        value_sent = '>';
    }
    DesignerMove.saveValueInConfig('small_big_all', value_sent);
    $('#key_SB_all').toggleClass('M_butt_Selected_down');
    $('#key_SB_all').toggleClass('M_butt');
    DesignerMove.reload();
};

// invert max/min all tables
DesignerMove.smallTabInvert = function () {
    for (var key in j_tabs) {
        DesignerMove.smallTab(key, 0);
    }
    DesignerMove.reload();
};

DesignerMove.relationLinesInvert = function () {
    show_relation_lines = ! show_relation_lines;
    DesignerMove.saveValueInConfig('relation_lines', show_relation_lines);
    $('#relLineInvert').toggleClass('M_butt_Selected_down');
    $('#relLineInvert').toggleClass('M_butt');
    DesignerMove.reload();
};

DesignerMove.smallTabRefresh = function () {
    for (var key in j_tabs) {
        if (document.getElementById('id_hide_tbody_' + key).innerHTML !== 'v') {
            DesignerMove.smallTab(key, 0);
        }
    }
};

DesignerMove.smallTab = function (t, re_load) {
    var id      = document.getElementById('id_tbody_' + t);
    var id_this = document.getElementById('id_hide_tbody_' + t);
    var id_t    = document.getElementById(t);
    if (id_this.innerHTML === 'v') {
        // ---CROSS
        id.style.display = 'none';
        id_this.innerHTML = '>';
    } else {
        id.style.display = '';
        id_this.innerHTML = 'v';
    }
    if (re_load) {
        DesignerMove.reload();
    }
};

DesignerMove.selectTab = function (t) {
    var id_zag = document.getElementById('id_zag_' + t);
    if (id_zag.className !== 'tab_zag_3') {
        document.getElementById('id_zag_' + t).className = 'tab_zag_2';
    } else {
        document.getElementById('id_zag_' + t).className = 'tab_zag';
    }
    // ----------
    var id_t = document.getElementById(t);
    window.scrollTo(parseInt(id_t.style.left, 10) - 300, parseInt(id_t.style.top, 10) - 300);
    setTimeout(
        function () {
            document.getElementById('id_zag_' + t).className = 'tab_zag';
        },
        800
    );
};

DesignerMove.canvasClick = function (id, event) {
    var n = 0;
    var relation_name = 0;
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
    var Local_X = isIE ? event.clientX + document.body.scrollLeft : event.pageX;
    var Local_Y = isIE ? event.clientY + document.body.scrollTop : event.pageY;
    Local_X -= $('#osn_tab').offset().left;
    Local_Y -= $('#osn_tab').offset().top;
    DesignerMove.clear();
    for (K in contr) {
        for (key in contr[K]) {
            for (key2 in contr[K][key]) {
                for (key3 in contr[K][key][key2]) {
                    if (! document.getElementById('check_vis_' + key2).checked ||
                        ! document.getElementById('check_vis_' + contr[K][key][key2][key3][0]).checked) {
                        continue; // if hide
                    }
                    var x1_left  = document.getElementById(key2).offsetLeft + 1;// document.getElementById(key2+"."+key3).offsetLeft;
                    var x1_right = x1_left + document.getElementById(key2).offsetWidth;
                    var x2_left  = document.getElementById(contr[K][key][key2][key3][0]).offsetLeft;// +document.getElementById(contr[K][key2][key3][0]+"."+contr[K][key2][key3][1]).offsetLeft
                    var x2_right = x2_left + document.getElementById(contr[K][key][key2][key3][0]).offsetWidth;
                    a[0] = Math.abs(x1_left - x2_left);
                    a[1] = Math.abs(x1_left - x2_right);
                    a[2] = Math.abs(x1_right - x2_left);
                    a[3] = Math.abs(x1_right - x2_right);
                    n = s_left = s_right = 0;
                    for (var i = 1; i < 4; i++) {
                        if (a[n] > a[i]) {
                            n = i;
                        }
                    }
                    if (n === 1) {
                        x1 = x1_left - sm_s;
                        x2 = x2_right + sm_s;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n === 2) {
                        x1 = x1_right + sm_s;
                        x2 = x2_left - sm_s;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n === 3) {
                        x1 = x1_right + sm_s;
                        x2 = x2_right + sm_s;
                        s_right = 1;
                    }
                    if (n === 0) {
                        x1 = x1_left - sm_s;
                        x2 = x2_left - sm_s;
                        s_left    = 1;
                    }

                    var y1 = document.getElementById(key2).offsetTop + document.getElementById(key2 + '.' + key3).offsetTop + height_field;
                    var y2 = document.getElementById(contr[K][key][key2][key3][0]).offsetTop +
                                     document.getElementById(contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1]).offsetTop + height_field;

                    if (!selected && Local_X > x1 - 10 && Local_X < x1 + 10 && Local_Y > y1 - 7 && Local_Y < y1 + 7) {
                        DesignerMove.line0(
                            x1 + osn_tab.offsetLeft,
                            y1 - osn_tab.offsetTop,
                            x2 + osn_tab.offsetLeft,
                            y2 - osn_tab.offsetTop,
                            'rgba(255,0,0,1)');

                        selected = 1;
                        relation_name = key;
                        Key0 = contr[K][key][key2][key3][0];
                        Key1 = contr[K][key][key2][key3][1];
                        Key2 = key2;
                        Key3 = key3;
                        Key = K;
                    } else {
                        DesignerMove.line0(
                            x1 + osn_tab.offsetLeft,
                            y1 - osn_tab.offsetTop,
                            x2 + osn_tab.offsetLeft,
                            y2 - osn_tab.offsetTop,
                            DesignerMove.getColorByTarget(contr[K][key][key2][key3][0] + '.' + contr[K][key][key2][key3][1])
                        );
                    }
                }
            }
        }
    }
    if (selected) {
        // select relations
        var left = Glob_X - (document.getElementById('layer_upd_relation').offsetWidth >> 1);
        document.getElementById('layer_upd_relation').style.left = left + 'px';
        var top = Glob_Y - document.getElementById('layer_upd_relation').offsetHeight - 10;
        document.getElementById('layer_upd_relation').style.top = top + 'px';
        document.getElementById('layer_upd_relation').style.display = 'block';
        var argsep = CommonParams.get('arg_separator');
        link_relation = 'T1=' + Key0 + argsep + 'F1=' + Key1 + argsep + 'T2=' + Key2 + argsep + 'F2=' + Key3 + argsep + 'K=' + Key;
    }
};

DesignerMove.updRelation = function () {
    document.getElementById('layer_upd_relation').style.display = 'none';
    var argsep = CommonParams.get('arg_separator');
    link_relation += argsep + 'server=' + server + argsep + 'db=' + db;
    link_relation += argsep + 'operation=removeRelation' + argsep + 'ajax_request=true';

    var $msgbox = Functions.ajaxShowMessage(Messages.strProcessingRequest);
    $.post('db_designer.php', link_relation, function (data) {
        if (data.success === false) {
            Functions.ajaxShowMessage(data.error, false);
        } else {
            Functions.ajaxRemoveMessage($msgbox);
            DesignerMove.loadPage(selected_page);
        }
    }); // end $.post()
};

DesignerMove.visibleTab = function (id, t_n) {
    if (id.checked) {
        document.getElementById(t_n).style.display = 'block';
    } else {
        document.getElementById(t_n).style.display = 'none';
    }
    DesignerMove.reload();
};

// max/min all tables
DesignerMove.hideTabAll = function (id_this) {
    if (id_this.alt === 'v') {
        id_this.alt = '>';
        id_this.src = id_this.dataset.right;
    } else {
        id_this.alt = 'v';
        id_this.src = id_this.dataset.down;
    }
    var E = document.form1;
    for (var i = 0; i < E.elements.length; i++) {
        if (E.elements[i].type === 'checkbox' && E.elements[i].id.substring(0, 10) === 'check_vis_') {
            if (id_this.alt === 'v') {
                E.elements[i].checked = true;
                document.getElementById(E.elements[i].value).style.display = '';
            } else {
                E.elements[i].checked = false;
                document.getElementById(E.elements[i].value).style.display = 'none';
            }
        }
    }
    DesignerMove.reload();
};

DesignerMove.inArrayK = function (x, m) {
    var b = 0;
    for (var u in m) {
        if (x === u) {
            b = 1;
            break;
        }
    }
    return b;
};

DesignerMove.noHaveConstr = function (id_this) {
    var a = [];
    var K;
    var key;
    var key2;
    var key3;
    for (K in contr) {
        for (key in contr[K]) {
            // contr name
            for (key2 in contr[K][key]) {
                // table name
                for (key3 in contr[K][key][key2]) {
                    // field name
                    a[key2] = a[contr[K][key][key2][key3][0]] = 1; // exist constr
                }
            }
        }
    }

    if (id_this.alt === 'v') {
        id_this.alt = '>';
        id_this.src = id_this.dataset.right;
    } else {
        id_this.alt = 'v';
        id_this.src = id_this.dataset.down;
    }
    var E = document.form1;
    for (var i = 0; i < E.elements.length; i++) {
        if (E.elements[i].type === 'checkbox' && E.elements[i].id.substring(0, 10) === 'check_vis_') {
            if (!DesignerMove.inArrayK(E.elements[i].value, a)) {
                if (id_this.alt === 'v') {
                    E.elements[i].checked = true;
                    document.getElementById(E.elements[i].value).style.display = '';
                } else {
                    E.elements[i].checked = false;
                    document.getElementById(E.elements[i].value).style.display = 'none';
                }
            }
        }
    }
};

DesignerMove.generalScroll = function () {
    // if (timeoutID)
    clearTimeout(timeoutID);
    timeoutID = setTimeout(
        function () {
            document.getElementById('top_menu').style.left = document.body.scrollLeft + 'px';
            document.getElementById('top_menu').style.top  = document.body.scrollTop + 'px';
        },
        200
    );
};

// max/min all tables
DesignerMove.showLeftMenu = function (id_this) {
    var icon = id_this.children[0];
    $('#key_Show_left_menu').toggleClass('M_butt_Selected_down');
    if (icon.alt === 'v') {
        document.getElementById('layer_menu').style.top = '0px';
        document.getElementById('layer_menu').style.display = 'block';
        icon.alt = '>';
        icon.src = icon.dataset.up;
        if (isIE) {
            DesignerMove.generalScroll();
        }
    } else {
        document.getElementById('layer_menu').style.top = -1000 + 'px'; // fast scroll
        document.getElementById('layer_menu').style.display = 'none';
        icon.alt = 'v';
        icon.src = icon.dataset.down;
    }
};

DesignerMove.sideMenuRight = function (id_this) {
    $('#side_menu').toggleClass('right');
    $('#layer_menu').toggleClass('left');
    var icon = $(id_this.childNodes[0]);
    var current = icon.attr('src');
    icon.attr('src', icon.attr('data-right')).attr('data-right', current);

    icon = $(document.getElementById('layer_menu_sizer').childNodes[0])
        .toggleClass('floatleft')
        .toggleClass('floatright')
        .children();
    current = icon.attr('src');
    icon.attr('src', icon.attr('data-right'));
    icon.attr('data-right', current);
    menu_moved = !menu_moved;
    DesignerMove.saveValueInConfig('side_menu', $('#side_menu').hasClass('right'));
    $('#key_Left_Right').toggleClass('M_butt_Selected_down');
    $('#key_Left_Right').toggleClass('M_butt');
};

DesignerMove.showText = function () {
    $('#side_menu').find('.hidable').show();
};

DesignerMove.hideText = function () {
    if (!always_show_text) {
        $('#side_menu').find('.hidable').hide();
    }
};

DesignerMove.pinText = function () {
    always_show_text = !always_show_text;
    $('#pin_Text').toggleClass('M_butt_Selected_down');
    $('#pin_Text').toggleClass('M_butt');
    DesignerMove.saveValueInConfig('pin_text', always_show_text);
};

DesignerMove.startDisplayField = function () {
    if (ON_relation) {
        return;
    }
    if (!ON_display_field) {
        ON_display_field = 1;
        document.getElementById('designer_hint').innerHTML = Messages.strChangeDisplay;
        document.getElementById('designer_hint').style.display = 'block';
        document.getElementById('display_field_button').className = 'M_butt_Selected_down';// '#FFEE99';gray #AAAAAA

        if (isIE) { // correct for IE
            document.getElementById('display_field_button').className = 'M_butt_Selected_down_IE';
        }
    } else {
        document.getElementById('designer_hint').innerHTML = '';
        document.getElementById('designer_hint').style.display = 'none';
        document.getElementById('display_field_button').className = 'M_butt';
        ON_display_field = 0;
    }
};

var TargetColors = [];

DesignerMove.getColorByTarget = function (target) {
    var color = '';  // "rgba(0,100,150,1)";

    for (var a in TargetColors) {
        if (TargetColors[a][0] === target) {
            color = TargetColors[a][1];
            break;
        }
    }

    if (color.length === 0) {
        var i = TargetColors.length + 1;
        var d = i % 6;
        var j = (i - d) / 6;
        j = j % 4;
        j++;
        var color_case = [
            [1, 0, 0],
            [0, 1, 0],
            [0, 0, 1],
            [1, 1, 0],
            [1, 0, 1],
            [0, 1, 1]
        ];
        var a = color_case[d][0];
        var b = color_case[d][1];
        var c = color_case[d][2];
        var e = (1 - (j - 1) / 6);

        var r = Math.round(a * 200 * e);
        var g = Math.round(b * 200 * e);
        b = Math.round(c * 200 * e);
        color = 'rgba(' + r + ',' + g + ',' + b + ',1)';

        TargetColors.push([target, color]);
    }

    return color;
};

DesignerMove.clickOption = function (id_this, column_name, table_name) {
    var left = Glob_X - (document.getElementById(id_this).offsetWidth >> 1);
    document.getElementById(id_this).style.left = left + 'px';
    // var top = Glob_Y - document.getElementById(id_this).offsetHeight - 10;
    document.getElementById(id_this).style.top  = (screen.height / 4) + 'px';
    document.getElementById(id_this).style.display = 'block';
    document.getElementById('option_col_name').innerHTML = '<strong>' + Functions.sprintf(Messages.strAddOption, decodeURI(column_name)) + '</strong>';
    col_name = column_name;
    tab_name = table_name;
};

DesignerMove.closeOption = function () {
    document.getElementById('designer_optionse').style.display = 'none';
    document.getElementById('rel_opt').value = '--';
    document.getElementById('Query').value = '';
    document.getElementById('new_name').value = '';
    document.getElementById('operator').value = '---';
    document.getElementById('groupby').checked = false;
    document.getElementById('h_rel_opt').value = '--';
    document.getElementById('h_operator').value = '---';
    document.getElementById('having').value = '';
    document.getElementById('orderby').value = '---';
};

DesignerMove.selectAll = function (id_this, owner) {
    var parent = document.form1;
    downer = owner;
    var i;
    var k;
    var tab = [];
    for (i = 0; i < parent.elements.length; i++) {
        if (parent.elements[i].type === 'checkbox' && parent.elements[i].id.substring(0, (9 + id_this.length)) === 'select_' + id_this + '._') {
            if (document.getElementById('select_all_' + id_this).checked === true) {
                parent.elements[i].checked = true;
                parent.elements[i].disabled = true;
                var temp = '`' + id_this.substring(owner.length + 1) + '`.*';
            } else {
                parent.elements[i].checked = false;
                parent.elements[i].disabled = false;
            }
        }
    }
    if (document.getElementById('select_all_' + id_this).checked === true) {
        select_field.push('`' + id_this.substring(owner.length + 1) + '`.*');
        tab = id_this.split('.');
        from_array.push(tab[1]);
    } else {
        for (i = 0; i < select_field.length; i++) {
            if (select_field[i] === ('`' + id_this.substring(owner.length + 1) + '`.*')) {
                select_field.splice(i, 1);
            }
        }
        for (k = 0; k < from_array.length; k++) {
            if (from_array[k] === id_this) {
                from_array.splice(k, 1);
                break;
            }
        }
    }
    DesignerMove.reload();
};

DesignerMove.tableOnOver = function (id_this, val, buil) {
    buil = parseInt(buil);
    if (!val) {
        document.getElementById('id_zag_' + id_this).className = 'tab_zag_2';
        if (buil) {
            document.getElementById('id_zag_' + id_this + '_2').className = 'tab_zag_2';
        }
    } else {
        document.getElementById('id_zag_' + id_this).className = 'tab_zag';
        if (buil) {
            document.getElementById('id_zag_' + id_this + '_2').className = 'tab_zag';
        }
    }
};

/**
 * This function stores selected column information in select_field[]
 * In case column is checked it add else it deletes
 */
DesignerMove.storeColumn = function (id_this, owner, col) {
    var i;
    var k;
    if (document.getElementById('select_' + owner + '.' + id_this + '._' + col).checked === true) {
        select_field.push('`' + id_this + '`.`' + col + '`');
        from_array.push(id_this);
    } else {
        for (i = 0; i < select_field.length; i++) {
            if (select_field[i] === ('`' + id_this + '`.`' + col + '`')) {
                select_field.splice(i, 1);
                break;
            }
        }
        for (k = 0; k < from_array.length; k++) {
            if (from_array[k] === id_this) {
                from_array.splice(k, 1);
                break;
            }
        }
    }
};

/**
 * This function builds object and adds them to history_array
 * first it does a few checks on each object, then makes an object(where,rename,groupby,aggregate,orderby)
 * then a new history object is made and finally all these history objects are added to history_array[]
 */
DesignerMove.addObject = function () {
    var p;
    var where_obj;
    var rel = document.getElementById('rel_opt');
    var sum = 0;
    var init = history_array.length;
    if (rel.value !== '--') {
        if (document.getElementById('Query').value === '') {
            Functions.ajaxShowMessage(Functions.sprintf(Messages.strQueryEmpty));
            return;
        }
        p = document.getElementById('Query');
        where_obj = new where(rel.value, p.value);// make where object
        history_array.push(new history_obj(col_name, where_obj, tab_name, h_tabs[downer + '.' + tab_name], 'Where'));
        sum = sum + 1;
    }
    if (document.getElementById('new_name').value !== '') {
        var rename_obj = new rename(document.getElementById('new_name').value);// make Rename object
        history_array.push(new history_obj(col_name, rename_obj, tab_name, h_tabs[downer + '.' + tab_name], 'Rename'));
        sum = sum + 1;
    }
    if (document.getElementById('operator').value !== '---') {
        var aggregate_obj = new aggregate(document.getElementById('operator').value);
        history_array.push(new history_obj(col_name, aggregate_obj, tab_name, h_tabs[downer + '.' + tab_name], 'Aggregate'));
        sum = sum + 1;
        // make aggregate operator
    }
    if (document.getElementById('groupby').checked === true) {
        history_array.push(new history_obj(col_name, 'GroupBy', tab_name, h_tabs[downer + '.' + tab_name], 'GroupBy'));
        sum = sum + 1;
        // make groupby
    }
    if (document.getElementById('h_rel_opt').value !== '--') {
        if (document.getElementById('having').value === '') {
            return;
        }
        where_obj = new having(
            document.getElementById('h_rel_opt').value,
            document.getElementById('having').value,
            document.getElementById('h_operator').value
        );// make where object
        history_array.push(new history_obj(col_name, where_obj, tab_name, h_tabs[downer + '.' + tab_name], 'Having'));
        sum = sum + 1;
        // make having
    }
    if (document.getElementById('orderby').value !== '---') {
        var oderby_obj = new orderby(document.getElementById('orderby').value);
        history_array.push(new history_obj(col_name, oderby_obj, tab_name, h_tabs[downer + '.' + tab_name], 'OrderBy'));
        sum = sum + 1;
        // make orderby
    }
    Functions.ajaxShowMessage(Functions.sprintf(Messages.strObjectsCreated, sum));
    // output sum new objects created
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(init, history_array.length);
    DesignerMove.closeOption();
    $('#ab').accordion('refresh');
};

AJAX.registerTeardown('designer/move.js', function () {
    $('#side_menu').off('mouseenter mouseleave');
    $('#key_Show_left_menu').off('click');
    $('#toggleFullscreen').off('click');
    $('#newPage').off('click');
    $('#editPage').off('click');
    $('#savePos').off('click');
    $('#SaveAs').off('click');
    $('#delPages').off('click');
    $('#StartTableNew').off('click');
    $('#rel_button').off('click');
    $('#StartTableNew').off('click');
    $('#display_field_button').off('click');
    $('#reloadPage').off('click');
    $('#angular_direct_button').off('click');
    $('#grid_button').off('click');
    $('#key_SB_all').off('click');
    $('#SmallTabInvert').off('click');
    $('#relLineInvert').off('click');
    $('#exportPages').off('click');
    $('#query_builder').off('click');
    $('#key_Left_Right').off('click');
    $('#pin_Text').off('click');
    $('#canvas').off('click');
    $('#key_HS_all').off('click');
    $('#key_HS').off('click');
    $('.scroll_tab_struct').off('click');
    $('.scroll_tab_checkbox').off('click');
    $('#id_scroll_tab').find('tr').off('click', '.designer_Tabs2,.designer_Tabs');
    $('.designer_tab').off('click', '.select_all_1');
    $('.designer_tab').off('click', '.small_tab,.small_tab2');
    $('.designer_tab').off('click', '.small_tab_pref_1');
    $('.tab_zag_noquery').off('mouseover');
    $('.tab_zag_noquery').off('mouseout');
    $('.tab_zag_query').off('mouseover');
    $('.tab_zag_query').off('mouseout');
    $('.designer_tab').off('click','.tab_field_2,.tab_field_3,.tab_field');
    $('.designer_tab').off('click', '.select_all_store_col');
    $('.designer_tab').off('click', '.small_tab_pref_click_opt');
    $('#del_button').off('click');
    $('#cancel_button').off('click');
    $('#ok_add_object').off('click');
    $('#cancel_close_option').off('click');
    $('#ok_new_rel_panel').off('click');
    $('#cancel_new_rel_panel').off('click');
    $('#page_content').off('mouseup');
    $('#page_content').off('mousedown');
    $('#page_content').off('mousemove');
});

AJAX.registerOnload('designer/move.js', function () {
    $('#key_Show_left_menu').on('click', function () {
        DesignerMove.showLeftMenu(this);
        return false;
    });
    $('#toggleFullscreen').on('click', function () {
        DesignerMove.toggleFullscreen();
        return false;
    });
    $('#addOtherDbTables').on('click', function () {
        DesignerMove.addOtherDbTables();
        return false;
    });
    $('#newPage').on('click', function () {
        DesignerMove.new();
        return false;
    });
    $('#editPage').on('click', function () {
        DesignerMove.editPages();
        return false;
    });
    $('#savePos').on('click', function () {
        DesignerMove.save3();
        return false;
    });
    $('#SaveAs').on('click', function () {
        DesignerMove.saveAs();
        return false;
    });
    $('#delPages').on('click', function () {
        DesignerMove.deletePages();
        return false;
    });
    $('#StartTableNew').on('click', function () {
        DesignerMove.startTableNew();
        return false;
    });
    $('#rel_button').on('click', function () {
        DesignerMove.startRelation();
        return false;
    });
    $('#display_field_button').on('click', function () {
        DesignerMove.startDisplayField();
        return false;
    });
    $('#reloadPage').on('click', function () {
        $('#designer_tab').trigger('click');
    });
    $('#angular_direct_button').on('click', function () {
        DesignerMove.angularDirect();
        return false;
    });
    $('#grid_button').on('click', function () {
        DesignerMove.grid();
        return false;
    });
    $('#key_SB_all').on('click', function () {
        DesignerMove.smallTabAll(this);
        return false;
    });
    $('#SmallTabInvert').on('click', function () {
        DesignerMove.smallTabInvert();
        return false;
    });
    $('#relLineInvert').on('click', function () {
        DesignerMove.relationLinesInvert();
        return false;
    });
    $('#exportPages').on('click', function () {
        DesignerMove.exportPages();
        return false;
    });
    $('#query_builder').on('click', function () {
        build_query('SQL Query on Database', 0);
    });
    $('#key_Left_Right').on('click', function () {
        DesignerMove.sideMenuRight(this);
        return false;
    });
    $('#side_menu').hover(function () {
        DesignerMove.showText();
        return false;
    }, function () {
        DesignerMove.hideText();
        return false;
    });
    $('#pin_Text').on('click', function () {
        DesignerMove.pinText(this);
        return false;
    });
    $('#canvas').on('click', function (event) {
        DesignerMove.canvasClick(this, event);
    });
    $('#key_HS_all').on('click', function () {
        DesignerMove.hideTabAll(this);
        return false;
    });
    $('#key_HS').on('click', function () {
        DesignerMove.noHaveConstr(this);
        return false;
    });
    $('.scroll_tab_struct').on('click', function () {
        DesignerMove.startTabUpd($(this).attr('table_name'));
    });
    $('.scroll_tab_checkbox').on('click', function () {
        DesignerMove.visibleTab(this,$(this).val());
    });
    $('#id_scroll_tab').find('tr').on('click', '.designer_Tabs2,.designer_Tabs', function () {
        DesignerMove.selectTab($(this).attr('designer_url_table_name'));
    });
    $('.designer_tab').on('click', '.select_all_1', function () {
        DesignerMove.selectAll($(this).attr('designer_url_table_name'), $(this).attr('designer_out_owner'));
    });
    $('.designer_tab').on('click', '.small_tab,.small_tab2', function () {
        DesignerMove.smallTab($(this).attr('table_name'), 1);
    });
    $('.designer_tab').on('click', '.small_tab_pref_1', function () {
        DesignerMove.startTabUpd($(this).attr('table_name_small'));
    });
    $('.tab_zag_noquery').mouseover(function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),0, $(this).attr('query_set'));
    });
    $('.tab_zag_noquery').mouseout(function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),1, $(this).attr('query_set'));
    });
    $('.tab_zag_query').mouseover(function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),0, 1);
    });
    $('.tab_zag_query').mouseout(function () {
        DesignerMove.tableOnOver($(this).attr('table_name'),1, 1);
    });
    $('.designer_tab').on('click','.tab_field_2,.tab_field_3,.tab_field', function () {
        var params = ($(this).attr('click_field_param')).split(',');
        DesignerMove.clickField(params[3], params[0], params[1], params[2]);
    });
    $('.designer_tab').on('click', '.select_all_store_col', function () {
        var params = ($(this).attr('store_column_param')).split(',');
        DesignerMove.storeColumn(params[0], params[1], params[2]);
    });
    $('.designer_tab').on('click', '.small_tab_pref_click_opt', function () {
        var params = ($(this).attr('Click_option_param')).split(',');
        DesignerMove.clickOption(params[0], params[1], params[2]);
    });
    $('input#del_button').on('click', function () {
        DesignerMove.updRelation();
    });
    $('input#cancel_button').on('click', function () {
        document.getElementById('layer_upd_relation').style.display = 'none';
        DesignerMove.reload();
    });
    $('input#ok_add_object').on('click', function () {
        DesignerMove.addObject();
    });
    $('input#cancel_close_option').on('click', function () {
        DesignerMove.closeOption();
    });
    $('input#ok_new_rel_panel').on('click', function () {
        DesignerMove.newRelation();
    });
    $('input#cancel_new_rel_panel').on('click', function () {
        document.getElementById('layer_new_relation').style.display = 'none';
    });
    $('#page_content').on('mousedown', function (e) {
        DesignerMove.mouseDown(e);
    });
    $('#page_content').on('mouseup', function (e) {
        DesignerMove.mouseUp(e);
    });
    $('#page_content').on('mousemove', function (e) {
        DesignerMove.mouseMove(e);
    });
});
