/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @author  Ivan A Kirillov (Ivan.A.Kirillov@gmail.com)
 * @package phpMyAdmin-Designer
 */

/**
 * init
 */
var dx, dy, dy2;
var cur_click;
// update in Main()
var sm_x = 2, sm_y = 2;
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
var link_relation  = "";
var id_hint;
var canvas_width   = 0;
var canvas_height  = 0;
var osn_tab_width  = 0;
var osn_tab_height = 0;
var height_field   = 7;
var Glob_X, Glob_Y;
var timeoutID;
var layer_menu_cur_click = 0;
var step = 10;
var old_class;

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------


//window.captureEvents(Event.MOUSEDOWN | Event.MOUSEUP);
//---CROSS
document.onmousedown = MouseDown;
document.onmouseup   = MouseUp;
document.onmousemove = MouseMove;

var isIE = document.all && !window.opera;
var isNN = !document.all && document.getElementById;
var isN4 = document.layers;

if (isIE) {
    window.onscroll = General_scroll;
    document.onselectstart = function () {return false;};
}

//document.onmouseup = function(){General_scroll_end();}
function MouseDown(e)
{
    var offsetx, offsety;
    if (cur_click != null) {
        offsetx = isIE ? event.clientX + document.body.scrollLeft : e.pageX;
        offsety = isIE ? event.clientY + document.body.scrollTop : e.pageY;
        dx = offsetx - parseInt(cur_click.style.left);
        dy = offsety - parseInt(cur_click.style.top);
        //alert(" dx = " + dx + " dy = " +dy);
        document.getElementById("canvas").style.visibility = 'hidden';
        /*
        var left = parseInt(cur_click.style.left);
        var top  = parseInt(cur_click.style.top);
        dx = e.pageX - left;
        dy = e.pageY - top;

        alert(" dx = " + dx + " dy = " +dy);
        */
        cur_click.style.zIndex = 2;
    }
    if (layer_menu_cur_click) {
        offsetx = isIE ? event.clientX + document.body.scrollLeft : e.pageX;
        dx = offsetx - parseInt(document.getElementById("layer_menu").style.width);
    }
}

function MouseMove(e)
{
    //Glob_X = e.pageX;
    //Glob_Y = e.pageY;
    Glob_X = isIE ? event.clientX + document.body.scrollLeft : e.pageX;
    Glob_Y = isIE ? event.clientY + document.body.scrollTop : e.pageY;

    //mouseX = (bw.ns4||bw.ns6)? e.pageX: bw.ie&&bw.win&&!bw.ie4? (event.clientX-2)+document.body.scrollLeft : event.clientX+document.body.scrollLeft;
    //mouseY = (bw.ns4||bw.ns6)? e.pageY: bw.ie&&bw.win&&!bw.ie4? (event.clientY-2)+document.body.scrollTop : event.clientY+document.body.scrollTop;

    //window.status = "X = "+ Glob_X + " Y = "+ Glob_Y;

    if (cur_click != null) {
        var mGx = Glob_X - dx;
        var mGy = Glob_Y - dy;
        mGx = mGx > 0 ? mGx : 0;
        mGy = mGy > 0 ? mGy : 0;

        if (ON_grid) {
            mGx = mGx % step < step / 2 ? mGx - mGx % step : mGx - mGx % step + step;
            mGy = mGy % step < step / 2 ? mGy - mGy % step : mGy - mGy % step + step;
        }

        cur_click.style.left = mGx + 'px';
        cur_click.style.top  = mGy + 'px';
    }

    if (ON_relation || ON_display_field) {
        document.getElementById('hint').style.left = (Glob_X + 20) + 'px';
        document.getElementById('hint').style.top  = (Glob_Y + 20) + 'px';
    }

    if (layer_menu_cur_click) {
        document.getElementById("layer_menu").style.width = ((Glob_X - dx) >= 150 ? Glob_X - dx : 150) + 'px';
        //document.getElementById("layer_menu").style.height = Glob_Y - dy>=200?Glob_Y - dy:200;
        //document.getElementById("id_scroll_tab").style.height = Glob_Y - dy2;
    }
}

