

var refreshForeign = function (buttonId) {
    var element = $('#' + buttonId);
    var select = element.parent().find('select');
    $.post('refresh_foreign.php',{ db: element.data('db'), table: element.data('table'), column: element.data('field') },function (data) {
        console.log(data);
        select.empty();
        select.append(data);
    });
};
