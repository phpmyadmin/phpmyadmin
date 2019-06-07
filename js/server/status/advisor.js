/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server Status Advisor
 *
 * @package PhpMyAdmin
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server/status/advisor.js', function () {
    $('a[href="#openAdvisorInstructions"]').off('click');
    $('#statustabs_advisor').html('');
    $('#advisorDialog').remove();
    $('#instructionsDialog').remove();
});

AJAX.registerOnload('server/status/advisor.js', function () {
    // if no advisor is loaded
    if ($('#advisorData').length === 0) {
        return;
    }

    /** ** Server config advisor ****/
    var $dialog = $('<div></div>').attr('id', 'advisorDialog');
    var $instructionsDialog = $('<div></div>')
        .attr('id', 'instructionsDialog')
        .html($('#advisorInstructionsDialog').html());

    $('a[href="#openAdvisorInstructions"]').on('click', function () {
        var dlgBtns = {};
        dlgBtns[Messages.strClose] = function () {
            $(this).dialog('close');
        };
        $instructionsDialog.dialog({
            title: Messages.strAdvisorSystem,
            width: '60%',
            buttons: dlgBtns
        });
    });

    var $cnt = $('#statustabs_advisor');
    var $tbody;
    var $tr;
    var even = true;

    var data = JSON.parse($('#advisorData').text());
    $cnt.html('');

    if (data.parse.errors.length > 0) {
        $cnt.append('<b>Rules file not well formed, following errors were found:</b><br>- ');
        $cnt.append(data.parse.errors.join('<br>- '));
        $cnt.append('<p></p>');
    }

    if (data.run.errors.length > 0) {
        $cnt.append('<b>Errors occurred while executing rule expressions:</b><br>- ');
        $cnt.append(data.run.errors.join('<br>- '));
        $cnt.append('<p></p>');
    }

    if (data.run.fired.length > 0) {
        $cnt.append('<p><b>' + Messages.strPerformanceIssues + '</b></p>');
        $cnt.append('<table class="data" id="rulesFired" border="0"><thead><tr>' +
                    '<th>' + Messages.strIssuse + '</th><th>' + Messages.strRecommendation +
                    '</th></tr></thead><tbody></tbody></table>');
        $tbody = $cnt.find('table#rulesFired');

        var rcStripped;

        $.each(data.run.fired, function (key, value) {
            // recommendation may contain links, don't show those in overview table (clicking on them redirects the user)
            rcStripped = $.trim($('<div>').html(value.recommendation).text());
            $tbody.append($tr = $('<tr class="linkElem noclick"><td>' +
                                    value.issue + '</td><td>' + rcStripped + ' </td></tr>'));
            even = !even;
            $tr.data('rule', value);

            $tr.on('click', function () {
                var rule = $(this).data('rule');
                $dialog
                    .dialog({ title: Messages.strRuleDetails })
                    .html(
                        '<p><b>' + Messages.strIssuse + ':</b><br>' + rule.issue + '</p>' +
                    '<p><b>' + Messages.strRecommendation + ':</b><br>' + rule.recommendation + '</p>' +
                    '<p><b>' + Messages.strJustification + ':</b><br>' + rule.justification + '</p>' +
                    '<p><b>' + Messages.strFormula + ':</b><br>' + rule.formula + '</p>' +
                    '<p><b>' + Messages.strTest + ':</b><br>' + rule.test + '</p>'
                    );

                var dlgBtns = {};
                dlgBtns[Messages.strClose] = function () {
                    $(this).dialog('close');
                };

                $dialog.dialog({ width: 600, buttons: dlgBtns });
            });
        });
    }
});
