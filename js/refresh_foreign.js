

var refreshForeign = function (buttonId) {
    var element = $('#' + buttonId);
    var select = element.parent().find('select');
    $.post('refresh_foreign.php',{ db: element.data('db'), table: element.data('table') },function (data) {
        console.log(data);
    });
};
