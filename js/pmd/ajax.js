/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
function PrintXML(data)
{
    var $root = $(data).find('root');
    if ($root.length === 0) {
        // error
        var myWin = window.open('', 'Report', 'width=400, height=250, resizable=1, scrollbars=1, status=1');
        var tmp = myWin.document;
        tmp.write(data);
        tmp.close();
    } else {
        // success
        if ($root.attr('act') == 'save_pos') {
            PMA_ajaxShowMessage($root.attr('return'));
        } else if ($root.attr('act') == 'relation_upd') {
            PMA_ajaxShowMessage($root.attr('return'));
            if ($root.attr('b') == '1') {
                contr.splice($root.attr('K'), 1);
                Re_load();
            }
        } else if ($root.attr('act') == 'relation_new') {
            PMA_ajaxShowMessage($root.attr('return'));
            if ($root.attr('b') == '1') {
                var i  = contr.length;
                var t1 = $root.attr('DB1') + '.' + $root.attr('T1');
                var f1 = $root.attr('F1');
                var t2 = $root.attr('DB2') + '.' + $root.attr('T2');
                var f2 = $root.attr('F2');
                contr[i] = [];
                contr[i][''] = [];
                contr[i][''][t2] = [];
                contr[i][''][t2][f2] = [];
                contr[i][''][t2][f2][0] = t1;
                contr[i][''][t2][f2][1] = f1;
                Re_load();
            }
        }
    }
}

/**
 *
 */
function makeRequest(url, parameters)
{
    var $msg = PMA_ajaxShowMessage();
    $.post(url, parameters, function (data) {
        PMA_ajaxRemoveMessage($msg);
        PrintXML(data);
    });
    return true;
}
