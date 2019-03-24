/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server Status Advisor
 *
 * @package PhpMyAdmin
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status_advisor.js', function () {
    $('a[href="#openAdvisorInstructions"]').off('click');
    $('#statustabs_advisor').html('');
    $('#advisorDialog').remove();
    $('#instructionsDialog').remove();
});

AJAX.registerOnload('server_status_advisor.js', function () {
    // if no advisor is loaded
    if ($('#advisorData').length === 0) {
        return;
    }

    /** ** Server config advisor ****/
    var $dialog = $('<div />').attr('id', 'advisorDialog');
    var $instructionsDialog = $('<div />')
        .attr('id', 'instructionsDialog')
        .html($('#advisorInstructionsDialog').html());

    $('a[href="#openAdvisorInstructions"]').click(function () {
        var dlgBtns = {};
        dlgBtns[PMA_messages.strClose] = function () {
            $(this).dialog('close');
        };
        $instructionsDialog.dialog({
            title: PMA_messages.strAdvisorSystem,
            width: '60%',
            buttons: dlgBtns
        });
    });

    var $cnt = $('#statustabs_advisor');
    var $tbody;
    var $tr;
    var str;
    var even = true;

    data = JSON.parse($('#advisorData').text());
    $cnt.html('');

    if (data.parse.errors.length > 0) {
        $cnt.append('<b>Rules file not well formed, following errors were found:</b><br />- ');
        $cnt.append(data.parse.errors.join('<br/>- '));
        $cnt.append('<p></p>');
    }

    if (data.run.errors.length > 0) {
        $cnt.append('<b>Errors occurred while executing rule expressions:</b><br />- ');
        $cnt.append(data.run.errors.join('<br/>- '));
        $cnt.append('<p></p>');
    }

    if (data.run.fired.length > 0) {
        $cnt.append('<p><b>' + PMA_messages.strPerformanceIssues + '</b></p>');
        $cnt.append('<table class="data" id="rulesFired" border="0"><thead><tr>' +
                    '<th>' + PMA_messages.strIssuse + '</th><th>' + PMA_messages.strRecommendation +
                    '</th></tr></thead><tbody></tbody></table>');
        $tbody = $cnt.find('table#rulesFired');

        var rc_stripped;

        $.each(data.run.fired, function (key, value) {
            // recommendation may contain links, don't show those in overview table (clicking on them redirects the user)
            rc_stripped = $.trim($('<div>').html(value.recommendation).text());
            $tbody.append($tr = $('<tr class="linkElem noclick"><td>' +
                                    value.issue + '</td><td>' + rc_stripped + ' </td></tr>'));
            even = !even;
            $tr.data('rule', value);

            $tr.click(function () {
                var rule = $(this).data('rule');
                $dialog
                    .dialog({ title: PMA_messages.strRuleDetails })
                    .html(
                        '<p><b>' + PMA_messages.strIssuse + ':</b><br />' + rule.issue + '</p>' +
                    '<p><b>' + PMA_messages.strRecommendation + ':</b><br />' + rule.recommendation + '</p>' +
                    '<p><b>' + PMA_messages.strJustification + ':</b><br />' + rule.justification + '</p>' +
                    '<p><b>' + PMA_messages.strFormula + ':</b><br />' + rule.formula + '</p>' +
                    '<p><b>' + PMA_messages.strTest + ':</b><br />' + rule.test + '</p>'
                    );

                var dlgBtns = {};
                dlgBtns[PMA_messages.strClose] = function () {
                    $(this).dialog('close');
                };

                $dialog.dialog({ width: 600, buttons: dlgBtns });
            });
        });
    }
});
