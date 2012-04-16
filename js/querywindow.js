/* vim: set expandtab sw=4 ts=4 sts=4: */
function PMA_queryAutoCommit()
{
    var sqlqueryform = document.getElementById('sqlqueryform');
    sqlqueryform.target = window.opener.frame_content.name;
    sqlqueryform.submit();
    return;
}

function PMA_querywindowCommit(tab)
{
    var $hiddenqueryform = $('#hiddenqueryform');
    $hiddenqueryform.find("input[name='querydisplay_tab']").val(tab);
    $hiddenqueryform.submit();
    return false;
}

function PMA_querywindowSetFocus()
{
    $('#sqlquery').focus();
}

function PMA_querywindowResize()
{
    var $el = $(this)[0];
    var $querywindowcontainer = $('#querywindowcontainer');

    // for Gecko
    if (typeof($el.sizeToContent) == 'function') {
        $el.sizeToContent();
        //self.scrollbars.visible = false;
        // give some more space ... to prevent 'fli(pp/ck)ing'
        $el.resizeBy(10, 50);
        return;
    }

    // for IE, Opera
    if ($querywindowcontainer.length) {
        // get content size
        var newWidth  = $querywindowcontainer.width();
        var newHeight = $querywindowcontainer.height();

        // set size to contentsize
        // plus some offset for scrollbars, borders, statusbar, menus ...
        $el.resizeTo(newWidth + 45, newHeight + 75);
    }
}