function MouseUp(e)
{
    if (cur_click != null) {
        document.getElementById("canvas").style.visibility = 'visible';
        Re_load();
        cur_click.style.zIndex = 1;
        cur_click = null;
    }
    layer_menu_cur_click = 0;
    //window.releaseEvents(Event.MOUSEMOVE);
}
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------


//function ToInt(s)
//{
//    return s.substring(0,s.length-2)*1; //re = /(\d+)\w*/; newstr = str.replace(re, "$1");
//}

function Canvas_pos()
{
    canvas_width  = document.getElementById('canvas').width  = osn_tab_width  - 3;
    canvas_height = document.getElementById('canvas').height = osn_tab_height - 3;

    if (isIE) {
        document.getElementById('canvas').style.width  = ((osn_tab_width  - 3)?(osn_tab_width  - 3):0) + 'px';
        document.getElementById('canvas').style.height = ((osn_tab_height - 3)?(osn_tab_height - 3):0) + 'px';
    }
}

function Osn_tab_pos()
{
    osn_tab_width  = parseInt(document.getElementById('osn_tab').style.width);
    osn_tab_height = parseInt(document.getElementById('osn_tab').style.height);
}


function Main()
{
    //alert( document.getElementById('osn_tab').offsetTop);
    //---CROSS
    if (isIE) {
        document.getElementById('top_menu').style.position = 'absolute';
        document.getElementById('layer_menu').style.position = 'absolute';
    }

    document.getElementById("layer_menu").style.top = -1000 + 'px'; //fast scroll
    sm_x += document.getElementById('osn_tab').offsetLeft;
    sm_y += document.getElementById('osn_tab').offsetTop;
    Osn_tab_pos();
    Canvas_pos();
    Small_tab_refresh();
    Re_load();
    id_hint = document.getElementById('hint');
    if (isIE) {
        General_scroll();
    }
}


//-------------------------------- new -----------------------------------------
function Rezize_osn_tab()
{
    var max_X = 0;
    var max_Y = 0;
    for (key in j_tabs) {
        var k_x = parseInt(document.getElementById(key).style.left) + document.getElementById(key).offsetWidth;
        var k_y = parseInt(document.getElementById(key).style.top) + document.getElementById(key).offsetHeight;
        max_X = max_X < k_x ? k_x : max_X;
        max_Y = max_Y < k_y ? k_y : max_Y;
    }

    osn_tab_width  = max_X + 50;
    osn_tab_height = max_Y + 50;
    Canvas_pos();
    document.getElementById('osn_tab').style.width = osn_tab_width + 'px';
    document.getElementById('osn_tab').style.height = osn_tab_height + 'px';
}
//------------------------------------------------------------------------------

/**
 * refreshes display, must be called after state changes
 */
function Re_load()
{
    Rezize_osn_tab();
    var n;
    var x1;
    var x2;
    var a = new Array();
    Clear();
    for (K in contr)
        for (key in contr[K])                     // contr name
            for (key2 in contr[K][key])           // table name
                for (key3 in contr[K][key][key2]) // field name
                {
                    if (!document.getElementById("check_vis_" + key2).checked ||
                        !document.getElementById("check_vis_" + contr[K][key][key2][key3][0]).checked) {
                        // if hide
                        continue;
                    }
                    var x1_left  = document.getElementById(key2).offsetLeft+1;
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
                    if (n == 1) {
                        x1 = x1_left - sm_s;
                        x2 = x2_right + sm_s;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n == 2) {
                        x1 = x1_right + sm_s;
                        x2 = x2_left - sm_s;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n == 3) {
                        x1 = x1_right + sm_s;
                        x2 = x2_right + sm_s;
                        s_right = 1;
                    }
                    if (n == 0) {
                        x1 = x1_left - sm_s;
                        x2 = x2_left - sm_s;
                        s_left = 1;
                    }
                    //alert(key2 + "." + key3);

                    var row_offset_top = 0;
                    //alert('id_tbody_' + key2);
                    //alert(document.getElementById('id_hide_tbody_' + key2));
                    var tab_hide_button = document.getElementById('id_hide_tbody_' + key2);

                    //alert(tab_hide_button.innerHTML);
                    if (tab_hide_button.innerHTML == 'v') {
                        row_offset_top = document.getElementById(key2 + "." + key3).offsetTop;
                    }

                    var y1 = document.getElementById(key2).offsetTop
                         + row_offset_top
                         + height_field;
                    //alert(1);

                    row_offset_top = 0;
                    var tab_hide_button = document.getElementById('id_hide_tbody_' + contr[K][key][key2][key3][0]);
                    if (tab_hide_button.innerHTML == 'v') {
                        row_offset_top = document.getElementById(contr[K][key][key2][key3][0]
                            + '.' + contr[K][key][key2][key3][1]).offsetTop;
                    }

                    var y2 =
                          document.getElementById(contr[K][key][key2][key3][0]).offsetTop
                        + row_offset_top
                        + height_field;

                    //alert(y1 + ' - ' + key2 + "." + key3);
                    Line0(x1 - sm_x, y1 - sm_y, x2 - sm_x, y2 - sm_y, "rgba(0,100,150,1)");
                }
}

