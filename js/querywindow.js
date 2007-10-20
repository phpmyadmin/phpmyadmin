function PMA_queryAutoCommit()
{
    document.getElementById('sqlqueryform').target = window.opener.frame_content.name;
    document.getElementById('sqlqueryform').submit();
    return;
}

function PMA_querywindowCommit(tab)
{
    document.getElementById('hiddenqueryform').querydisplay_tab.value = tab;
    document.getElementById('hiddenqueryform').submit();
    return false;
}

function PMA_querywindowSetFocus()
{
    document.getElementById('sqlquery').focus();
}

function PMA_querywindowResize()
{
    // for Gecko
    if (typeof(self.sizeToContent) == 'function') {
        self.sizeToContent();
        //self.scrollbars.visible = false;
        // give some more space ... to prevent 'fli(pp/ck)ing'
        self.resizeBy(10, 50);
        return;
    }

    // for IE, Opera
    if (document.getElementById && typeof(document.getElementById('querywindowcontainer')) != 'undefined') {

        // get content size
        var newWidth  = document.getElementById('querywindowcontainer').offsetWidth;
        var newHeight = document.getElementById('querywindowcontainer').offsetHeight;

        // set size to contentsize
        // plus some offset for scrollbars, borders, statusbar, menus ...
        self.resizeTo(newWidth + 45, newHeight + 75);
    }
}
