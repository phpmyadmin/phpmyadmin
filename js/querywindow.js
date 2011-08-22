function PMA_queryAutoCommit()
{
    document.getElementById('sqlqueryform').target = window.opener.frame_content.name;
    document.getElementById('sqlqueryform').submit();
    return;
}

function PMA_querywindowCommit(tab)
{
    $('#hiddenqueryform').find("input[name='querydisplay_tab']").attr("value" ,tab);
    $('#hiddenqueryform').submit();
    return false;
}

function PMA_querywindowSetFocus()
{
    $('#sqlquery').focus();
}

function PMA_querywindowResize()
{
    // for Gecko
    if (typeof($(this)[0].sizeToContent) == 'function') {
        $(this)[0].sizeToContent();
        //self.scrollbars.visible = false;
        // give some more space ... to prevent 'fli(pp/ck)ing'
        $(this)[0].resizeBy(10, 50);
        return;
    }

    // for IE, Opera
    if ($('#querywindowcontainer') != 'undefined') {
        // get content size
        var newWidth  = $("#querywindowcontainer")[0].offsetWidth;
        var newHeight = $("#querywindowcontainer")[0].offsetHeight;

        // set size to contentsize
        // plus some offset for scrollbars, borders, statusbar, menus ...
        $(this)[0].resizeTo(newWidth + 45, newHeight + 75);
    }
}