/**
 * draws a line from x1:y1 to x2:y2 with color
 */
function Line(x1, y1, x2, y2, color_line)
{
    var canvas = document.getElementById("canvas");
    var ctx    = canvas.getContext("2d");
    ctx.strokeStyle = color_line;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.stroke();
}

/**
 * draws a relation/constraint line, whether angular or not
 */
function Line0(x1, y1, x2, y2, color_line)
{
    Circle(x1, y1, 3, 3, "rgba(0,0,255,1)");
    Rect(x2 - 1, y2 - 2, 4, 4, "rgba(0,0,255,1)");

    if (ON_angular_direct) {
        Line2(x1, y1, x2, y2, color_line);
    } else {
        Line3(x1, y1, x2, y2, color_line);
    }
}

/**
 * draws a angualr relation/constraint line
 */
function Line2(x1, y1, x2, y2, color_line)
{
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

    Line(x1, y1, x1_, y1, color_line);
    Line(x2, y2, x2_, y2, color_line);
    Line(x1_, y1, x2_, y2, color_line);
}

/**
 * draws a relation/constraint line
 */
function Line3(x1, y1, x2, y2, color_line)
{
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

        Line(x1, y1, x1_, y1, color_line);
        Line(x2, y2, x2_, y2, color_line);
        Line(x1_, y1, x2_, y2, color_line);
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

        Line(x1, y1, x1_, y1, color_line);
        Line(x2, y2, x2_, y2, color_line);
        Line(x1_, y1, x2_, y2, color_line);
        return;
    }

    var x_s = (x1 + x2) / 2;
    Line(x1, y1, x_s, y1, color_line);
    Line(x_s, y2, x2, y2, color_line);
    Line(x_s, y1, x_s, y2, color_line);
}

function Circle(x, y, r, w, color)
{
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.beginPath();
    ctx.moveTo(x, y);
    ctx.lineWidth = w;
    ctx.strokeStyle = color;
    ctx.arc(x, y, r, 0, 2 * Math.PI, true);
    ctx.stroke();
}

function Clear()
{
    var canvas = document.getElementById("canvas");
    var ctx    = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas_width, canvas_height);
}

function Rect(x1, y1, w, h, color)
{
    var ctx = document.getElementById('canvas').getContext('2d');
    ctx.fillStyle = color;
    ctx.fillRect(x1, y1, w, h);
}

//------------------------------ SAVE ------------------------------------------
function Save(url) // (del?) no for pdf
{
    for (key in j_tabs) {
        document.getElementById('t_x_' + key + '_').value = parseInt(document.getElementById(key).style.left);
        document.getElementById('t_y_' + key + '_').value = parseInt(document.getElementById(key).style.top);
        document.getElementById('t_v_' + key + '_').value = document.getElementById('id_tbody_' + key).style.display == 'none' ? 0 : 1;
        document.getElementById('t_h_' + key + '_').value = document.getElementById('check_vis_' + key).checked ? 1 : 0;
    }
    document.form1.action = url;
    document.form1.submit();
}

function Get_url_pos()
{
    var poststr = '';
    for (key in j_tabs) {
        poststr += '&t_x[' + key + ']=' + parseInt(document.getElementById(key).style.left);
        poststr += '&t_y[' + key + ']=' + parseInt(document.getElementById(key).style.top);
        poststr += '&t_v[' + key + ']=' + (document.getElementById('id_tbody_' + key).style.display == 'none' ? 0 : 1);
        poststr += '&t_h[' + key + ']=' + (document.getElementById('check_vis_' + key).checked ? 1 : 0);
    }
    return poststr;
}

