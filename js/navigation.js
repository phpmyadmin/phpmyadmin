/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation frame
 */

/**
 * init
 */
var today = new Date();
var expires = new Date(today.getTime() + (56 * 86400000));
var pma_navi_width;
var pma_saveframesize_timeout = null;

/**
 * opens/closes (hides/shows) tree elements
 *
 * @param   string  id          id of the element in the DOM
 * @param   boolean only_open   do not close/hide element
 */
function toggle(id, only_open)
{
    var el = document.getElementById('subel' + id);
    if (! el) {
        return false;
    }

    var img = document.getElementById('el' + id + 'Img');

    if (el.style.display == 'none' || only_open) {
        el.style.display = '';
        if (img) {
            var newimg = PMA_getImage('b_minus.png');
            img.className = newimg.attr('class');
            img.src = newimg.attr('src');
            img.alt = '-';
        }
        // if only one sub-list, open as well
        var $submenus = $(el).find("> li > ul");
        var $sublinks = $(el).find("> li > a.item, > li > a.tableicon");
        if ($submenus.length == 1 && $sublinks.length == 0) {
            toggle($submenus.prop("id").split("subel").join(""), true);
        }
    } else {
        el.style.display = 'none';
        if (img) {
            var newimg = PMA_getImage('b_plus.png');
            img.className = newimg.attr('class');
            img.src = newimg.attr('src');
            img.alt = '+';
        }
    }
    return true;
}

function PMA_callFunctionDelayed(myfunction, delay)
{
    if (typeof pma_saveframesize_timeout == "number") {
         window.clearTimeout(pma_saveframesize_timeout);
        pma_saveframesize_timeout = null;
    }
}

/**
 * saves current navigation frame width in a cookie
 * usally called on resize of the navigation frame
 */
function PMA_saveFrameSizeReal()
{
    if (parent.text_dir == 'ltr') {
        pma_navi_width = parseInt(parent.document.getElementById('mainFrameset').cols);
    } else {
        pma_navi_width = parent.document.getElementById('mainFrameset').cols.match(/\d+$/);
    }
    if ((pma_navi_width > 0) && (pma_navi_width != PMA_getCookie('pma_navi_width'))) {
        PMA_setCookie('pma_navi_width', pma_navi_width, expires);
    }
}

/**
 * calls PMA_saveFrameSizeReal with delay
 */
function PMA_saveFrameSize()
{
    //alert(typeof(pma_saveframesize_timeout) + ' : ' + pma_saveframesize_timeout);

    if (typeof pma_saveframesize_timeout == "number") {
        window.clearTimeout(pma_saveframesize_timeout);
        pma_saveframesize_timeout = null;
    }

    pma_saveframesize_timeout = window.setTimeout(PMA_saveFrameSizeReal, 2000);
}

/**
 * sets navigation frame width to the value stored in the cookie
 * usally called on document load
 */
function PMA_setFrameSize()
{
    pma_navi_width = PMA_getCookie('pma_navi_width');
    //alert('from cookie: ' + typeof(pma_navi_width) + ' : ' + pma_navi_width);
    if (pma_navi_width != null && parent.document != document) {
        if (parent.text_dir == 'ltr') {
            parent.document.getElementById('mainFrameset').cols = pma_navi_width + ',*';
        } else {
            parent.document.getElementById('mainFrameset').cols = '*,' + pma_navi_width;
        }
        //alert('framesize set');
    }
}

/**
 * retrieves a named value from cookie
 *
 * @param   string  name    name of the value to retrieve
 * @return  string  value   value for the given name from cookie
 */
function PMA_getCookie(name)
{
    var start = document.cookie.indexOf(name + "=");
    var len = start + name.length + 1;
    if ((!start) && (name != document.cookie.substring(0, name.length))) {
        return null;
    }
    if (start == -1) {
        return null;
    }
    var end = document.cookie.indexOf(";", len);
    if (end == -1) {
        end = document.cookie.length;
    }
    return unescape(document.cookie.substring(len,end));
}

/**
 * stores a named value into cookie
 *
 * @param   string  name    name of value
 * @param   string  value   value to be stored
 * @param   Date    expires expire time
 * @param   string  path
 * @param   string  domain
 * @param   boolean secure
 */
function PMA_setCookie(name, value, expires, path, domain, secure)
{
    document.cookie = name + "=" + escape(value) +
        ( (expires) ? ";expires=" + expires.toGMTString() : "") +
        ( (path)    ? ";path=" + path : "") +
        ( (domain)  ? ";domain=" + domain : "") +
        ( (secure)  ? ";secure" : "");
}

/**
 * hide all LI elements with second A tag which doesn`t contain requested value
 *
 * @param   string  value    requested value
 *
 */
function fast_filter(value)
{
    lowercase_value = value.toLowerCase();
    $("#subel0 a[class!='tableicon']").each(function(idx,elem){
        $elem = $(elem);
        // .indexOf is case sensitive so convert to lowercase to compare
        if (value && $elem.html().toLowerCase().indexOf(lowercase_value) == -1) {
            $elem.parent().hide();
        } else {
            $elem.parents('li').show();
        }
    });
}

/**
 * Clears fast filter.
 */
function clear_fast_filter()
{
    var $elm = $('#fast_filter');
    $elm.val('');
    fast_filter('');
}

/**
 * Reloads the recent tables list.
 */
function PMA_reloadRecentTable()
{
    $.get('navigation.php', {
            'token': window.parent.token,
            'server': window.parent.server,
            'ajax_request': true,
            'recent_table': true},
        function (data) {
            if (data.success == true) {
                $('#recentTable').html(data.options);
            }
        });
}

/* Performed on load */
$(document).ready(function(){
    /* Display filter */
    $('#NavFilter').css('display', 'inline');
    var txt = $('#fast_filter').val();

    $('#fast_filter.gray').live('focus', function() {
        $(this).removeClass('gray');
        clear_fast_filter();
    });

    $('#fast_filter:not(.gray)').live('focusout', function() {
        var $input = $(this);
        if ($input.val() == '') {
            $input
                .addClass('gray')
                .val(txt);
        }
    });

    $('#clear_fast_filter').click(function() {
        clear_fast_filter();
        $('#fast_filter').focus();
    });

    $('#fast_filter').keyup(function(evt) {
        fast_filter($(this).val());
    });

    /* Jump to recent table */
    $('#recentTable').change(function() {
        if (this.value != '') {
            var arr = jQuery.parseJSON(this.value);
            window.parent.setDb(arr['db']);
            window.parent.setTable(arr['table']);
            window.parent.refreshMain($('#LeftDefaultTabTable')[0].value);
        }
    });

    /* Create table */
    $('#newtable a.ajax').click(function(event){
        event.preventDefault();
        /*Getting the url */
        var url = $('#newtable a').attr("href");
        if (url.substring(0, 15) == "tbl_create.php?") {
             url = url.substring(15);
        }
        url = url +"&num_fields=&ajax_request=true";
        /*Creating a div on the frame_content frame */
        var div = parent.frame_content.$('<div id="create_table_dialog"></div>');
        var target = "tbl_create.php";

        /*
         * Calling to the createTableDialog function
         * (needs to be done in the context of frame_content in order
         *  for the qtip tooltips to work)
         * */
        parent.frame_content.PMA_createTableDialog(div , url , target);
    });//end of create new table
});//end of document get ready

