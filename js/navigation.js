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
function toggle(id, only_open) {
    var el = document.getElementById('subel' + id);
    if (! el) {
        return false;
    }

    var img = document.getElementById('el' + id + 'Img');

    if (el.style.display == 'none' || only_open) {
        el.style.display = '';
        if (img) {
            img.src = image_minus;
            img.alt = '-';
        }
    } else {
        el.style.display = 'none';
        if (img) {
            img.src = image_plus;
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
        pma_navi_width = parseInt(parent.document.getElementById('mainFrameset').cols)
    } else {
        pma_navi_width = parent.document.getElementById('mainFrameset').cols.match(/\d+$/) 
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
function PMA_getCookie(name) {
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
function PMA_setCookie(name, value, expires, path, domain, secure) {
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
function fast_filter(value){
    lowercase_value = value.toLowerCase();
    $("#subel0 a[class!='tableicon']").each(function(idx,elem){
        $elem = $(elem);
        // .indexOf is case sensitive so convert to lowercase to compare
        if (value && $elem.html().toLowerCase().indexOf(lowercase_value) == -1) {
            $elem.parent().hide();
        } else {
            $elem.parent().show();
        }
    });
}

/**
 * Clears fast filter.
 */
function clear_fast_filter() {
    var elm = $('#NavFilter input');
    elm.val('');
    fast_filter('');
    elm.focus();
}

/**
 * Reloads the recent tables list.
 */
function PMA_reloadRecentTable() {
    $.get('navigation.php',
            { 'token' : window.parent.token, 'ajax_request' : true, 'recent_table' : true },
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
    $('input[id="fast_filter"]').focus(function() {
        if($(this).attr("value") === "filter tables by name") {
            clear_fast_filter();
        }
    });
    $('#clear_fast_filter').click(clear_fast_filter);
    $('#fast_filter').focus(function (evt) {evt.target.select();});
    $('#fast_filter').keyup(function (evt) {fast_filter(evt.target.value);});

    /* Jump to recent table */
    $('#recentTable').change(function() {
        if (this.value != '') {
            var arr = this.value.split('.');
            window.parent.setDb(arr[0]);
            window.parent.setTable(arr[1]);
            window.parent.refreshMain($('#LeftDefaultTabTable')[0].value);
        }
    });
    /* Create table */
    $('#newtable a.ajax').click(function(event){
        event.preventDefault();
       	var $url = $('#newtable a').attr("href");
       	if ($url.substring(0, 15) == "tbl_create.php?") {
             $url = $url.substring(15);
        }
       	var $div = parent.frame_content.$('<div id="create_table_dialog"></div>');

        /* @todo Validate this form! */

        /**
        *  @var    button_options  Object that stores the options passed to jQueryUI
        *                          dialog
        */
        var button_options = {};
        // in the following function we need to use $(this)
        button_options[PMA_messages['strCancel']] = function() {$(this).parent().dialog('close').remove();}

        var button_options_error = {};
        button_options_error[PMA_messages['strOK']] = function() {$(this).parent().dialog('close').remove();}

        var $msgbox = PMA_ajaxShowMessage();

        $.get( "tbl_create.php" , $url+"&num_fields=1&ajax_request=true" ,  function(data) {
            //in the case of an error, show the error message returned.
            if (data.success != undefined && data.success == false) {
                $div
                .append(data.error)
                .dialog({
                    title: PMA_messages['strCreateTable'],
                    height: 230,
                    width: 900,
                    open: PMA_verifyTypeOfAllColumns,
                    buttons : button_options_error
                })// end dialog options
                //remove the redundant [Back] link in the error message.
                .find('fieldset').remove();
            } else {
                $div
                .append(data)
                .dialog({
                    title: PMA_messages['strCreateTable'],
                    height: 600,
                    width: 900,
                    open: PMA_verifyTypeOfAllColumns,
                    buttons : button_options
                }); // end dialog options
            }
            PMA_ajaxRemoveMessage($msgbox);
        }) // end $.get()
    });//end of create new table
});//end of document get ready