function Save2()
{
    var poststr = 'IS_AJAX=1&server='+server+'&db=' + db + '&token=' + token + '&die_save_pos=1';
    poststr += Get_url_pos();
    makeRequest('pmd_save_pos.php', poststr);
}

function Grid()
{
    if (!ON_grid) {
        ON_grid = 1;
        document.getElementById('grid_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('grid_button').className = 'M_butt';
        ON_grid = 0;
    }
}

function Angular_direct()
{
    if (ON_angular_direct) {
        ON_angular_direct = 0;
        document.getElementById('angular_direct_button').className = 'M_butt_Selected_down';
    } else {
        ON_angular_direct = 1;
        document.getElementById('angular_direct_button').className = 'M_butt';
    }
    Re_load();
}
//++++++++++++++++++++++++++++++ RELATION ++++++++++++++++++++++++++++++++++++++
function Start_relation()
{
    if (ON_display_field) {
        return;
    }

    if (!ON_relation) {
        document.getElementById('foreign_relation').style.display = '';
        ON_relation = 1;
        document.getElementById('hint').innerHTML = LangSelectReferencedKey;
        document.getElementById('hint').style.visibility = "visible";
        document.getElementById('rel_button').className = 'M_butt_Selected_down';
    } else {
        document.getElementById('hint').innerHTML = "";
        document.getElementById('hint').style.visibility = "hidden";
        document.getElementById('rel_button').className = 'M_butt';
        click_field = 0;
        ON_relation = 0;
    }
}

function Click_field(T, f, PK) // table field
{
    if (ON_relation) {
        if (!click_field) {
            //.style.display=='none'        .style.visibility = "hidden"
            if (!PK) {
                alert(LangPleaseSelectPrimaryOrUniqueKey);
                return;// 0;
            }//PK
            if (j_tabs[db + '.' + T] != '1') {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            click_field = 1;
            link_relation = "T1=" + T + "&F1=" + f;
            document.getElementById('hint').innerHTML = LangSelectForeignKey;
        } else {
            Start_relation(); // hidden hint...
            if (j_tabs[db + '.' + T] != '1' || !PK) {
                document.getElementById('foreign_relation').style.display = 'none';
            }
            var left = Glob_X - (document.getElementById('layer_new_relation').offsetWidth>>1);
            document.getElementById('layer_new_relation').style.left = left + 'px';
            var top = Glob_Y - document.getElementById('layer_new_relation').offsetHeight - 10;
            document.getElementById('layer_new_relation').style.top  = top + 'px';
            document.getElementById('layer_new_relation').style.visibility = "visible";
            link_relation += '&T2=' + T + '&F2=' + f;
        }
    }

    if (ON_display_field) {
        // if is display field
        if (display_field[T] == f) {
            //alert(T);
            //s = '';for(k in display_field)s += k + ' = ' + display_field[k] + ',';alert(s);
            old_class = 'tab_field';
            //display_field.splice(T, 1);
            delete display_field[T];
            //s = '';for(k in display_field)s += k + ' = ' + display_field[k] + ', ';alert(s);
            //n = 0;for(k in display_field)n++;alert(n);
        } else {
            old_class = 'tab_field_3';
            if (display_field[T]) {
                document.getElementById('id_tr_' + T + '.' + display_field[T]).className = 'tab_field';
                //display_field.splice(T, 1);
                delete display_field[T];
            }
            display_field[T] = f;
        }
        ON_display_field = 0;
        document.getElementById('hint').innerHTML = "";
        document.getElementById('hint').style.visibility = "hidden";
        document.getElementById('display_field_button').className = 'M_butt';
        makeRequest('pmd_display_field.php', 'T=' + T + '&F=' + f + '&server=' + server + '&db=' + db + '&token=' + token);
    }
}

function New_relation()
{
    document.getElementById('layer_new_relation').style.visibility = 'hidden';
    link_relation += '&server=' + server + '&db=' + db + '&token=' + token + '&die_save_pos=0';
    link_relation += '&on_delete=' + document.getElementById('on_delete').value + '&on_update=' + document.getElementById('on_update').value;
    link_relation += Get_url_pos();

    //alert(link_relation);
    makeRequest('pmd_relation_new.php', link_relation);
}

//-------------------------- create tables -------------------------------------

function Start_table_new()
{
    window.location.href = 'db_operations.php?server=' + server + '&db=' + db + '&token=' + token;
}

function Start_tab_upd(table)
{
    window.location.href = 'tbl_structure.php?server=' + server + '&db=' + db + '&token=' + token + '&table=' + table;
}
//--------------------------- hide tables --------------------------------------

function Small_tab_all(id_this) // max/min all tables
{
    if (id_this.alt == "v") {
        for (key in j_tabs) {
            if (document.getElementById('id_hide_tbody_'+key).innerHTML == "v") {
                Small_tab(key, 0);
            }
        }
        id_this.alt = ">";
        id_this.src = "pmd/images/rightarrow1.png";
    } else {
        for (key in j_tabs) {
            if (document.getElementById('id_hide_tbody_'+key).innerHTML != "v") {
                Small_tab(key, 0);
            }
        }
        id_this.alt = "v";
        id_this.src = "pmd/images/downarrow1.png";
    }
    Re_load();
}

function Small_tab_invert() // invert max/min all tables
{
    for (key in j_tabs) {
        Small_tab(key, 0);
    }
    Re_load();
}

function Small_tab_refresh()
{
     for (key in j_tabs) {
         if(document.getElementById('id_hide_tbody_'+key).innerHTML != "v") {
             Small_tab(key, 0);
             Small_tab(key, 0);
         }
     }
}

function Small_tab(t, re_load)
{
    var id      = document.getElementById('id_tbody_' + t);
    var id_this = document.getElementById('id_hide_tbody_' + t);
    var id_t    = document.getElementById(t);

    id_t.style.width = id_t.offsetWidth + 'px';
    if (id_this.innerHTML == "v") {
        //---CROSS
        id.style.display = 'none';
        id_this.innerHTML = '>';
    } else {
        id.style.display = '';
        id_this.innerHTML = 'v';
    }
    if (re_load) {
        Re_load();
    }
}
//------------------------------------------------------------------------------
function Select_tab(t)
{
    var id_zag = document.getElementById('id_zag_' + t);
    if (id_zag.className != 'tab_zag_3') {
        document.getElementById('id_zag_' + t).className = 'tab_zag_2';
    } else {
        document.getElementById('id_zag_' + t).className = 'tab_zag';
    }
    //----------
    var id_t = document.getElementById(t);
    window.scrollTo(parseInt(id_t.style.left) - 300, parseInt(id_t.style.top) - 300);

    setTimeout(function(){document.getElementById('id_zag_' + t).className = 'tab_zag';}, 800);
}
//------------------------------------------------------------------------------

function Canvas_click(id)
{
    var n = 0;
    var relation_name = 0;
    var selected = 0;
    var a = new Array();
    var Key0, Key1, Key2, Key3, Key, x1, x2;
    Clear();
    for (K in contr)
        for (key in contr[K])
            for (key2 in contr[K][key])
                for (key3 in contr[K][key][key2]) {
                    if (!document.getElementById("check_vis_"+key2).checked ||
                        !document.getElementById("check_vis_"+contr[K][key][key2][key3][0]).checked) continue; // if hide
                    var x1_left  = document.getElementById(key2).offsetLeft + 1;//document.getElementById(key2+"."+key3).offsetLeft;
                    var x1_right = x1_left + document.getElementById(key2).offsetWidth;
                    var x2_left  = document.getElementById(contr[K][key][key2][key3][0]).offsetLeft;//+document.getElementById(contr[K][key2][key3][0]+"."+contr[K][key2][key3][1]).offsetLeft
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
                    if (n == 1) {
                        x1 = x1_left - sm_s;
                        x2 = x2_right + sm_s;
                        if (x1 < x2) {
                            n = 0;
                        }
                    }
                    if (n == 2) {
                        x1 = x1_right + sm_s;
                        x2 = x2_left - sm_s;
                        if (x1 > x2) {
                            n = 0;
                        }
                    }
                    if (n == 3) {
                        x1 = x1_right + sm_s;
                        x2 = x2_right + sm_s;
                        s_right = 1;
                    }
                    if (n == 0) {
                        x1 = x1_left - sm_s;
                        x2 = x2_left - sm_s;
                        s_left    = 1;
                    }

                    var y1 = document.getElementById(key2).offsetTop + document.getElementById(key2+"."+key3).offsetTop + height_field;
                    var y2 = document.getElementById(contr[K][key][key2][key3][0]).offsetTop +
                                     document.getElementById(contr[K][key][key2][key3][0]+"."+contr[K][key][key2][key3][1]).offsetTop + height_field;
                    if (!selected && Glob_X > x1 - 10 && Glob_X < x1 + 10 && Glob_Y > y1 - 7 && Glob_Y < y1 + 7) {
                        Line0(x1 - sm_x, y1 - sm_y, x2 - sm_x, y2 - sm_y, "rgba(255,0,0,1)");
                        selected = 1; // Rect(x1-sm_x,y1-sm_y,10,10,"rgba(0,255,0,1)");
                        relation_name = key; //
                        Key0 = contr[K][key][key2][key3][0];
                        Key1 = contr[K][key][key2][key3][1];
                        Key2 = key2; Key3 = key3;
                        Key = K;
                    } else {
                        Line0(x1 - sm_x, y1 - sm_y, x2 - sm_x, y2 - sm_y, "rgba(0,100,150,1)");
                    }
                }
    if (selected) {
        // select relations
        //alert(Key0+' - '+Key1+' - '+Key2+' - '+Key3);
        var left = Glob_X - (document.getElementById('layer_upd_relation').offsetWidth>>1);
        document.getElementById('layer_upd_relation').style.left = left + 'px';
        var top = Glob_Y - document.getElementById('layer_upd_relation').offsetHeight - 10;
        document.getElementById('layer_upd_relation').style.top = top + 'px';
        document.getElementById('layer_upd_relation').style.visibility = 'visible';
        link_relation = 'T1=' + Key0 + '&F1=' + Key1 + '&T2=' + Key2 + '&F2=' + Key3 + '&K=' + Key;
    }
}

function Upd_relation()
{
    document.getElementById('layer_upd_relation').style.visibility = 'hidden';
    link_relation += '&server=' + server + '&db=' + db + '&token=' + token + '&die_save_pos=0';
    link_relation += Get_url_pos();
    makeRequest('pmd_relation_upd.php', link_relation);
}

function VisibleTab(id, t_n)
{
    if (id.checked) {
        document.getElementById(t_n).style.visibility = 'visible';
    } else {
        document.getElementById(t_n).style.visibility = 'hidden';
    }
    Re_load();
}

function Hide_tab_all(id_this) // max/min all tables
{
    if (id_this.alt == 'v') {
        id_this.alt = '>';
        id_this.src = "pmd/images/rightarrow1.png";
    } else {
        id_this.alt = 'v';
        id_this.src = "pmd/images/downarrow1.png";
    }
    var E = document.form1;
    for (i = 0; i < E.elements.length; i++) {
        if (E.elements[i].type == "checkbox" && E.elements[i].id.substring(0, 10) == 'check_vis_') {
            if (id_this.alt == 'v') {
                E.elements[i].checked = true;
                document.getElementById(E.elements[i].value).style.visibility = 'visible';
            } else {
                E.elements[i].checked = false;
                document.getElementById(E.elements[i].value).style.visibility = 'hidden';
            }
        }
    }
    Re_load();
}

function in_array_k(x, m)
{
    var b = 0;
    for (u in m) {
        if (x == u) {
            b=1;
            break;
        }
    }
    return b;
}

function No_have_constr(id_this)
{
    var a = new Array();
    for (K in contr)
        for (key in contr[K])                     // contr name
            for (key2 in contr[K][key])           // table name
                for (key3 in contr[K][key][key2]) // field name
                    a[key2] = a[contr[K][key][key2][key3][0]] = 1; // exist constr


    if (id_this.alt == 'v') {
        id_this.alt = '>';
        id_this.src = "pmd/images/rightarrow2.png";
    } else {
        id_this.alt = 'v';
        id_this.src = "pmd/images/downarrow2.png";
    }
    var E = document.form1;
    for (i = 0; i < E.elements.length; i++) {
        if (E.elements[i].type == "checkbox" && E.elements[i].id.substring(0, 10) == 'check_vis_')
        {
            if (!in_array_k(E.elements[i].value, a))
            if (id_this.alt == 'v') {
                E.elements[i].checked = true;
                document.getElementById(E.elements[i].value).style.visibility = 'visible';
            } else {
                E.elements[i].checked = false;
                document.getElementById(E.elements[i].value).style.visibility = 'hidden';
            }
        }
    }
}

function Help()
{
    var WinHelp = window.open("pmd_help.php", "wind1", "top=200,left=400,width=300,height=200,resizable=yes,scrollbars=yes,menubar=no");
}

function PDF_save()
{
    // var WinPDF =
    // window.open("pmd_pdf.php?token="+token+"&db="+db,"wind1", "top=200,left=200,width=200,height=100,resizable=yes,scrollbars=yes,menubar=no");
    Save('pmd_pdf.php?server=' + server + '&token=' + token + '&db=' + db);
}

function General_scroll()
{
    /*
    if (!document.getElementById('show_relation_olways').checked) {
        document.getElementById("canvas").style.visibility = 'hidden';
        clearTimeout(timeoutID);
        timeoutID = setTimeout(General_scroll_end, 500);
    }
    */
    //if (timeoutID)
    clearTimeout(timeoutID);
    timeoutID = setTimeout
    (
        function()
        {
            document.getElementById('top_menu').style.left = document.body.scrollLeft + 'px';
            document.getElementById('top_menu').style.top  = document.body.scrollTop + 'px';
            document.getElementById('layer_menu').style.left = document.body.scrollLeft + 'px';
            document.getElementById('layer_menu').style.top  = (document.body.scrollTop + document.getElementById('top_menu').offsetHeight) + 'px';
        }
        ,200
    );
}

/*
function General_scroll_end()
{
    document.getElementById('layer_menu').style.left = document.body.scrollLeft;
    document.getElementById('layer_menu').style.top  = document.body.scrollTop + document.getElementById('top_menu').offsetHeight;
    if (isIE) {
        document.getElementById('layer_menu').style.left = document.body.scrollLeft;
        document.getElementById('layer_menu').style.top  = document.body.scrollTop + document.getElementById('top_menu').offsetHeight;
    }
    document.getElementById("canvas").style.visibility = 'visible';
}
*/

function Show_left_menu(id_this) // max/min all tables
{
    if (id_this.alt == "v") {
        document.getElementById("layer_menu").style.top = document.getElementById('top_menu').offsetHeight + 'px';
        document.getElementById("layer_menu").style.visibility = 'visible';
        id_this.alt = ">";
        id_this.src = "pmd/images/uparrow2_m.png";
        if (isIE) {
            General_scroll();
        }
    } else {
        document.getElementById("layer_menu").style.top = -1000 + 'px'; //fast scroll
        document.getElementById("layer_menu").style.visibility = 'hidden';
        id_this.alt = "v";
        id_this.src = "pmd/images/downarrow2_m.png";
    }
}
//------------------------------------------------------------------------------
function Top_menu_right(id_this)
{
    if (id_this.alt == ">") {
        document.getElementById('top_menu').style.marginLeft = document.getElementById('top_menu').offsetWidth + 'px'; // = 350
        id_this.alt = "<";
        id_this.src = "pmd/images/2leftarrow_m.png";
    } else {
        document.getElementById('top_menu').style.marginLeft = 0;
        id_this.alt = ">";
        id_this.src = "pmd/images/2rightarrow_m.png";
    }
}
//------------------------------------------------------------------------------
function Start_display_field()
{
    if (ON_relation) {
        return;
    }
    if (!ON_display_field) {
        ON_display_field = 1;
        document.getElementById('hint').innerHTML = LangChangeDisplay;
        document.getElementById('hint').style.visibility = "visible";
        document.getElementById('display_field_button').className = 'M_butt_Selected_down';//'#FFEE99';gray #AAAAAA

        if (isIE) { // correct for IE
            document.getElementById('display_field_button').className = 'M_butt_Selected_down_IE';
        }
    } else {
        document.getElementById('hint').innerHTML = "";
        document.getElementById('hint').style.visibility = "hidden";
        document.getElementById('display_field_button').className = 'M_butt';
        ON_display_field = 0;
    }
}
