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
    $hiddenqueryform.addClass('disableAjax').submit();
    return false;
}

function PMA_querywindowSetFocus()
{
    $('#sqlquery').focus();
}

$(function () {
    $('#topmenucontainer').css('padding', 0);
});
